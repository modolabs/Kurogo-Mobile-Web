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
    protected $tokenSecret='';
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    protected $signatureMethod = 'HMAC-SHA1';
    protected $verifierKey = 'oauth_verifier';
    protected $verifierErrorKey = '';
    protected $manualVerify=false;
    private $oauth;
    
    abstract protected function getAuthURL(array $params);
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
	
	public function oauthRequest($method, $url, $parameters = null, $headers = null, $use_token=true) {
        $parameters = is_array($parameters) ? $parameters : array();
	    if (!$this->oauth) {
	        $this->oauth = new OAuthRequest($this->consumer_key, $this->consumer_secret);
	        $this->oauth->setSignatureMethod($this->signatureMethod);
	    }
	    
	    if ($use_token) {
            $this->oauth->setToken($this->token);
            $this->oauth->setTokenSecret($this->tokenSecret);
        } else {
            $this->oauth->setToken('');
            $this->oauth->setTokenSecret('');
        }

	    $this->oauth->setDebugMode($this->debugMode);
	    return $this->oauth->request($method, $url, $parameters, $headers);
	}
	
	protected function getAccessToken($verifier='') {
		$parameters = $this->getAccessTokenParameters();
        
        if (strlen($verifier)) {
            $parameters[$this->verifierKey]=$verifier;
        }
        
		// make the call
		$response = $this->oauthRequest($this->accessTokenMethod, $this->accessTokenURL,  $parameters);
		parse_str($response, $return);

		if (!isset($return['oauth_token'], $return['oauth_token_secret'])) {
		    error_log('oauth_token not found in getAccessToken');
		    return false;
		}

		// set some properties
		$this->setToken($return['oauth_token']);
		$this->setTokenSecret($return['oauth_token_secret']);
		
		// return
		return $return;
	}
	
	public function getVerifierKey() {
	    return $this->verifierKey;
	}

	protected function getRequestTokenParameters() {
	    return array();
	}

    protected function getRequestToken(array $params) {
        $this->reset();
        //get a request token 
        // at this time it uses the login module, that may need to be more flexible
		$parameters = array_merge($this->getRequestTokenParameters(), array(
		    'oauth_callback'=>FULL_URL_BASE . 'login/login?' . http_build_query(array_merge($params, array(
		        'authority'=>$this->getAuthorityIndex()
		        )))
        ));

        $response = $this->oauthRequest($this->requestTokenMethod, $this->requestTokenURL, $parameters);
		parse_str($response, $return);

		// validate
		if(!isset($return['oauth_token'], $return['oauth_token_secret'])) {
		    error_log('oauth_token not found in getRequestToken');
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

    public function login($login, $pass, Session $session, $options) {
        $startOver = isset($_REQUEST['startOver']) ? $_REQUEST['startOver'] : false;
        //see if we already have a request token
        if ($startOver || !$this->token || !$this->tokenSecret) {
            $this->setToken(null);
            $this->setTokenSecret(null);
            if (!$this->getRequestToken($options)) {
                error_log("Error getting request token");
                return AUTH_FAILED;
            }
        }
        
        //if oauth_verifier is set then we are in the callback
        if (isset($_REQUEST[$this->verifierKey])) {
            //get an access token
            if ($response = $this->getAccessToken($_REQUEST[$this->verifierKey])) {
            
                //we should now have the current user
                if ($user = $this->getUserFromArray($response)) {
                    $session->login($user);
                    return AUTH_OK;
                } else {
                    error_log("Unable to find user for $response");
                    return AUTH_FAILED;
                }
            } else {
                error_log("Error getting Access token");
                return AUTH_FAILED;
            }
        } elseif (!$startOver && $this->token && $this->manualVerify) {
            return AUTH_OAUTH_VERIFY;
        } else {
        
            //redirect to auth page
            $url = $this->getAuthURL($options);
            header("Location: " . $url);
            exit();
        }
    }

    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        $this->tokenSessionVar = sprintf("%s_token", $this->getAuthorityIndex());
        $this->tokenSecretSessionVar = sprintf("%s_tokenSecret", $this->getAuthorityIndex());

        if (isset($_SESSION[$this->tokenSessionVar], $_SESSION[$this->tokenSecretSessionVar])) {
            $this->setToken($_SESSION[$this->tokenSessionVar]);
            $this->setTokenSecret($_SESSION[$this->tokenSecretSessionVar]);
        }
    }

    protected function reset($hard=false) {
        $this->setToken(null);
        $this->setTokenSecret(null);
        unset($_SESSION[$this->tokenSessionVar]);
        unset($_SESSION[$this->tokenSecretSessionVar]);
    }

    public function getToken() {
        return $this->token;
    }
    
    public function getTokenSecret() {
        return $this->tokenSecret;
    }

    public function setToken($token) {
        $this->token = $token;
        $_SESSION[$this->tokenSessionVar] = $token;
    }

    public function setTokenSecret($tokenSecret) {
        $this->tokenSecret = $tokenSecret;
        $_SESSION[$this->tokenSecretSessionVar] = $tokenSecret;
    }
    
    public function getSessionData(OAuthUser $user) {
        return array(
            $this->tokenSessionVar=>$this->token,
            $this->tokenSecretSessionVar=>$this->tokenSecret
        );
    }

    public function setSessionData($data) {
        if (isset($data[$this->tokenSessionVar])) {
            $this->setToken($data[$this->tokenSessionVar]);
        }

        if (isset($data[$this->tokenSecretSessionVar])) {
            $this->setTokenSecret($data[$this->tokenSecretSessionVar]);
        }
    }        
}

class OAuthUser extends User
{
    protected $token;
    protected $tokenSecret;
    
    public function setToken($token) {
        $this->token = $token;
    }

    public function setTokenSecret($tokenSecret) {
        $this->tokenSecret = $tokenSecret;
    }

    public function getSessionData() {
        return $this->AuthenticationAuthority->getSessionData($this);   
    }

    public function setSessionData($data) {
        $this->AuthenticationAuthority->setSessionData($data);
    }
    
}