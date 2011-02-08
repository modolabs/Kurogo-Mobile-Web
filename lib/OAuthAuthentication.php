<?php

abstract class OAuthAuthentication extends AuthenticationAuthority
{
    protected $tokenSessionVar;
    protected $tokenSecretSessionVar;
    protected $requestTokenURL;
    protected $requestTokenMethod='POST';
    protected $accessTokenURL;
    protected $accessTokenMethod='POST';
    protected $consumer_key;
    protected $consumer_secret;
    protected $token;
    protected $token_secret;
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    protected $verifierKey = 'oauth_verifier';
    protected $verifierErrorKey = '';
    private $oauth;
    
    abstract protected function getAuthURL();
    abstract protected function getUserFromArray(array $array);

    protected function validUserLogins() { 
        return array('LINK', 'NONE');
    }
		
    // auth is handled by oauth
    protected function auth($login, $password, &$user) {
        return AUTH_FAILED;
    }

    //does not support groups
    public function getGroup($group) {
        return false;
    }

	protected function getAccessTokenParameters() {
	    return array();
	}
	
	protected function oauthRequest($url, $method, $parameters = null) {
	    if (!$this->oauth) {
	        $this->oauth = new OAuthRequest($this->consumer_key, $this->consumer_secret);
	    }
	    
	    return $this->oauth->request($url, $method, $parameters, $this->token_secret);
	}
	
	protected function getAccessToken($token, $verifier='') {
		$parameters = array_merge($this->getAccessTokenParameters(),array(
		    'oauth_token'=>$token
        ));
        
        if (strlen($verifier)) {
            $parameters['oauth_verifier']=$verifier;
        }
        
		// make the call
		$response = $this->oauthRequest($this->accessTokenURL, $this->accessTokenMethod, $parameters);
		parse_str($response, $return);

		if(!isset($return['oauth_token'], $return['oauth_token_secret'])) {
		    return false;
		}

		// set some properties
		$this->setToken($return['oauth_token']);
		$this->setTokenSecret($return['oauth_token_secret']);
		
		// return
		return $return;
	}

	protected function getRequestTokenParameters() {
	    return array();
	}

    protected function getRequestToken() {
        $this->reset();
        //get a request token 
        // at this time it uses the login module, that may need to be more flexible
		$parameters = array_merge($this->getRequestTokenParameters(), array(
		    'oauth_callback'=>FULL_URL_BASE . 'login/login?' . http_build_query(array(
		        'authority'=>$this->getAuthorityIndex()
		        ))
        ));
        $response = $this->oauthRequest($this->requestTokenURL, $this->requestTokenMethod, $parameters);
		parse_str($response, $return);

		// validate
		if(!isset($return['oauth_token'], $return['oauth_token_secret'])) {
		    return false;
		}
		
		$this->setToken($return['oauth_token']);
        $this->setTokenSecret($return['oauth_token_secret']);
        return true;
    }
    
    public function getConsumerKey()
    {
        return $this->consumer_key;
    }

    public function getConsumerSecret()
    {
        return $this->consumer_secret;
    }

    public function login($login, $pass, Module $module) {
        $startOver = isset($_GET['startOver']) ? $_GET['startOver'] : false;
        //see if we already have a request token
        if ($startOver || !$this->token || !$this->token_secret) {
            if (!$this->getRequestToken()) {
                return AUTH_FAILED;
            }
        }
        
        //if oauth_verifier is set then we are in the callback
        if (isset($_GET[$this->verifierKey])) {
            //get an access token
            if ($response = $this->getAccessToken($this->token, $_GET[$this->verifierKey])) {
                //we should now have the current user
                if ($user = $this->getUserFromArray($response)) {
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

    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (isset($_SESSION[$this->tokenSessionVar], $_SESSION[$this->tokenSecretSessionVar])) {
            $this->setToken($_SESSION[$this->tokenSessionVar]);
            $this->setTokenSecret($_SESSION[$this->tokenSecretSessionVar]);
        }
    }

    protected function reset() {
        $this->setToken(null);
        $this->setTokenSecret(null);
        unset($_SESSION[$this->tokenSessionVar]);
        unset($_SESSION[$this->tokenSecretSessionVar]);
    }

    public function setToken($token) {
        $this->token = $token;
        $_SESSION[$this->tokenSessionVar] = $token;
    }

    public function setTokenSecret($token_secret) {
        $this->token_secret = $token_secret;
        $_SESSION[$this->tokenSecretSessionVar] = $token_secret;
    }
}
