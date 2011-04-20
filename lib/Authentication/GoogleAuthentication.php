<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAuthentication extends OAuthAuthentication
{
    protected $realm;
    protected $authorityClass = 'google';
    protected $userClass='GoogleUser';
    protected $requestTokenURL = 'https://www.google.com/accounts/OAuthGetRequestToken';
    protected $authorizeTokenURL = 'https://www.google.com/accounts/OAuthAuthorizeToken';
    protected $accessTokenURL = 'https://www.google.com/accounts/OAuthGetAccessToken';
    protected $useCache = true;
    protected $scope = array();
    protected $requireLogin = -1;
    protected $oauth=false;
    protected $cache;
    
    public function validate(&$error) {
        return true;
    }

    protected function reset($hard=false)
    {
        parent::reset($hard);
        if ($hard) {
            // this where we would log out of google
        }
    }

    protected function getUserFromArray(array $array) {
        
        if ($ns = $this->getOpenIDNamespace('http://specs.openid.net/extensions/oauth/1.0', $array)) {
            if ($request_token = $this->getOpenIDValue('request_token', $ns, $array)) {
        		$this->setToken($request_token);
                if (!$this->getAccessToken()) {
                    throw new Exception("Error getting OAuth Access token");
                }
               
                // do not save request token
                unset($array['openid_' . $ns . '_request_token']);
            }
        }
    
        $user = new $this->userClass($this);
        if ($user->setVars($array)) {
            $this->cacheUserArray($user->getUserID(), $array);
            return $user;
        }
        
        return false;
    }

    protected function getOpenIDNameSpace($uri, $var) {
        if ($key = array_search($uri, $var)) {
            if (preg_match("/^openid_ns_(.*)$/", $key, $ns)) {
                return $ns[1];
            }
        }
        
        return false;
    }    
    
    protected function getOpenIDValue($value, $ns, $var) {
        return isset($var['openid_' . $ns . '_' . $value]) ? $var['openid_' . $ns . '_' . $value] : false;
    }    

    protected function cacheUserArray($login, array $array) {
        return file_put_contents($this->cacheFile($login), serialize($array));
    }

    protected function cacheFile($login) {
        $cacheDir = CACHE_DIR . '/GoogleOpenID' ;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }
        return $cacheDir . "/" . md5($login);
    }

    public function login($login, $pass, Session $session, $options) {
        $startOver = isset($_REQUEST['startOver']) ? $_REQUEST['startOver'] : false;
        
        //if openid_identity is set then we are in the callback
        $ARGS = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
        if (isset($ARGS['openid_mode'])) {
            if (isset($ARGS['openid_identity'])) {
                if ($user = $this->getUserFromArray($ARGS)) {
    
                    $session->login($user);
                    return AUTH_OK;
                } else {
                    error_log("Unable to find user");
                    return AUTH_FAILED;
                }
            } else {
                error_log("openid_identity not found");
                return AUTH_FAILED;
            }
        } else {
        
            //redirect to auth page
            $url = $this->getAuthURL($options);
            header("Location: " . $url);
            exit();
        }
    }
    
    public function getUser($login) {

        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        if ($user = $this->loadUser($login)) {
            return $user;
        }

        return false;
    }
    
    private function loadUser($login) {

        $filename = $this->cacheFile($login) ;
        if (file_exists($filename)) {
            if ($array = unserialize(file_get_contents($filename))) {
                return $this->getUserFromArray($array);
            }
        }
        
        return false;
    }    
    
    protected function getOpenIDEndpoint() {

        $url = "https://www.google.com/accounts/o8/id" ;
        if ($xrds = file_get_contents($url)) {
            if (preg_match("#<URI>(.*?)</URI>#", $xrds, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }
    
    public function oauth() {
        return $this->oauth;
    }

    protected function getAuthURL(array $params) {
    
        if (!$url = $this->getOpenIDEndpoint()) {
            throw new Exception("Unable to get Google OpenID endpoint.");
        }
                
        $url_parts = parse_url(FULL_URL_BASE);

        if ($this->realm) {
            $realm = $this->realm;
            if (stripos($realm, $url_parts['host'])===false) {
                throw new Exception("OpenID Realm $this->realm must match server name " . $url_parts['host']);
            }
            
        } else {
            $realm = sprintf("http://%s", $url_parts['host']);
            if (!in_array($_SERVER['SERVER_PORT'], array(80,443))) {
                $realm .= ":" . $_SERVER['SERVER_PORT'];
            }
        }
        
        $parameters = array(
            'openid.mode'=>'checkid_setup',
            'openid.ns'=>'http://specs.openid.net/auth/2.0',
            'openid.claimed_id'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.return_to'=>FULL_URL_BASE . 'login/login?' . http_build_query(array_merge($params, array(
                'authority'=>$this->getAuthorityIndex()
		        ))),
            'openid.realm'=>$realm,
            'openid.ns.ax'=>'http://openid.net/srv/ax/1.0',
            'openid.ax.mode'=>'fetch_request',
            'openid.ax.required'=>'email,firstname,lastname',
            'openid.ax.type.email'=>'http://schema.openid.net/contact/email',
            'openid.ax.type.firstname'=>'http://axschema.org/namePerson/first',
            'openid.ax.type.lastname'=>'http://axschema.org/namePerson/last'
        );
        
        if ($this->requireLogin >= 0) {
            $parameters = array_merge($parameters, array(
                'openid.ns.pape'=>'http://specs.openid.net/extensions/pape/1.0',
                'openid.pape.max_auth_age'=>$this->requireLogin
            ));
        }
        
        if ($this->oauth) {
            if ($realm != ("http://". $this->consumer_key)) {
                throw new Exception("Google OpenID + OAuth will only work if the realm ($realm) and consumer key ($this->consumer_key) are the same");
            }
            
            $parameters = array_merge($parameters, array(
                'openid.ns.oauth'=>'http://specs.openid.net/extensions/oauth/1.0',
                'openid.oauth.consumer'=>$this->consumer_key,
                'openid.oauth.scope'=>implode(" ", $this->scope)
            ));
        }
        
	    $url .= stripos($url, "?") ? '&' : '?';
        $url .= http_build_query($parameters);
        
        return $url;
    }

    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (isset($args['GOOGLE_REQUIRE_LOGIN'])) {
            $this->requireLogin = $args['GOOGLE_REQUIRE_LOGIN'];
        }

        if (isset($args['OPENID_REALM'])) {
            if (!preg_match("@^https?://@", $args['OPENID_REALM'])) {
                throw new Exception("Invalid OpenID realm {$args['OPENID_REALM']}. Realm must be a full url");
            }

            $this->realm = $args['OPENID_REALM'];
        }

        if (isset($args['OAUTH_CONSUMER_KEY'], $args['OAUTH_CONSUMER_SECRET'])) {
            $this->consumer_key = $args['OAUTH_CONSUMER_KEY'];
            $this->consumer_secret = $args['OAUTH_CONSUMER_SECRET'];
            if (!isset($args['GOOGLE_SCOPE'])) {
                throw new Exception("GOOGLE_SCOPE parameter must be specified");
            } elseif (!is_array($args['GOOGLE_SCOPE'])) {
                throw new Exception("GOOGLE_SCOPE parameter is not an array");
            }
            
            $this->scope = $args['GOOGLE_SCOPE'];
            $this->oauth = true;
        }
    }
}

/**
  * @package Authentication
  */
class GoogleUser extends OAuthUser
{

    protected function valueKeyForTypeKey($key) {
        if (preg_match("/^openid_(.*?)_type_(.*?)$/", $key, $matches)) {
            return sprintf("openid_%s_value_%s", $matches[1], $matches[2]);
        }
        
        return null;
    }
    
    public function setVars(array $array) {
    
        if (!isset($array['openid_identity'])) {
            return false;
        }
        
        $this->setUserID($array['openid_identity']);
        
        if ( ($type_key = array_search('http://schema.openid.net/contact/email', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setEmail($array[$value_key]);
            }
        }

        if ( ($type_key = array_search('http://axschema.org/namePerson/first', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setFirstName($array[$value_key]);
            }
        }

        if ( ($type_key = array_search('http://axschema.org/namePerson/last', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setLastName($array[$value_key]);
            }
        }

        if ($token = $this->AuthenticationAuthority->getToken()) {
            $this->setToken($token);
            $this->setTokenSecret($this->AuthenticationAuthority->getTokenSecret());
            $this->setUserData('oauth_token', $this->token);
            $this->setUserData('oauth_token_secret', $this->tokenSecret);
        } elseif ($this->AuthenticationAuthority->oauth()) {
            if (!$token = $this->getUserData('oauth_token')) {
                throw new Exception("Unable to load OAuth tokens for " . $this->getFullName());
            }
            $tokenSecret = $this->getUserData('oauth_token_secret');
            $this->setToken($token);
            $this->setTokenSecret($token_secret);
            $this->AuthenticationAuthority->setToken($token);
            $this->AuthenticationAuthority->setTokenSecret($token_secret);
        }
        
        return true;
    }
}
