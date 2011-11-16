<?php

includePackage('Authorization');

class TwitterSocialMediaController extends SocialMediaController
{
    protected $DEFAULT_PARSER_CLASS='TwitterDataParser';
//    protected $USER_PARSER_CLASS='TwitterUserParser';
    protected $OAuthProviderClass='TwitterOAuthProvider';
    protected $cacheFolder='Twitter';
    protected $oauth;
    

    public function auth(array $options) {
        $oauth = $this->oauth();
        return $oauth->auth($options, $userData);
    }
    
    public function search($q, $start=0, $limit=null) {
    
    }

    public function getUser($userID) {
        $this->setBaseURL("http://api.twitter.com/1/users/show/$userID.json");
        if ($data = $this->getParsedData()) {
            return $data;
        }
        
        return false;
    }

    public function getItem($id) {
        $this->setBaseURL("http://api.twitter.com/1/statuses/show/$id.json");
        if ($data = $this->getParsedData()) {
            return $data;
        }
        
        return false;
    }

    public function canRetrieve() {
        return true;
    }

    public function canPost() {
        return false;
    }
    
    protected function retrieveData($url) {    
        if (!$this->canRetrieve()) {
            return false;
        }

        $oauth = $this->oauth();
        $authorization = $oauth->getAuthorizationHeader($this->method, $url);
        $this->addHeader('Authorization', $authorization);
        
        return parent::retrieveData($url);
    }
    
    protected function oauth() {
        if (!$this->oauth) {
            $this->oauth = OAuthProvider::factory($this->OAuthProviderClass, $this->initArgs);
        }
        return $this->oauth;
    }
    
    protected function setTwitterURL() {
        $this->setBaseURL('http://api.twitter.com/1/statuses/user_timeline.json');
        if (isset($this->initArgs['ACCOUNT'])) {
            $this->addFilter('screen_name', $this->initArgs['ACCOUNT']);
            $this->addFilter('trim_user', 1);
        }
    }

    public function init($args) {
        parent::init($args);
        $this->setTwitterURL();
    }
}

class TwitterDataParser extends DataParser
{
    private function parsePost($entry) {
        $post = new TwitterPost($this->dataController);
        $post->setID($entry['id']);
        $post->setAuthor($entry['user']['id']);
        $post->setCreated(new DateTime($entry['created_at']));
        $post->setBody($entry['text']);
        $post->setParentID($entry['in_reply_to_status_id']);        

        return $post;
    }

    private function parseUser($entry) {
        $user = new TwitterUser($this->dataController);
        $user->setUserID($entry['id']);
        $user->setName($entry['name']);
        $user->setImageURL(IS_SECURE ? $entry['profile_image_url_https'] : $entry['profile_image_url']);
        return $user;
    }

    public function parseData($data) {
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
                } elseif (isset($data['id'], $data['text'], $data['created_at'])) {
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
}

class TwitterPost extends SocialMediaPost
{
    public function getReplyURL() {
        Debug::die_here($this);
    }
    
    public function getLikeURL() {
        Debug::die_here($this);
    }

    public function getAuthorUser() {
        return $this->dataController->getUser($this->author);
    }
    
}
