<?php

abstract class OAuthProvider
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

    public function reset() {
        $this->setToken(null, null, null);
    }
    
    public function auth($options, &$userArray) {
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
            header("Location: " . $url);
            exit();
        }
    }
    
	protected function buildQuery(array $parameters) {

		if(empty($parameters)) return '';

		// encode the keys
		$keys = self::urlencode(array_keys($parameters));

		// encode the values
		$values = self::urlencode(array_values($parameters));

		// combine the key/value array
		$parameters = array_combine($keys, $values);

		// sort parameters as required by oauth
		uksort($parameters, 'strcmp');

		$params = array();
		foreach($parameters as $key => $value) {
			// sort by value
			if (is_array($value)) {
			    $value = natsort($value);
			}
		    $params[] = $key .'='. str_replace('%25', '%', $value);
		}
		
		// return
		return implode('&', $params);
	}

	protected function calculateHeader($url, $parameters) {

		// init var
		$params = array();

		// encode each parameter
		foreach($parameters as $key => $value) {
		    $params[] = self::urlencode($key) .'="'. self::urlencode($value) .'"';
		}

		// build return
		$return = 'OAuth ' . implode(',', $params);

		return $return;
	}

    /* Builds the base string according to 3.4.1 of RFC 5849 */
	protected function calculateBaseString($method, $url, $parameters) {

		$parameters = is_array($parameters) ? $parameters : array();

		// init var
		$pairs = array();
		$params = array();

		// sort parameters by key
		uksort($parameters, 'strcmp');

		foreach($parameters as $key => $value) {
			// sort by value
			if(is_array($value)) { 
			    $value = natsort($value);
            }

			$params[] = self::urlencode($key) .'='. self::urlencode($value);
		}
		
		// builds base
		$parts = array(
		    strtoupper($method),
		    $url,
		    implode('&', $params)
        );
        
        $parts = self::urlencode($parts);
        $base = implode('&', $parts);
        return $base;
	}

    /* Encodes urls. This attempts to conform to 3.6 of RFC 5849 
       If there is a problem with an OAuth provider, likely it's going to be here 
    */
	protected static function urlencode($value) {
		if (is_array($value)) {
		    return array_map(array(__CLASS__, 'urlencode'), $value);
		}

        return str_replace('+',' ', str_replace('%7E', '~', rawurlencode($value)));
	}

    /* sign the request according to 3.1 of RFC 5849 */
	protected function oauthSignature($method, $url, $parameters) {
		// calculate the base string
		$baseString = $this->calculateBaseString($method, $url, $parameters);
		$key = self::urlencode($this->consumerSecret) .'&' . self::urlencode($this->tokenSecret);
		
		switch ($this->signatureMethod)
		{
		    case 'PLAINTEXT':
		        $sig = $key;
		        break;
		    case 'HMAC-SHA1':
        		$sig = base64_encode(hash_hmac('SHA1', $baseString, $key, true));
        		break;
        	case 'RSA-SHA1':

                if (!$privatekeyid = openssl_get_privatekey($this->cert)) {
                    throw new KurogoException("Error getting private key for $this->cert");
                }

                // Sign using the key
                $ok = openssl_sign($this->base_string, $signature, $privatekeyid);

                // Release the key resource
                openssl_free_key($privatekeyid);

                $sig = base64_encode($signature);
        	    break;
        	default:
        	    throw new KurogoException("Signature method $this->signatureMethod not handled");
		}
		
		return $sig;
	}
	
	protected function buildURL($parts) {
        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? $parts['host'] : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';
    
        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
          $host = "$host:$port";
        }
        return "$scheme://$host$path";
	}
	
	protected function baseURL($url) {
        $parts = parse_url($url);
        return $this->buildURL($parts);
	}
	
	protected function parseQueryString($queryString) {
	    $return = array();
	    $vars = explode('&', $queryString);
	    foreach ($vars as $value) {
	        $bits = explode("=", $value);
	        $return[$bits[0]] = urldecode($bits[1]);
	    }
	    return $return;
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

    public function getAuthorizationHeader($method, &$url, &$parameters = null, &$headers = null) {
		$params = (array) $parameters;
		$options = array();
		$headers = (array) $headers;

        /* strip out query string and add it to parameters */
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            $params = array_merge($params, $this->parseQueryString($urlParts['query']));
        }

        $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->consumerKey;
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_signature_method'] = $this->signatureMethod;
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_version'] = $this->oauthVersion;
		
		if ($this->token) {
		    $oauth['oauth_token'] = $this->token;
		}
		
	    foreach ($params as $param=>$value) {
	        if (preg_match("/^oauth_/", $param)) {
	            $oauth[$param] = $value;
	            unset($params[$param]);
	        }
	    }
		
        switch ($method) {
            case 'POST':
                $params = array_merge($params, $oauth);
                $url = $this->baseURL($url);
        		$params['oauth_signature'] = $this->oauthSignature($method, $url, $params);
                $authHeader =  $this->calculateHeader($url, $params);
                break;
                
            case 'GET':
                $data = $oauth;
                $base_url = $url = $this->baseURL($url);
                if(count($params)>0) {
                    $data = array_merge($data, $params);
                    $url .= '?'. $this->buildQuery($params);
                }

        		$oauth['oauth_signature'] = $this->oauthSignature($method, $base_url, $data);
                $authHeader = $this->calculateHeader($url, $oauth);
                break;
            default:
                throw new KurogoException("Invalid method $method");
                break;
        }        
        
        return $authHeader;
    }
    
    public function setIndex($index) {
        $this->index = (string) $index;
    }

    public function setTitle($title) {
        $this->title = (string) $title;
    }

    public static function factory($providerClass, $args) {
        if (!class_exists($providerClass) || !is_subclass_of($providerClass, 'OAuthProvider')) {
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
	
    public function oauthRequest($method, $url, $parameters = null, $headers = null) {
        $contextOpts = array(
            'http'=>array(
                'method'=>$method,
                'follow_location'=>false,
                'max_redirects'=>0
            )
        );
        
        $requestParameters = $parameters;
        $requestHeaders = $headers;
        
        switch ($method) 
        {
            case 'GET':
                if (count($parameters)>0) {
                    $glue = strpos($url, '?') !== false ? '&' : '?';
                    $url .= $glue . http_build_query($parameters);
                    $requestParameters = array();
                }
                break;
                
            case 'POST':
                $requestHeaders[] = "Content-type: application/x-www-form-urlencoded";
                $contextOpts['http']['content'] = '';
                break;
                
            default:
                throw new KurogoException("Invalid method $method");
        }

	    $requestHeaders[] = 'Authorization: ' . $this->getAuthorizationHeader($method, $url, $requestParameters, $requestHeaders);
	    $requestHeaders[] = 'Expect:';
        $contextOpts['http']['header'] = implode("\r\n", $requestHeaders) . "\r\n";
        
        $streamContext = stream_context_create($contextOpts);
        Kurogo::log(LOG_INFO, sprintf("Making %s request to %s. Using %s %s %s %s", $method, $url, $this->consumerKey, $this->consumerSecret, $this->token, $this->tokenSecret), 'auth');

        $response = file_get_contents($url, false, $streamContext);
        
        //parse the response
        $this->response = new DataResponse();
        $this->response->setRequest($method, $url, $parameters, $headers);
        $this->response->setResponse($response, $http_response_header);
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $this->response->getCode(), strlen($response)), 'auth');

        //if there is a location header we need to re-sign before redirecting
        if ($redirectURL = $this->response->getHeader("Location")) {
            Kurogo::log(LOG_DEBUG, "Found Location Header", 'auth');
		    $redirectParts = parse_url($redirectURL);
		    //if the redirect does not include the host or scheme, use the scheme/host from the original URL
            if (!isset($redirectParts['scheme']) || !isset($redirectParts['host'])) {
                $urlParts = parse_url($url);
                unset($urlParts['path']);
                unset($urlParts['query']);
                $redirectURL = $this->buildURL($urlParts) . $redirectURL;
            }
		    if (isset($redirectParts['query'])) {
		        $newParameters = array_merge($parameters, $this->parseQueryString($redirectParts['query']));
		    }
		    $newURL = $this->baseURL($redirectURL);
		    //error_log("Redirecting to $newURL");
            Kurogo::log(LOG_DEBUG, "Redirecting to $newURL", 'auth');
    		return $this->oauthRequest($method, $newURL, $newParameters, $headers);
        }
        
        return $response;
	}
	
	public function getResponse() {
	    return $this->response;
	}

    protected function getRequestToken(array $options) {
    
        list($method, $url, $parameters) = $this->getRequestTokenURL($options);
        $response = $this->oauthRequest($method, $url, $parameters);
		parse_str($response, $return);

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
		$response = $this->oauthRequest($method, $url,  $parameters);
		parse_str($response, $return);

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
