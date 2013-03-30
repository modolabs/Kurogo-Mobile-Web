<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');
abstract class KurogoOAuthProvider
{
    const TOKEN_TYPE_REQUEST='R';
    const TOKEN_TYPE_ACCESS='A';
    protected $oauthVersion='1.0';
    protected $title;
    protected $index;

    protected $consumerKey;
    protected $consumerSecret;
    protected $tokenType;
    protected $token;
    protected $tokenSecret;
    protected $cert;

    protected $requestTokenMethod = 'GET';
    protected $accessTokenMethod = 'GET';
    protected $signatureMethod = 'HMAC-SHA1';
    protected $debugMode;

    protected $verifierKey = 'oauth_verifier';
    protected $verifierErrorKey = '';
    protected $manualVerify=false;
    
    protected $retriever;
    
    abstract protected function getAuthURL(array $options);
    
    public function getToken($tokenType=self::TOKEN_TYPE_ACCESS) {
        if ($this->tokenType == $tokenType) {
            return $this->token;
        }
    }

    public function getTokenSecret($tokenType=self::TOKEN_TYPE_ACCESS) {
        if ($this->tokenType == $tokenType) {
            return $this->tokenSecret;
        }
    }
    
    public function canRetrieve() {
        return strlen($this->getToken(self::TOKEN_TYPE_ACCESS))>0;
    }

    public function canPost() {
        return strlen($this->getToken(self::TOKEN_TYPE_ACCESS))>0;
    }
    
    public function getConsumerKey() {
        return $this->consumerKey;
    }

    public function getConsumerSecret() {
        return $this->consumerSecret;
    }

    public function getSignatureMethod() {
        return $this->signatureMethod;
    }

    public function getCert() {
        return $this->cert;
    }

    public function reset() {
        $this->setToken(null, null, null);
    }
    
    public function auth($options, &$userArray) {
        if (!Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            throw new KurogoConfigurationException($this->getLocalizedString("ERROR_AUTHENTICATION_DISABLED"));
        }
        
        $startOver = isset($options['startOver']) && $options['startOver'];
        if ($startOver) {
            $this->reset();
        }
        
        if (!$this->getToken(self::TOKEN_TYPE_REQUEST)) {
            if (!$this->getRequestToken($options)) {
                Kurogo::log(LOG_WARNING, "Error getting request token", 'auth');
                return AUTH_FAILED;
            }
        }
        
        if (isset($_REQUEST[$this->verifierKey])) {
            //get an access token
            $options[$this->verifierKey] = $_REQUEST[$this->verifierKey];
            
            if ($userArray = $this->getAccessToken($options)) {
                return AUTH_OK;
            } else {
                Kurogo::log(LOG_WARNING, "Error getting access token", 'auth');
                return AUTH_FAILED;
            }
        } elseif ($this->manualVerify && !$startOver) {
            return AUTH_OAUTH_VERIFY;
        } else {
        
            //redirect to auth page
            $url = $this->getAuthURL($options);
            Kurogo::log(LOG_DEBUG, "Redirecting to AuthURL $url", 'auth');
            Kurogo::redirectToURL($url);
        }
    }

	public function setCertificate($cert) {
	    $this->cert = $cert;
	}
	
	public function setSignatureMethod($signatureMethod) {
	    if (!in_array($signatureMethod, array(
	        'HMAC-SHA1',
	        'RSA-SHA1',
	        'PLAINTEXT'
            ))) {
            throw new KurogoException ("Invalid signature method $signatureMethod");
        }
        
        $this->signatureMethod = $signatureMethod;
	}

	public function setDebugMode($debugMode) {
	    $this->debugMode = $debugMode ? true : false;
	}
	
	public function saveTokenForUser(User $user) {
	    if ($this->tokenType == self::TOKEN_TYPE_ACCESS) {
	        $user->setUserData($this->tokenSessionVar(), array(
	            $this->tokenType, $this->token, $this->tokenSecret
            ));
	    }
	}

	public function setTokenFromUser(User $user) {
	    if ($tokenData = $user->getUserData($this->tokenSessionVar())) {
            list($type, $token, $tokenSecret) = $tokenData;
            $this->setToken($type, $token, $tokenSecret);
	    }
	}

    public function setToken($type, $token, $tokenSecret='') {
        if ($type && !in_array($type, array(self::TOKEN_TYPE_REQUEST, self::TOKEN_TYPE_ACCESS))) {
            throw new KurogoException("Invalid token type $type");
        }
        
        $this->tokenType = $type;
        $this->token = $token;
        $this->tokenSecret = $tokenSecret;
        Kurogo::log(LOG_DEBUG, "Setting $this->index $type to $token - $tokenSecret", 'auth');
        $_SESSION[$this->tokenSessionVar()] = array(
            $type, $token, $tokenSecret
        );
    }

    
    public function setIndex($index) {
        $this->index = (string) $index;
    }

    public function setTitle($title) {
        $this->title = (string) $title;
    }

    public static function factory($providerClass, $args) {
        if (!class_exists($providerClass) || !is_subclass_of($providerClass, 'KurogoOAuthProvider')) {
            throw new KurogoConfigurationException("Invalid OAuthProvider class $providerClass");
        }
        
        $provider = new $providerClass;
        $provider->init($args);
        return $provider;
    }
    
    protected function tokenSessionVar() {
        return sprintf("%s_token", $this->index);
    }

    protected function tokenSecretSessionVar() {
        return sprintf("%s_tokenSecret", $this->index);
    }

	public function getVerifierKey() {
	    return $this->verifierKey;
	}

	protected function getRequestTokenParameters($options) {
        $return_url = isset($options['return_url']) ? $options['return_url'] : '';
        return array(
		    'oauth_callback'=>$return_url
        );
	}
	
    public function oauthRequest($method, $url, $parameters = null, $headers = null, $cacheLifetime = null) {
        $retriever = $this->getRetriever();
        $retriever->setToken($this->token);
        $retriever->setTokenSecret($this->tokenSecret);
        $retriever->setMethod($method);        
        $retriever->setBaseURL($url);
        $retriever->setParameters($parameters);
        $retriever->setHeaders($headers);
        $retriever->setCacheLifetime($cacheLifetime);
        return $retriever->getResponse();
	}
	
	protected function getRetriever() {
	    if (!$this->retriever) {
	        $this->retriever = DataRetriever::factory('OAuthDataRetriever', array(
	            'OAUTH_CONSUMER_KEY'    => $this->consumerKey,
	            'OAUTH_CONSUMER_SECRET' => $this->consumerSecret,
	            'signatureMethod'=> $this->signatureMethod,
	            'cert'           => $this->cert
	        ));
	    }
	    
	    return $this->retriever;
	}
	
    protected function getRequestToken(array $options) {
        list($method, $url, $parameters) = $this->getRequestTokenURL($options);
        $response = $this->oauthRequest($method, $url, $parameters, null, 0);
		parse_str($response->getResponse(), $return);

		// validate
		if(!isset($return['oauth_token'], $return['oauth_token_secret'])) {
            Kurogo::log(LOG_WARNING, 'oauth_token not found in getRequestToken', 'auth');
		    return false;
		}
		
		$this->setToken(self::TOKEN_TYPE_REQUEST, $return['oauth_token'], $return['oauth_token_secret']);
        return true;
    }
    
    protected function getRequestTokenURL($options) {
        return array(
            $this->requestTokenMethod,
            $this->requestTokenURL,
            $this->getRequestTokenParameters($options)
        );
    }

    protected function getAccessTokenURL($options) {
        return array(
            $this->accessTokenMethod,
            $this->accessTokenURL,
            $this->getAccessTokenParameters($options)
        );
    }
    
	protected function getAccessTokenParameters($options) {
	    $parameters = array();
        if (isset($options[$this->verifierKey]) && strlen($options[$this->verifierKey])) {
            $parameters[$this->verifierKey]=$options[$this->verifierKey];
        }
        
	    return $parameters;
	}
	
	protected function getAccessToken($options) {

        list($method, $url, $parameters) = $this->getAccessTokenURL($options);
		$response = $this->oauthRequest($method, $url,  $parameters, null, 0);
		parse_str($response->getResponse(), $return);

		if (!isset($return['oauth_token'], $return['oauth_token_secret'])) {
            Kurogo::log(LOG_WARNING, 'oauth_token not found in getAccessToken', 'auth');
		    return false;
		}
		
		$this->setToken(self::TOKEN_TYPE_ACCESS, $return['oauth_token'], $return['oauth_token_secret']);
		return $return;
	}
	
	protected function getTokenSessionData() {
	    if (isset($_SESSION[$this->tokenSessionVar()])) {
	        $tokenSessionData = $_SESSION[$this->tokenSessionVar()];
	        return $tokenSessionData;
	    }
	}
	
	public function getOAuthVersion() {
	    return $this->oauthVersion;
	}

    protected function init($args) {
    
        $args = is_array($args) ? $args : array();
        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        } else {
            $this->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        }

        if (!isset($args['TITLE']) || empty($args['TITLE'])) {
            throw new KurogoConfigurationException("Invalid OAuth provider title");
        }

        $this->setTitle($args['TITLE']);
        
        if (!isset($args['INDEX']) || empty($args['INDEX'])) {
            throw new KurogoConfigurationException("Invalid OAuth provider index");
        }

        $this->setIndex($args['INDEX']);

        if (isset($args['OAUTH_CONSUMER_KEY'], $args['OAUTH_CONSUMER_SECRET'])) {
            $this->consumerKey = $args['OAUTH_CONSUMER_KEY'];
            $this->consumerSecret = $args['OAUTH_CONSUMER_SECRET'];
        }
        
        if ($tokenSessionData = $this->getTokenSessionData()) {
            list($type, $token, $tokenSecret) = $tokenSessionData;
            $this->setToken($type, $token, $tokenSecret);
        }
        
    }
}
