<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class TwitterAuthentication extends OAuthAuthentication
{
    protected $OAuthProviderClass='TwitterOAuthProvider';
    protected $authorityClass = 'twitter';
    protected $userClass='TwitterOAuthUser';
	protected $API_URL = 'https://api.twitter.com/1';
	protected $useCache = true;
	protected $cacheLifetime = 900;
	protected $cache;

    protected function reset($hard=false) {
        parent::reset($hard);
        if ($hard) {
            // this where we would log out of twitter
        }
    }
    
    public function validate(&$error) {
        return true;
    }
    
    protected function getUserFromArray(array $array) {
        $user = false;
        if (isset($array['screen_name'])) {
            $user = $this->getUser($array['screen_name']);
        }
        
        return $user;
    }
	
    public function getUser($login) {
        if (empty($login)) {
            return new AnonymousUser();       
        }

        //use the cache if available
        if ($this->useCache) {
            $cacheFilename = "user_$login";
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache(CACHE_DIR . "/Twitter", $this->cacheLifetime, TRUE);
                  $this->cache->setSuffix('.json');
                  $this->cache->preserveFormat();
            }

            if ($this->cache->isFresh($cacheFilename)) {
                $data = $this->cache->read($cacheFilename);
            } else {
                $provider = $this->getOAuthProvider();
                //cache isn't fresh, load the data
                $response = $provider->oauthRequest('GET', $this->API_URL .'/users/show.json', array('screen_name'=>$login));
                if ($data = $response->getResponse()) {
                    $this->cache->write($data, $cacheFilename);
                }
                
            }
        } else {
            //load the data
            $provider = $this->getOAuthProvider();
            $response = $provider->oauthRequest('GET', $this->API_URL . '/users/show.json', array('screen_name'=>$login));
            $data = $response->getResponse();
        }
        
		// make the call
		if ($data) {
            $json = @json_decode($data, true);

            if (isset($json['screen_name'])) {
                $user = new $this->userClass($this);
                $user->setVars($json);
                return $user;
            }
        }

        return false;
    }
}

/**
  * @package Authentication
  */
class TwitterOAuthUser extends OAuthUser
{
    protected $twitter_userID;
    
    public function setTwitterUserID($userID) {
        $this->twitter_userID = $userID;
    }

    public function getTwitterUserID() {
        return $this->twitter_userID;
    }

    protected function standardAttributes() {
        return array_merge(parent::standardAttributes(), array('twitter_userID'));
    }
    
    public function setVars(array $array) {
        $this->setTwitterUserID($array['id']);
        $this->setUserID($array['screen_name']);
        $this->setFullName($array['name']);
    }
    
}
