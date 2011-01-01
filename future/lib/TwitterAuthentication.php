<?php

if (!function_exists('curl_init')) {
    throw new Exception("cURL library not available");
}

class TwitterAuthentication extends AuthenticationAuthority
{
	const API_URL = 'https://api.twitter.com/1';
	const SECURE_API_URL = 'https://api.twitter.com';
    protected $curl;
    protected $consumer_key;
    protected $consumer_secret;
    protected $token;
    protected $token_secret;
    protected $useCache = true;
    protected $cache;
    protected $cacheLifetime = 900;
    
    // auth is handled by twitter
    public function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

	private function buildQuery(array $parameters)
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

	private function calculateHeader(array $parameters, $url)
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

	private function calculateBaseString($url, $method, array $parameters)
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

	private static function urlencode_rfc3986($value)
	{
		if(is_array($value)) return array_map(array('TwitterAuthentication', 'urlencode_rfc3986'), $value);
		else
		{
			$search = array('+', ' ', '%7E', '%');
			$replace = array('%20', '%20', '~', '%25');

			return str_replace($search, $replace, urlencode($value));
		}
	}

	private function hmacsha1($key, $data)
	{
		return base64_encode(hash_hmac('SHA1', $data, $key, true));
	}

	private function doOAuthCall($method, array $parameters = null)
	{		
		$parameters = (array) $parameters;

		// append default parameters
		$parameters['oauth_consumer_key'] = $this->consumer_key;
		$parameters['oauth_nonce'] = md5(microtime() . rand());
		$parameters['oauth_timestamp'] = time();
		$parameters['oauth_signature_method'] = 'HMAC-SHA1';
		$parameters['oauth_version'] = '1.0';

		// calculate the base string
		$base = $this->calculateBaseString(self::SECURE_API_URL .'/oauth/'. $method, 'POST', $parameters);

		// add sign into the parameters
		$parameters['oauth_signature'] = $this->hmacsha1($this->consumer_secret .'&' . $this->token_secret, $base);

		// calculate header
		$header = $this->calculateHeader($parameters, self::SECURE_API_URL .'/oauth/'. $method);

		// set options
		$options[CURLOPT_URL] = self::SECURE_API_URL .'/oauth/'. $method;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HTTPHEADER] = array('Expect:');
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);

		// init
		$this->curl = curl_init();

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);

		// parse the string
		parse_str($response, $return);

		// return
		return $return;
	}

	private function doCall($url, array $parameters = null)
	{
		$parameters = (array) $parameters;

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->consumer_key;
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_token'] = $this->token;
		$oauth['oauth_signature_method'] = 'HMAC-SHA1';
		$oauth['oauth_version'] = '1.0';

		// set data
		$data = $oauth;
		if(!empty($parameters)) {
		    $data = array_merge($data, $parameters);
            $url .= '?'. $this->buildQuery($parameters);
        }

        $options[CURLOPT_POST] = false;

		// calculate the base string
		$base = $this->calculateBaseString(self::API_URL .'/'. $url, 'GET', $data);

		// add sign into the parameters
		$oauth['oauth_signature'] = $this->hmacsha1($this->consumer_secret .'&' . $this->token_secret, $base);

		$headers[] = $this->calculateHeader($oauth, self::API_URL .'/'. $url);
		$headers[] = 'Expect:';

		// set options
		$options[CURLOPT_URL] = self::API_URL .'/'. $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$options[CURLOPT_HTTPHEADER] = $headers;

		// init
		if($this->curl == null) {
		    $this->curl = curl_init();
		}

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
        return $response;
	}

    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }

        //use the cache if available
        if ($this->useCache) {
            $cacheFilename = "user_$login";
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache(CACHE_DIR . "/Twitter", $this->cacheLifetime, TRUE);
                  $this->cache->setSuffix('.json');
                  $this->cache->preserveFormat();
            }

            if ($this->cache->isFresh($cacheFilename)) {
                $data = $this->cache->read($cacheFilename);
            } else {
                //cache isn't fresh, load the data
                if ($data = $this->doCall('users/show.json', array('screen_name'=>$login))) {
                    $this->cache->write($data, $cacheFilename);
                }
                
            }
        } else {
            //load the data
            $data = $this->doCall('users/show.json', array('screen_name'=>$login));
        }
        
		// make the call
		if ($data) {
            $json = @json_decode($data, true);

            if (isset($json['screen_name'])) {
                $user = new TwitterUser($this);
                $user->setTwitterUserID($json['id']);
                $user->setUserID($json['screen_name']);
                $user->setFullName($json['name']);
                return $user;
            }        
        }

        return false;
    }
    
    private function getRequestToken()
    {
        //get a request token    
		$parameters = array(
		    'oauth_callback'=>FULL_URL_BASE . 'login/login?' . http_build_query(array(
		        'authority'=>$this->getAuthorityIndex()
		        ))
        );
        $response = $this->doOAuthCall('request_token', $parameters);

		// validate
		if(!isset($response['oauth_token'], $response['oauth_token_secret'])) {
		    return false;
		}
		
		$this->setToken($response['oauth_token']);
        $this->setTokenSecret($response['oauth_token_secret']);
        return true;
    }

	public function getAccessToken($token, $verifier)
	{
		$parameters = array(
		    'oauth_token'=>$token,
		    'oauth_verifier'=>$verifier
		);

		// make the call
		$response = $this->doOAuthCall('access_token', $parameters);

		if(!isset($response['oauth_token'], $response['oauth_token_secret'])) {
		    return false;
		}

		// set some properties
		$this->setToken($response['oauth_token']);
		$this->setTokenSecret($response['oauth_token_secret']);
		
		// return
		return $response;
	}
    
    public function login($login, $pass, Module $module)
    {
        //see if we already have a request token
        if (!$this->token || !$this->token_secret) {
            $this->getRequestToken();
        }
        
        //if oauth_verifier is set then we are in the callback
        if (isset($_GET['oauth_verifier'])) {
            //get an access token
            if ($response = $this->getAccessToken($this->token, $_GET['oauth_verifier'])) {
                
                //we should now have the current user
                if ($user = $this->getUser($response['screen_name'])) {
                    $session = $module->getSession();
                    $session->login($user);
                    return AUTH_OK;
                }
            } else {
                return AUTH_FAIL;
            }
        } else {
        
            //redirect to auth page
            $url = sprintf("%s/oauth/authenticate?oauth_token=%s", self::SECURE_API_URL, $this->token);
            header("Location: " . $url);
            exit();
        }
    }
    
    public function setToken($token) {
        $this->token = $token;
        $_SESSION['twitter_token'] = $token;
    }

    public function setTokenSecret($token_secret) {
        $this->token_secret = $token_secret;
        $_SESSION['twitter_token_secret'] = $token_secret;
    }
    
    protected function reset()
    {
        unset($_SESSION['twitter_token']);
        unset($_SESSION['twitter_token_secret']);
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
        if (!isset($args['CONSUMER_KEY'], $args['CONSUMER_SECRET'])) {
            throw new Exception("Consumer key and secret not set");
        }

        if (!isset($args['OAUTH']) || !$args['OAUTH']) {
            throw new Exception("Twitter authentication must have OAUTH option set");
        }

        $this->consumer_key = $args['CONSUMER_KEY'];
        $this->consumer_secret = $args['CONSUMER_SECRET'];
        
        if (isset($_SESSION['twitter_token'], $_SESSION['twitter_token_secret'])) {
            $this->setToken($_SESSION['twitter_token']);
            $this->setTokenSecret($_SESSION['twitter_token_secret']);
        }
    }
}

class TwitterUser extends BasicUser
{
    protected $twitter_userID;
    
    public function setTwitterUserID($userID)
    {
        $this->twitter_userID = $userID;
    }

    public function getTwitterUserID()
    {
        return $this->twitter_userID;
    }

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('twitter_userID'));
    }
    
}
