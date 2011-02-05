<?php

if (!function_exists('curl_init')) {
    throw new Exception("cURL library not available");
}

if (!function_exists('hash_hmac')) {
    throw new Exception("hash_hmac function not available");
}


class OAuthRequest
{
    protected $curl;
    protected $consumerKey;
    protected $consumerSecret;

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

	protected function calculateHeader(array $parameters, $url) {

		// divide into parts
		$parts = parse_url($url);

		// init var
		$params = array();

		// encode each parameter
		foreach($parameters as $key => $value) {
		    $params[] = str_replace('%25', '%', self::urlencode($key) .'="'. self::urlencode($value) .'"');
		}

		// build return
		$return = 'Authorization: OAuth ' . implode(',', $params);

		return $return;
	}

    /* Builds the base string according to 3.4.1 of RFC 5849 */
	protected function calculateBaseString($url, $method, array $parameters) {

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

			$params[] = self::urlencode($key) .'%3D'. self::urlencode($value);
		}

		// builds base
		$base = strtoupper($method) .'&';
		$base .= urlencode($url) .'&';
		$base .= implode('%26', $params);

		// return
		return $base;
	}

    /* Encodes urls. This attempts to conform to 3.6 of RFC 5849 
       If there is a problem with an OAuth provider, likely it's going to be here 
    */
	protected static function urlencode($value) {
		if (is_array($value)) {
		    return array_map(array(__CLASS__, 'urlencode'), $value);
		}

        $search = array('+', ' ', '%7E', '%');
        $replace = array('%20', '%20', '~', '%25');

        return str_replace($search, $replace, rawurlencode($value));
	}

    /* sign the request according to 3.1 of RFC 5849 */
	protected function oauthSignature($url, $token_secret, $method, $parameters) {
		// calculate the base string
		$baseString = $this->calculateBaseString($url, $method, $parameters);
		$key = rawurlencode($this->consumerSecret) .'&' . rawurlencode($token_secret);
		$sig = base64_encode(hash_hmac('SHA1', $baseString, $key, true));
		return $sig;
	}

    /* public method to make an OAuth Request */
	public function request($url, $method, $parameters = null, $token_secret='') {		
		$parameters = (array) $parameters;
		$options = array();
		$headers = array();

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->consumerKey;
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_signature_method'] = 'HMAC-SHA1';
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_version'] = '1.0';

        switch ($method)
        {
            case 'POST':
                $parameters = array_merge($parameters, $oauth);
        		$parameters['oauth_signature'] = $this->oauthSignature($url, $token_secret, $method, $parameters);
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);

                break;
            case 'GET':
                $data = $oauth;
                if(!empty($parameters)) {
                    $data = array_merge($data, $parameters);
                    $url .= '?'. $this->buildQuery($parameters);
                }
        		$oauth['oauth_signature'] = $this->oauthSignature($url, $token_secret, $method, $parameters);
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
	
	public function __construct($consumerKey, $consumerSecret) {
	    $this->consumerKey = $consumerKey;
	    $this->consumerSecret = $consumerSecret;
	    
	    if (empty($this->consumerKey) || empty($this->consumerSecret)) {
	        throw new Exception("Consumer key and secret not set");
	    }
	}
}
