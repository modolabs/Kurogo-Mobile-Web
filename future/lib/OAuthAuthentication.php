<?php

if (!function_exists('curl_init')) {
    throw new Exception("cURL library not available");
}


abstract class OAuthAuthentication extends AuthenticationAuthority
{
    protected $tokenSessionVar;
    protected $tokenSecretSessionVar;
    protected $requestTokenURL;
    protected $requestTokenMethod='POST';
    protected $accessTokenURL;
    protected $accessTokenMethod='POST';
    protected $curl;
    protected $consumer_key;
    protected $consumer_secret;
    protected $token;
    protected $token_secret;
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    protected $verifierKey = 'oauth_verifier';
    protected $verifierErrorKey = '';
    
    abstract protected function getAuthURL();
    abstract protected function getUserFromArray(array $array);
		
    // auth is handled by oauth
    protected function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    //does not support groups
    public function getGroup($group)
    {
        return false;
    }

	protected function getAccessTokenParameters()
	{
	    return array();
	}
	
	protected function getAccessToken($token, $verifier)
	{
		$parameters = array_merge($this->getAccessTokenParameters(),array(
		    'oauth_token'=>$token,
		    'oauth_verifier'=>$verifier
		));

		// make the call
		$response = $this->doOAuthCall($this->accessTokenURL, $this->accessTokenMethod, $parameters);
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

	protected function getRequestTokenParameters()
	{
	    return array();
	}

    protected function getRequestToken()
    {
        $this->reset();
        //get a request token 
        // at this time it uses the login module, that may need to be more flexible
		$parameters = array_merge($this->getRequestTokenParameters(), array(
		    'oauth_callback'=>FULL_URL_BASE . 'login/login?' . http_build_query(array(
		        'authority'=>$this->getAuthorityIndex()
		        ))
        ));
        $response = $this->doOAuthCall($this->requestTokenURL, $this->requestTokenMethod, $parameters);
		parse_str($response, $return);

		// validate
		if(!isset($return['oauth_token'], $return['oauth_token_secret'])) {
		    return false;
		}
		
		$this->setToken($return['oauth_token']);
        $this->setTokenSecret($return['oauth_token_secret']);
        return true;
    }

	protected function buildQuery(array $parameters)
	{
		// no parameters?
		if(empty($parameters)) return '';

		// encode the keys
		$keys = self::urlencode_rfc3986(array_keys($parameters));

		// encode the values
		$values = self::urlencode_rfc3986(array_values($parameters));

		// reset the parameters
		$parameters = array_combine($keys, $values);

		// sort parameters by key
		uksort($parameters, 'strcmp');

		// loop parameters
		foreach($parameters as $key => $value)
		{
			// sort by value
			if(is_array($value)) $parameters[$key] = natsort($value);
		}

		// process parameters
		foreach($parameters as $key => $value) $chunks[] = $key .'='. str_replace('%25', '%', $value);

		// return
		return implode('&', $chunks);
	}

	protected function calculateHeader(array $parameters, $url)
	{
		// redefine
		$url = (string) $url;

		// divide into parts
		$parts = parse_url($url);

		// init var
		$chunks = array();

		// process queries
		foreach($parameters as $key => $value) $chunks[] = str_replace('%25', '%', self::urlencode_rfc3986($key) .'="'. self::urlencode_rfc3986($value) .'"');

		// build return
		$return = 'Authorization: OAuth realm="' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '", ';
		$return .= implode(',', $chunks);

		// prepend name and OAuth part
		return $return;
	}

	protected function calculateBaseString($url, $method, array $parameters)
	{
		// redefine
		$url = (string) $url;
		$parameters = (array) $parameters;

		// init var
		$pairs = array();
		$chunks = array();

		// sort parameters by key
		uksort($parameters, 'strcmp');

		// loop parameters
		foreach($parameters as $key => $value)
		{
			// sort by value
			if(is_array($value)) $parameters[$key] = natsort($value);
		}

		// process queries
		foreach($parameters as $key => $value)
		{
			$chunks[] = self::urlencode_rfc3986($key) .'%3D'. self::urlencode_rfc3986($value);
		}

		// buils base
		$base = $method .'&';
		$base .= urlencode($url) .'&';
		$base .= implode('%26', $chunks);

		// return
		return $base;
	}

	protected static function urlencode_rfc3986($value)
	{
		if(is_array($value)) return array_map(array(__CLASS__, 'urlencode_rfc3986'), $value);
		else
		{
			$search = array('+', ' ', '%7E', '%');
			$replace = array('%20', '%20', '~', '%25');

			return str_replace($search, $replace, urlencode($value));
		}
	}

	protected function hmacsha1($key, $data)
	{
		return base64_encode(hash_hmac('SHA1', $data, $key, true));
	}
	
	protected function oauthSignature($url, $method, $parameters)
	{
		// calculate the base string
		$base = $this->calculateBaseString($url, $method, $parameters);
		$sig = $this->hmacsha1($this->consumer_secret .'&' . $this->token_secret, $base);
		return $sig;
	}
	
	protected function doOAuthCall($url, $method, $parameters = null)
	{		
		$parameters = (array) $parameters;
		$options = array();
		$headers = array();

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->consumer_key;
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_signature_method'] = 'HMAC-SHA1';
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_version'] = '1.0';

        switch ($method)
        {
            case 'POST':
                $parameters = array_merge($parameters, $oauth);
        		$parameters['oauth_signature'] = $this->oauthSignature($url, $method, $parameters);
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);

                break;
            case 'GET':
                $data = $oauth;
                if(!empty($parameters)) {
                    $data = array_merge($data, $parameters);
                    $url .= '?'. $this->buildQuery($parameters);
                }
        		$oauth['oauth_signature'] = $this->oauthSignature($url, $method, $parameters);
                $headers[] = $this->calculateHeader($oauth, $url);
                break;
            default:
                throw new Exception("Invalid method $method");
                break;
        }            

        $headers[] = 'Expect:';

		// set options
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HTTPHEADER] = $headers;

		// init
		$this->curl = curl_init();
		
		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		return $response;
	}
	
    public function login($login, $pass, Module $module)
    {
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

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (isset($_SESSION[$this->tokenSessionVar], $_SESSION[$this->tokenSecretSessionVar])) {
            $this->setToken($_SESSION[$this->tokenSessionVar]);
            $this->setTokenSecret($_SESSION[$this->tokenSecretSessionVar]);
        }
    }

    protected function reset()
    {
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
