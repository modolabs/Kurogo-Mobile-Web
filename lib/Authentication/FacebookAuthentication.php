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
 * Facebook Authentication
 * @package Authentication
 */

/**
 * Facebook authentication
 * @package Authentication
 */
class FacebookAuthentication extends AuthenticationAuthority
{
    protected $authorityClass = 'facebook';
    protected $userClass='FacebookUser';
    protected $api_key;
    protected $api_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $expires;
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    protected $perms = array(
        'user_about_me',
        'email',
    );
    
    protected function validUserLogins()
    {
        return array('LINK', 'NONE');
    }
    
    // auth is handled by fb
    public function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    // facebook can only get the current user
    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        //use the cache if available. Don't use the cache for the special me user
        if ($this->useCache && $login != 'me') {
            $cacheFilename = "user_$login";
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache(CACHE_DIR . "/Facebook", $this->cacheLifetime, TRUE);
                  $this->cache->setSuffix('.json');
                  $this->cache->preserveFormat();
            }

            if ($this->cache->isFresh($cacheFilename)) {
                $data = $this->cache->read($cacheFilename);
            } else {

                //get the data
                $url = sprintf("https://graph.facebook.com/%s?%s", $login, http_build_query(array(
                'fields'=>'id,first_name,last_name,email,picture,gender',
                'access_token'=>$this->access_token
                )));

                if ($data = @file_get_contents($url)) {
                    $this->cache->write($data, $cacheFilename);
                }
                
            }
        } else {
            //get the data
            $url = sprintf("https://graph.facebook.com/%s?%s", $login, http_build_query(array(
            'fields'=>'id,first_name,last_name,email,picture,gender',
            'access_token'=>$this->access_token
            )));

            $data = @file_get_contents($url);
        }
        
		if ($data) {
            $json = @json_decode($data, true);

            if (isset($json['id'])) {
                $user = new $this->userClass($this);
                $user->setUserID($json['id']);
                $user->setFirstName($json['first_name']);
                $user->setLastName($json['last_name']);
                if (isset($json['email'])) {
                    $user->setEmail($json['email']);
                }
                return $user;
            }        
        }
        
        return false;
    }
    
    public function login($login, $pass, Session $session, $options)
    {
        //if the code is present, then this is the callback that the user authorized the application
        if (isset($_GET['code'])) {
        
            // if a redirect_uri isn't set than we can't get an access token
            if (!isset($_SESSION['redirect_uri'])) {
                return AUTH_FAILED;
            }
            
            $this->redirect_uri = $_SESSION['redirect_uri'];
            unset($_SESSION['redirect_uri']);
            
            //get access token
            $url = "https://graph.facebook.com/oauth/access_token?" . http_build_query(array(
                'client_id'=>$this->api_key,
                'redirect_uri'=>$this->redirect_uri,
                'client_secret'=>$this->api_secret,
                'code'=>$_GET['code']
            ));
                                    
            if ($result = @file_get_contents($url)) {
                
                parse_str($result, $vars);
                foreach ($vars as $arg=>$value) {
                    switch ($arg) 
                    {
                        case 'access_token':
                        case 'expires':
                            $this->$arg = $_SESSION['fb_' . $arg] = $value;                        
                            break;
                    }
                }

                // get the current user via API
                if ($user = $this->getUser('me')) {
                    $session->login($user);
                    return AUTH_OK;
                }  else {
                    return AUTH_FAILED; // something is amiss
                }

            } else {
                return AUTH_FAILED; //something is amiss
            }
            
        } elseif (isset($_GET['error'])) {
            //most likely the user denied
            return AUTH_FAILED;
        } else {
            
            //find out which "display" to use based on the device
            $deviceClassifier = Kurogo::deviceClassifier();
            $display = 'page';
            switch ($deviceClassifier->getPagetype())
            {
                case 'compliant':
                    $display = $deviceClassifier->isComputer() ? 'page' : 'touch';
                    break;
                case 'basic':
                    $display = 'wap';
                    break;
            }
            
            

            // facebook does not like empty options
            foreach ($options as $option=>$value) {
                if (strlen($value)==0) {
                    unset($options[$option]);
                }
            }

            //save the redirect_uri so we can use it later
            $this->redirect_uri = $_SESSION['redirect_uri'] = FULL_URL_BASE . 'login/login?' . http_build_query(
                array_merge($options, 
                array('authority'=>$this->getAuthorityIndex())));

            //show the authorization/login screen
            $url = "https://graph.facebook.com/oauth/authorize?" . http_build_query(array(
                'client_id'=>$this->api_key,
                'redirect_uri'=>$this->redirect_uri,
                'scope'=>implode(',', $this->perms),
                'display'=>$display
            ));
            
            Kurogo::redirectToURL($url);
        }
    }
    
    protected function reset($hard=false)
    {
        parent::reset($hard);
        unset($_SESSION['fb_expires']);
        unset($_SESSION['fb_access_token']);
        if ($hard) {
            // this where we would log out of facebook
        }
    }
    
    //does not support groups
    public function getGroup($group)
    {
        return false;
    }

    public function validate(&$error) {
        return true;
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        if (!isset($args['FACEBOOK_API_KEY'], $args['FACEBOOK_API_SECRET']) ||
            strlen($args['FACEBOOK_API_KEY'])==0 || strlen($args['FACEBOOK_API_SECRET'])==0) {
            throw new KurogoConfigurationException("API key and secret not set");
        }

        $this->api_key = $args['FACEBOOK_API_KEY'];
        $this->api_secret = $args['FACEBOOK_API_SECRET'];
        if (isset($_SESSION['fb_access_token'])) {
            $this->access_token = $_SESSION['fb_access_token'];
        }

        if (isset($args['FACEBOOK_API_PERMS'])) {
            $this->perms = array_unique(array_merge($this->perms, $args['FACEBOOK_API_PERMS']));
        }
    }
    
    public function getSessionData(FacebookUser $user) {
        return array(
            'fb_access_token'=>$this->access_token
        );
    }

    public function setSessionData($data) {
        if (isset($data['fb_access_token'])) {
            $this->access_token = $data['fb_access_token'];
        }
    }        
}

/**
 * Facebook user
 * @package Authentication
 */
class FacebookUser extends User
{
    public function getSessionData() {
        return $this->AuthenticationAuthority->getSessionData($this);   
    }

    public function setSessionData($data) {
        $this->AuthenticationAuthority->setSessionData($data);
    }
}
