<?php

class FacebookDataRetriever extends URLDataRetriever implements ItemDataRetriever, SocialDataRetriever
{
    protected $DEFAULT_PARSER_CLASS='FacebookDataParser';
    protected $cacheGroup = 'Facebook';
    protected $graphURL = 'https://graph.facebook.com/';
    protected $userURL;
    protected $clientId;
    protected $clientSecret;
    protected $user;

    public function getServiceName() {
        return 'facebook';
    }
    
    public function getAccount() {
        return $this->user;
    }
    
    public function getUser($userID)
    {
        $this->setBaseURL($this->graphURL.$userID);
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
    
    public function getItem($id, &$response=null)
    {
        $this->setBaseURL($this->graphURL.$id);
        return $this->getData();
    }
    
    public function setUser($user)
    {
        $this->user = $user;
        $this->userURL = $this->graphURL.$user;
        $this->setBaseURL($this->userURL.'/feed');
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

        // @TODO why is this done every time??        
        $this->getAccessToken();
    }
    
    private function getAccessToken()
    {
        $query = array(
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'client_credentials'
        );
        $atURL = 'https://graph.facebook.com/oauth/access_token?'.http_build_query($query);
        list($name, $token) = explode('=', file_get_contents($atURL));
        $this->addFilter($name, $token);
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
        return $post;
    }

    private function parseUser($entry) {
        $user = new FacebookUser();
        $user->setUserID($entry['id']);
        $user->setName($entry['name']);
       /* if(isset($entry['picture']))
        {
            $user->setImageURL($entry['picture']);
        }
        else
        {*/
            // The below code alone causes an 800% increase in run-time.  This is the more correct way of doing it, but it's way to slow.  Maybe this + caching?
            /*$headers = get_headers('https://graph.facebook.com/'.$entry['id'].'/picture', 1);
            if(is_array($headers['Location']))
            {
                $user->setImageURL(end($headers['Location']));
            }
            else
            {
                $user->setImageURL($headers['Location']);
            }*/

            $user->setImageURL(ImageLoader::cacheImage('https://graph.facebook.com/'.$entry['id'].'/picture?type=square', array()));
        //}
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
                // Multiple entries
                if ($this->getOption('action') !== 'user' && isset($data['data']) && count($data['data'])>0 && isset($data['data'][0])) {
                    $return = array();
                    foreach ($data['data'] as $entry) {
                        $post = $this->parsePost($entry);
                        if($post->getBody())
                        {
                            $return[] = $post;
                        }
                    }
                    return $return;
                // Single entry
                } elseif ($this->getOption('action') !== 'user') {
                    return $this->parsePost($data);
                } elseif (isset($data['id'])) {
                    return $this->parseUser($data);
                }
            }
        }
        
        return array();
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
    protected $serviceName = 'twitter';

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
