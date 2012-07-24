<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Authorization');

class TwitterDataRetriever extends OAuthDataRetriever implements SocialDataRetriever, ItemDataRetriever
{
    protected $DEFAULT_PARSER_CLASS='TwitterDataParser';
    protected $OAuthProviderClass='TwitterOAuthProvider';
    protected $cacheGroup='Twitter';
    protected $user;
    
    public function getServiceName() {
        return 'twitter';
    }
    
    public function getAccount() {
        return $this->user;
    }
    
    public function getPosts() {
        $this->setBaseURL('http://api.twitter.com/1/statuses/user_timeline.json');
        $this->addFilter('screen_name', $this->user);
        $this->addFilter('trim_user', 1);
        return $this->getData();
    }

    public function getUser($userID) {
        $this->setBaseURL("http://api.twitter.com/1/users/show/$userID.json");
        return $this->getData();
    }

    public function getItem($id, &$response=null) {
        $this->setBaseURL("http://api.twitter.com/1/statuses/show/$id.json");
        return $this->getData();
    }
    
    public function canRetrieve() {
        return true;
    }

    public function canPost() {
        return false;
    }
    
    protected function setUser($user) {
        /* @TODO Validate User string */
        $this->user = $user;
    }
    
    public function init($args) {
        parent::init($args);
        if (isset($args['ACCOUNT'])) {
            $this->setUser($args['ACCOUNT']);
        }
        
    }

    public function cacheKey()
    {
        return URLDataRetriever::cacheKey();
    }
}

class TwitterDataParser extends DataParser
{
    private function parsePost($entry) {
        $post = new TwitterPost();
        $post->setID($entry['id_str']);
        $post->setAuthor($entry['user']['id_str']);
        $post->setCreated(new DateTime($entry['created_at']));
        $post->setBody($entry['text']);
        $post->setParentID($entry['in_reply_to_status_id']);        
        return $post;
    }

    private function parseUser($entry) {
        $user = new TwitterUser();
        $user->setUserID($entry['id_str']);
        $user->setName('@'.$entry['screen_name']);
        $user->setImageURL(ImageLoader::cacheImage(IS_SECURE ? $entry['profile_image_url_https'] : $entry['profile_image_url'], array()));
        return $user;
    }

    public function parseResponse(DataResponse $response) {
        $data = $response->getResponse();
        return $this->parseData($data);
    }
        
    public function parseData($data) 
    {
        if ($data = json_decode($data, true)) {
            if (is_array($data)) {
                if (count($data)>0 && isset($data[0])) {
                    $return = array();
                    foreach ($data as $entry) {
                        if (isset($entry['retweet_count'])) {
                            $post = $this->parsePost($entry);
                            $return[] = $post;
                        } else {
                            Debug::die_here($entry);
                        }
                    }
                    return $return;
                } elseif (isset($data['id_str'], $data['text'], $data['created_at'])) {
                    return $this->parsePost($data);
                } elseif (isset($data['screen_name'])) {
                    return $this->parseUser($data);
                }
            }
        }
        
        return array();
    }
}

class TwitterUser extends SocialMediaUser
{
    public function getProfileURL()
    {
        return 'https://twitter.com/intent/user?'.http_build_query(array('user_id' => $this->getUserID()));
    }
}

class TwitterPost extends SocialMediaPost
{
    protected $serviceName = 'twitter';
    public function getReplyURL() {
        return 'https://twitter.com/intent/tweet?'.http_build_query(array('in_reply_to' => $this->getID()));
    }
    public function getLikeURL() {
        return 'https://twitter.com/intent/favorite?'.http_build_query(array('tweet_id' => $this->getID()));
    }
    public function getShareURL()
    {
        return 'https://twitter.com/intent/retweet?'.http_build_query(array('tweet_id' => $this->getID()));
    }
    public function getLinks()
    {
        $links = array();
        $links['reply'] = array('url' => $this->getReplyURL(), 'title' => 'Reply', 'service' => $this->getServiceName());
        $links['share'] = array('url' => $this->getShareURL(), 'title' => 'Retweet', 'service' => $this->getServiceName());
        $links['like']  = array('url' => $this->getLikeURL(),  'title' => 'Favorite', 'service' => $this->getServiceName());
        return $links;
    }

    public function linkify($post)
    {
        return parent::linkify($post);
    }
}
