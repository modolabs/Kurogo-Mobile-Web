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
    protected $token;
    protected $tokenSecret;
    protected $cert;
    protected $returnHeaders = array();
    protected $signatureMethod = 'HMAC-SHA1';
    protected $baseString='';

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
		$return = 'Authorization: OAuth ' . implode(',', $params);

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
		$this->baseString = $this->calculateBaseString($method, $url, $parameters);
		$key = self::urlencode($this->consumerSecret) .'&' . self::urlencode($this->tokenSecret);
		
		switch ($this->signatureMethod)
		{
		    case 'HMAC-SHA1':
        		$sig = base64_encode(hash_hmac('SHA1', $this->baseString, $key, true));
        		break;
        	case 'RSA-SHA1':

                if (!$privatekeyid = openssl_get_privatekey($this->cert)) {
                    throw new Exception("Error getting private key for $this->cert");
                }

                // Sign using the key
                $ok = openssl_sign($this->base_string, $signature, $privatekeyid);

                // Release the key resource
                openssl_free_key($privatekeyid);

                $sig = base64_encode($signature);
        	    break;
        	default:
        	    throw new Exception("Signature method $this->signatureMethod not handled");
		}
		
		return $sig;
	}
	
	protected function baseURL($url) {
        $parts = parse_url($url);
    
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
	        'RSA-SHA1'
            ))) {
            throw new Exception ("Invalid signature method $signatureMethod");
        }
        
        $this->signatureMethod = $signatureMethod;
	}

	public function setToken($token) {
	    $this->token = $token;
	}
	
	public function setTokenSecret($tokenSecret) {
	    $this->tokenSecret = $tokenSecret;
	}

    /* public method to make an OAuth Request */
	public function request($method, $url, $parameters = null, $headers = null) {		
		$params = (array) $parameters;
		$options = array();
		$headers = (array) $headers;

        /* strip out query string and add it to parameters */
        $urlParts = parse_url($url);
        if (isset($urlParts['query'])) {
            $params = array_merge($params, $this->parseQueryString($urlParts['query']));
        }

        $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';
		$curl_url = $this->baseURL($url);
		$curl_headers = $headers;

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->consumerKey;
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_signature_method'] = $this->signatureMethod;
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_version'] = '1.0';
		
		if ($this->token) {
		    $oauth['oauth_token'] = $this->token;
		}

        switch ($method)
        {
            case 'POST':
                $params = array_merge($params, $oauth);
        		$params['oauth_signature'] = $this->oauthSignature($method, $curl_url, $params);
                $options[CURLOPT_POST] = true;
                $curl_headers[] = $this->calculateHeader($curl_url, $params);
                break;
                
            case 'GET':
                $data = $oauth;
                if(count($params)>0) {
                    $data = array_merge($data, $params);
                    $curl_url .= '?'. $this->buildQuery($params);
                }
                $base_url = $this->baseURL($curl_url);
        		$oauth['oauth_signature'] = $this->oauthSignature($method, $base_url, $data);
                $curl_headers[] = $this->calculateHeader($curl_url, $oauth);
                break;
            default:
                throw new Exception("Invalid method $method");
                break;
        }            

        $curl_headers[] = 'Expect:';

		// set options
		$options[CURLOPT_URL] = $curl_url . $fragment;
		$options[CURLOPT_FOLLOWLOCATION] = false;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_HTTPHEADER] = $curl_headers;
		$options[CURLOPT_HEADERFUNCTION] = array($this,'readHeader');

		// init
		$this->curl = curl_init();		
		$this->returnHeaders = array();
		
		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		
		// check for errors
        $http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if (curl_errno($this->curl) || $http_code >= 400) {
            error_log("There was an error $http_code retrieving $curl_url: $response");
            return false;
        }
        
		/* see if there is a redirect. If so resign and submit */
		if (isset($this->returnHeaders['Location'])) {
		    $redirectParts = parse_url($this->returnHeaders['Location']);
		    if (isset($redirectParts['query'])) {
		        $parameters = array_merge($parameters, $this->parseQueryString($redirectParts['query']));
		    }
		    $newURL = $this->baseURL($this->returnHeaders['Location']);
		    
    		return $this->request($method, $newURL, $parameters, $headers);
		}
		
		return $response;
	}

    private function readHeader($ch, $header) {
        $value = trim($header);
        if (preg_match("/^(.*?):(.*)$/", $value, $bits)) {
            $this->returnHeaders[$bits[1]] = trim($bits[2]); 
        } elseif ($value) {
            $this->returnHeaders[] = $value;
        }
        return strlen($header);
    }
    
	public function __construct($consumerKey, $consumerSecret) {
	    $this->consumerKey = $consumerKey;
	    $this->consumerSecret = $consumerSecret;
	    
	    if (empty($this->consumerKey) || empty($this->consumerSecret)) {
	        throw new Exception("Consumer key and secret not set");
	    }
	}
}
