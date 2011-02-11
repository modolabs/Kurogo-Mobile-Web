<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAppsAuthentication extends AuthenticationAuthority
{
    protected $domain;
    protected $oauth = false;

    public function getDomain()
    {
        return $this->domain;
    }

    // auth is handled by openID
    protected function auth($login, $password, &$user) {
        return AUTH_FAILED;
    }

    //does not support groups
    public function getGroup($group) {
        return false;
    }

    //Not sure if we can get users or not...
    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        if ($user = $this->loadUser($login)) {
            return $user;
        }

        return false;
    }
    
    private function loadUser($login)
    {
        $filename = $this->cacheFile($login) ;
        if (file_exists($filename)) {
            if ($array = unserialize(file_get_contents($filename))) {
                return $this->getUserFromArray($array);
            }
        }
        
        return false;
    }
    
    protected function cacheUserArray($login, array $array)
    {
        return file_put_contents($this->cacheFile($login), serialize($array));
    }
    
    protected function cacheFile($login)
    {
        $cacheDir = CACHE_DIR . '/OpenID' ;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }
        return $cacheDir . "/" . md5($login);
    }
    
    protected function getUserFromArray(array $array)
    {
        $user = new GoogleAppsUser($this);
        if ($user->setVars($array)) {
            $this->cacheUserArray($user->getUserID(), $array);
            return $user;
        }
        
        return false;
    }


    /**
     * Discovers the OpenID Endpoint for a Google Apps domain
     * http://groups.google.com/group/google-federated-login-api/web/openid-discovery-for-hosted-domains?pli=1
	 * @return string, a url for the OpenID endpoint (i.e the login page)
     */
    protected function getOpenIDEndpoint()
    {
        $url = "https://www.google.com/accounts/o8/.well-known/host-meta?hd=" . $this->domain;
        if ($host_meta = file_get_contents($url)) {
            if (preg_match("/Link: <(.*?)>/", $host_meta, $matches)) {
                $url = $matches[1];
                if ($xrds = file_get_contents($url)) {
                    if (preg_match("#<URI>(.*?)</URI>#", $xrds, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return false;
    }
    
    private function getOpenIDNameSpace($uri, $var)
    {
        if ($key = array_search($uri, $var)) {
            if (preg_match("/^openid_ns_(.*)$/", $key, $ns)) {
                return $ns[1];
            }
        }
        
        return false;
    }

    private function getOpenIDValue($value, $ns, $var)
    {
        return isset($var['openid_' . $ns . '_' . $value]) ? $var['openid_' . $ns . '_' . $value] : false;
    }

    public function login($login, $pass, Module $module)
    {
        $startOver = isset($_GET['startOver']) ? $_GET['startOver'] : false;
        $url = isset($_GET['url']) ? urldecode($_GET['url']) : '';
        //see if we already have a request token
        if ($startOver) {
            $this->reset();
        }
        
        //if openid_identity is set then we are in the callback
        $ARGS = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
        if (isset($ARGS['openid_mode'])) {
            if (isset($ARGS['openid_identity'])) {
                if ($user = $this->getUserFromArray($ARGS)) {
                    $session = $module->getSession();
                    $session->login($user);
                    return AUTH_OK;
                } else {
                    return AUTH_FAILED;
                }
            } else {
                return AUTH_FAILED;
            }
        } else {
        
            //redirect to auth page
            $url = $this->getAuthURL(array('url'=>$url));
            header("Location: " . $url);
            exit();
        }
    }
    
    protected function getAuthURL(array $params)
    {
        if (!$url = $this->getOpenIDEndpoint()) {
            throw new Exception("Unable to get OpenID endpoint url for $this->domain.");
        }
        
        $url_parts = parse_url(FULL_URL_BASE);

        $realm = sprintf("http://%s%s", $this->domain != $url_parts['host'] ? '*.' : '', $this->domain);
        if (!in_array($_SERVER['SERVER_PORT'], array(80,443))) {
            $realm .= ":" . $_SERVER['SERVER_PORT'];
        }

        $parameters = array(
            'openid.mode'=>'checkid_setup',
            'openid.ns'=>'http://specs.openid.net/auth/2.0',
            'openid.claimed_id'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.return_to'=>FULL_URL_BASE . 'login/login?' . http_build_query(array_merge($params,
                array(
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
        
	    $url .= stripos($url, "?") ? '&' : '?';
        $url .= http_build_query($parameters);
        return $url;
    }
    
    public function getConsumerKey()
    {
        return $this->consumer_key;
    }

    public function getConsumerSecret()
    {
        return $this->consumer_secret;
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (!isset($args['DOMAIN']) || strlen($args['DOMAIN'])==0) {
            throw new Exception("Google Apps Domain not set");
        }

        $this->domain = $args['DOMAIN'];

        $url_parts = parse_url(FULL_URL_BASE);
        if (!preg_match("#" . $this->domain . "$#", $url_parts['host'])) {
            throw new Exception("This application must be run on a subdomain of $this->domain");
        }

        if (isset($args['OAUTH']) && $args['OAUTH']) {
            if (!isset($args['CONSUMER_KEY'], $args['CONSUMER_SECRET'])) {
                throw new Exception("Consumer Key and secret must be set when OAuth is on");
            }
            
            $this->oauth = true;
            $this->consumer_key = $args['CONSUMER_KEY'];
            $this->consumer_secret = $args['CONSUMER_SECRET'];
        }
    }
}

/**
  * @package Authentication
  */
class GoogleAppsUser extends BasicUser
{
    protected $oauth_token;
    protected $oauth_token_secret;
    
    public function getDomain()
    {
        return $this->AuthenticationAuthority->getDomain();
    }

    protected function valueKeyForTypeKey($key) {
        if (preg_match("/^openid_(.*?)_type_(.*?)$/", $key, $matches)) {
            return sprintf("openid_%s_value_%s", $matches[1], $matches[2]);
        }
        
        return null;
    }
    
    public function setOAuthToken($token, $token_secret)
    {
        $this->oauth_token = $_SESSION['google_oauth_token'] = $token;
        $this->oauth_token_secret = $_SESSION['google_oauth_token_secret'] = $token_secret;
    }
    
    public function setVars(array $array)
    {
        if (!isset($array['openid_identity'])) {
            return false;
        }
        
        $this->setUserID($array['openid_identity']);
        
        if (isset($array['oauth_token'], $array['oauth_token_secret'])) {
            $this->setOAuthToken($array['oauth_token'], $array['oauth_token_secret']);
        }
        
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
        return true;
    }
}
