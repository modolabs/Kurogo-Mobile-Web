<?php

includePackage('Authorization');

class TwitterDataRetriever extends OAuthDataRetriever implements SocialDataRetriever
{
    protected $DEFAULT_PARSER_CLASS='TwitterDataParser';
//    protected $USER_PARSER_CLASS='TwitterUserParser';
    protected $OAuthProviderClass='TwitterOAuthProvider';
    protected $cacheFolder='Twitter';

    public function search($q, $start=0, $limit=null) {
        Debug::Die_herE();    
        return $this->retrieveData();
    }
    
    public function getUser($userID) {
        $this->setBaseURL("http://api.twitter.com/1/users/show/$userID.json");
        return $this->retrieveData();
    }

    public function getPost($id) {
        $this->setBaseURL("http://api.twitter.com/1/statuses/show/$id.json");
        return $this->retrieveData();
    }
    
    public function canRetrieve() {
        return true;
    }

    public function canPost() {
        return false;
    }
    
    protected function setUser($user) {
        $this->setBaseURL('http://api.twitter.com/1/statuses/user_timeline.json');
        $this->addFilter('screen_name', $user);
        $this->addFilter('trim_user', 1);
    }
    
    public function init($args) {
        parent::init($args);
        if (isset($args['ACCOUNT'])) {
            $this->setUser($args['ACCOUNT']);
        }
        
    }
}

class TwitterDataParser extends DataParser
{
    private function parsePost($entry) {
        $post = new TwitterPost();
        $post->setID($entry['id']);
        $post->setAuthor($entry['user']['id']);
        $post->setCreated(new DateTime($entry['created_at']));
        $post->setBody($entry['text']);
        $post->setParentID($entry['in_reply_to_status_id']);        
        return $post;
    }

    private function parseUser($entry) {
        $user = new TwitterUser();
        $user->setUserID($entry['id']);
        $user->setName($entry['name']);
        $user->setImageURL(IS_SECURE ? $entry['profile_image_url_https'] : $entry['profile_image_url']);
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
}