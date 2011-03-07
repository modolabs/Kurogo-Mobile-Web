<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAuthentication extends AuthenticationAuthority
{
    // auth is handled by openid
    protected function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    //does not support groups
    public function getGroup($group)
    {
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
        $user = new GoogleUser($this);
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
        $url = "https://www.google.com/accounts/o8/id" ;
        if ($xrds = file_get_contents($url)) {
            if (preg_match("#<URI>(.*?)</URI>#", $xrds, $matches)) {
                return $matches[1];
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

    public function login($login, $pass, Module $module, $options)
    {
        $startOver = isset($_GET['startOver']) ? $_GET['startOver'] : false;
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
            $url = $this->getAuthURL();
            header("Location: " . $url);
            exit();
        }
    }
    
    protected function getAuthURL()
    {
        if (!$url = $this->getOpenIDEndpoint()) {
            throw new Exception("Unable to get Google OpenID endpoint.");
        }
        
        $url_parts = parse_url(FULL_URL_BASE);

        $realm = sprintf("http://%s", $url_parts['host']);
        if (!in_array($_SERVER['SERVER_PORT'], array(80,443))) {
            $realm .= ":" . $_SERVER['SERVER_PORT'];
        }
        
        $parameters = array(
            'openid.mode'=>'checkid_setup',
            'openid.ns'=>'http://specs.openid.net/auth/2.0',
            'openid.claimed_id'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity'=>'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.return_to'=>FULL_URL_BASE . 'login/login?' . http_build_query(array(
                'authority'=>$this->getAuthorityIndex()
		        )),
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
}

/**
  * @package Authentication
  */
class GoogleUser extends User
{
    protected function valueKeyForTypeKey($key) {
        if (preg_match("/^openid_(.*?)_type_(.*?)$/", $key, $matches)) {
            return sprintf("openid_%s_value_%s", $matches[1], $matches[2]);
        }
        
        return null;
    }
    
    public function setVars(array $array)
    {
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
        return true;
    }
}
