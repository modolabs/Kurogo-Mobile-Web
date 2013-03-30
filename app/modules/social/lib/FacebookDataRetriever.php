<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class FacebookDataRetriever extends URLDataRetriever implements ItemDataRetriever, SocialDataRetriever
{
    protected $DEFAULT_PARSER_CLASS='FacebookDataParser';
    protected $cacheGroup = 'Facebook';
    protected $clientId;
    protected $clientSecret;
    protected $user;
    protected $accessToken;

    public function getServiceName() {
        return 'facebook';
    }
    
    public function getAccount() {
        return $this->user;
    }
    
    public function getUser($userID)
    {
    	$this->requiresAccessToken();
        $this->setBaseURL(sprintf('https://graph.facebook.com/%s/', $this->user));
        $this->setOption('action', 'user');
        return $this->getData();
    }
    
    public function canRetrieve()
    {
        return true;
    
    }
    public function canPost()
    {
        return false;
    }
    
    public function auth(array $options)
    {
    }
    
    public function getPosts() {
    	$this->requiresAccessToken();
		$this->setBaseURL(sprintf('https://graph.facebook.com/%s/posts', $this->user));
        $this->setOption('action', 'posts');
		if ($limit = $this->getOption('limit')) {
			$this->addParameter('limit',$limit);
		}
		$data = $this->getData();
		return $this->getData();
    }
    
    public function getItem($id, &$response=null)
    {
    	$this->requiresAccessToken();
        $this->setBaseURL(sprintf('https://graph.facebook.com/%s', $id));
        $this->setOption('action', 'post');
        return $this->getData();
    }
    
    private function requiresAccessToken() {
    	if (!$this->accessToken) {
			$this->clearInternalCache();
			$this->setBaseURL('https://graph.facebook.com/oauth/access_token');
			$this->addParameter('client_id', $this->clientId);
			$this->addParameter('client_secret', $this->clientSecret);
			$this->addParameter('grant_type', 'client_credentials');
	    	$this->setOption('action', 'getAccessToken');
	        $response = $this->getResponse();
    	    list($name, $token) = 	explode("=", $response->getResponse());
        	if (!$token) {
        		throw new KurogoDataException("Unable to retrieve facebook access token");
        	}
        	$this->accessToken = $token;
			$this->clearInternalCache();
    	}
    	    	
    	return $this->accessToken;
    }
    
    protected function initRequest() {
    	if ($this->accessToken) {
			$this->addParameter('access_token', $this->accessToken);
    	}
    }
    
    public function setUser($user)
    {
        $this->user = $user;
    }
    
    public function init($args) {
        parent::init($args);

        if (!isset($args['ACCOUNT'])) {
            throw new KurogoConfigurationException("ACCOUNT must be set for Facebook");
        }
        
        $this->setUser($args['ACCOUNT']);

        if (!isset($args['OAUTH_CONSUMER_KEY'])) {
            throw new KurogoConfigurationException("OAUTH_CONSUMER_KEY must be set for Facebook");
        }
        $this->clientId = $args['OAUTH_CONSUMER_KEY'];

        if (!isset($args['OAUTH_CONSUMER_SECRET'])) {
            throw new KurogoConfigurationException("OAUTH_CONSUMER_SECRET must be set for Facebook");
        }
        $this->clientSecret = $args['OAUTH_CONSUMER_SECRET'];
    }
    
}

class FacebookDataParser extends DataParser
{
    private function parsePost($entry) {
        $post = new FacebookPost();
        $post->setID($entry['id']);
        $post->setAuthor($entry['from']['id']);
        $post->setCreated(new DateTime($entry['created_time']));
        if(!empty($entry['message']))
        {
            $post->setBody($entry['message']);
        }
        
        if (isset($entry['likes'])) {
            $post->setLikeCount($entry['likes']['count']);
        }
        
        switch ($entry['type'])
        {
            case 'photo':
                if (isset($entry['source'])) {
                    $post->addImage($entry['source']);
                } elseif (preg_match("/^(.*)_s\.jpg$/", $entry['picture'], $bits)) {
                    $post->addImage($bits[1].'_n.jpg');
                } else {
                    $post->addImage($entry['picture']);
                }
                break;
            case 'link':
            case 'video':
            case 'status':
            case 'question':
                break;
            default:
                throw new KurogoDataException("Unhandled facebook type " . $entry['type']);
        }
        
        return $post;
    }

    private function parseUser($entry) {
        $user = new FacebookUser();
        $user->setUserID($entry['id']);
        $user->setName($entry['name']);
		$user->setImageURL(ImageLoader::cacheImage('https://graph.facebook.com/'.$entry['id'].'/picture?type=square', array()));
        return $user;
    }

    public function parseData($data) 
    {
        if ($data = json_decode($data, true)) {
            if (is_array($data)) {
		    	$action = $this->getOption('action');
		    	switch ($action)
		    	{
		    		case 'user':
	                    return $this->parseUser($data);
		    			break;
		    		case 'posts':
						$return = array();
						$posts = Kurogo::arrayVal($data, 'data', array());
						foreach ($posts as $entry) {
							$post = $this->parsePost($entry);
							if ($post->getBody()) {
								$return[] = $post;
							}
						}
						return $return;
		    			break;
		    		case 'post':
	                    return $this->parsePost($data);
		    			break;
		    	}
            }
        }
        
        return null;
    }
}

class FacebookUser extends SocialMediaUser
{
    public function getProfileURL()
    {
        return 'http://www.facebook.com/profile.php?'.http_build_query(array('id' => $this->getUserID()));
    }
}

class FacebookPost extends SocialMediaPost
{
    protected $serviceName = 'facebook';

    public function getReplyURL() {
        return null;
    }
    public function getLikeURL() {
        return null; 
    }
    public function getShareURL()
    {
        return null;
    }

    public function getLinks()
    {
        $links = array();
        //$links['reply'] = array('url' => $this->getReplyURL(), 'title' => 'Reply', 'service' => $this->getServiceName());
        //$links['share'] = array('url' => $this->getShareURL(), 'title' => 'Share', 'service' => $this->getServiceName());
        //$links['like']  = array('url' => $this->getLikeURL(),  'title' => 'Favorite', 'service' => $this->getServiceName());
        return $links;
    }
}
