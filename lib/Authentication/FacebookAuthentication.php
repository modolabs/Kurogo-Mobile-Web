<?php
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
    protected $api_key;
    protected $api_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $expires;
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    
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
                $user = new FacebookUser($this);
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
    
    public function login($login, $pass, Module $module, $options)
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
                    $session = $module->getSession();
                    $remainLoggedIn = isset($options['remainLoggedIn']) ? $options['remainLoggedIn'] : false;
                    $session->login($user, $remainLoggedIn);
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
            $deviceClassifier = $GLOBALS['deviceClassifier'];
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
            
            
            //save the redirect_uri so we can use it later
            $this->redirect_uri = $_SESSION['redirect_uri'] = FULL_URL_BASE . 'login/login?' . http_build_query(
                array_merge($options, 
                array('authority'=>$this->getAuthorityIndex())));

            //show the authorization/login screen
            $url = "https://graph.facebook.com/oauth/authorize?" . http_build_query(array(
                'client_id'=>$this->api_key,
                'redirect_uri'=>$this->redirect_uri,
                'scope'=>'user_about_me,email',
                'display'=>$display
            ));
            
            header("Location: $url");
            exit();
        }
    }
    
    protected function reset()
    {
        unset($_SESSION['fb_expires']);
        unset($_SESSION['fb_access_token']);
    }
    
    //does not support groups
    public function getGroup($group)
    {
        return false;
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        if (!isset($args['API_KEY'], $args['API_SECRET']) ||
            strlen($args['API_KEY'])==0 || strlen($args['API_SECRET'])==0) {
            throw new Exception("API key and secret not set");
        }

        $this->api_key = $args['API_KEY'];
        $this->api_secret = $args['API_SECRET'];
        if (isset($_SESSION['fb_access_token'])) {
            $this->access_token = $_SESSION['fb_access_token'];
        }
    }
    
    public function getSessionData(OAuthUser $user) {
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