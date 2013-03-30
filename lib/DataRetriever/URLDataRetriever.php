<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * by URL
 * @package ExternalData
 */
 
class URLDataRetriever extends DataRetriever {

    protected $DEFAULT_RESPONSE_CLASS = 'HTTPDataResponse';
    protected $baseURL;
    protected $filters=array();
    protected $requestURL;
    protected $requestMethod='GET';
    protected $requestParameters=array();
    protected $requestHeaders=array();
    protected $requestData;
    protected $streamContext = null;
    protected $saveToFile = false;
    protected $useCurl = false;
    protected $authUser;
    protected $authPassword;
    protected $authType = 'basic';
    
    public function __wakeup() {
    	if ($this->useCurl) {
	        $this->initCurl($this->initArgs);
    	} else {
    	    $this->initStreamContext($this->initArgs);
    	}
    }
    
    /**
     * Sets the base url for the request. This value will be set automatically if the BASE_URL argument
     * is included in the factory method. Subclasses that have fixed URLs (i.e. web service data controllers)
     * can set this in the init() method.
     * @param string $baseURL the base url including protocol
     * @param bool clearFilters whether or not to clear the filters when setting (default is true)
     */
    public function setBaseURL($baseURL, $clearFilters=true) {
        $this->baseURL = $baseURL;
        if ($clearFilters) {
            $this->removeAllFilters();
        }
    }

    public function setURL($baseURL, $clearFilters=true) {
        $this->setBaseURL($baseURL, $clearFilters);
    }
    
    /**
     * Adds a parameter to the url request. In the subclass has not overwritten url() then it will be added to the
     * url as a query string. Note that you can only have 1 value per parameter at this time. 
     * @param string $var the parameter to add
     * @param mixed $value the value to assign. Must be a scalar value
     */
    public function addFilter($var, $value) {
        $this->filters[$var] = $value;
    }
    
    public function addParameter($var, $value) {
        $this->addFilter($var, $value);
    }
    
    public function setFilters($filters) {
        $this->filters = $filters;
    }
    
    public function setParameters($parameters) {
        $this->setFilters($parameters);
    }
    
    protected function parameters() {
        $this->initRequestIfNeeded();
        return $this->filters;
    }

    /**
     * Removes a parameter from the url request. 
     * @param string $var the parameter to remove
     */
    public function removeFilter($var) {
        if (isset($this->filters[$var])) {
            unset($this->filters[$var]);
        }
    }
    
    /**
     * Remove all parameters from the url request. 
     */
    public function removeAllFilters() {
        $this->filters = array();
    }

    protected function setCurlOption($key, $value) {
        curl_setopt($this->curl, $key, $value);
    }

    protected function processCurlHeader($curl, $header) {
        $this->responseHeaders[] = trim($header);
        return strlen($header);
    }

	protected function setCurlMethod() {
        $method = $this->method();
        switch($method) {
            case "POST":
                $this->setCurlOption(CURLOPT_POST, 1);
                break;
            case "GET":
                $this->setCurlOption(CURLOPT_HTTPGET, 1);
                break;
            default:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        return $method;
    }
    
    protected function setCurlHeaders() {
        $headers = array();
        foreach($this->headers() as $key => $val) {
            $headers[] = $key . ": " . $val;
        }
        $this->setCurlOption(CURLOPT_HTTPHEADER, $headers);
        return $headers;
    }

    protected function setCurlData() {
        if ($data = $this->data()) {
            $this->setCurlOption(CURLOPT_POSTFIELDS, $data);
        }
        return $data;
    }
    
    protected function initCurl($args) {
        if(!function_exists("curl_init")) {
            throw new KurogoDataException("cURL PHP extension not available");
        }

        $this->curl = curl_init();
        
        $this->setCurlOption(CURLOPT_USERAGENT, Kurogo::KurogoUserAgent());
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_HEADERFUNCTION, array($this, 'processCurlHeader'));
        $this->setCurlOption(CURLOPT_MAXREDIRS, 20);

        //setup the option which configured in feed file
        foreach($args as $key=>$value) {
        	if (preg_match("/CURLOPT_/", $key)) {
        	    $this->setCurlOption(constant($key), $value);
        	}
        }
    }
    
    protected function init($args) {
        parent::init($args);
        if (isset($args['USE_CURL'])) {
        	$this->useCurl = (bool) $args['USE_CURL'];
        }
        
        if (isset($args['BASE_URL'])) {
            $this->setBaseURL($args['BASE_URL']);
        }

        if (isset($args['METHOD'])) {
            $this->setMethod($args['METHOD']);
        }

        if (isset($args['HEADERS'])) {
            $this->setHeaders($args['HEADERS']);
        }

        if (isset($args['DATA'])) {
            $this->setData($args['DATA']);
        }

        if (isset($args['AUTH_TYPE'])) {
            $this->setAuthType($args['AUTH_TYPE']);            
        }
        
        if (isset($args['AUTH_USER'])) {
            $this->setAuthUser($args['AUTH_USER']);
        }

        if (isset($args['AUTH_PASSWORD'])) {
            $this->setAuthPassword($args['AUTH_PASSWORD']);
        }

        if ($this->useCurl) {
			$this->initCurl($args);
		} else {
			$this->initStreamContext($args);
		}
		$this->setCredentials($this->authUser, $this->authPassword);
    }
    
    public function setHeaders($headers) {
        if (is_array($headers)) {
            $this->requestHeaders = $headers;
        }
    }

    public function addHeader($header, $value) {
        $this->requestHeaders[$header] = $value;
    }

    protected function headers() {
        $this->initRequestIfNeeded();
        return $this->requestHeaders;
    }
    
    protected function method() {
        $this->initRequestIfNeeded();
        return $this->requestMethod;
    }
    
    protected function setData($data) {
        $this->requestData = $data;
    }

    protected function data() {
        $this->initRequestIfNeeded();
        return $this->requestData;
    }
    
    public function setMethod($method) {
        if (!in_array($method, array('POST','GET','DELETE','PUT'))) {
            throw new KurogoConfigurationException("Invalid method $method");
        }
        
        $this->requestMethod = $method;
    }

    private function setContextMethod() {
        $method = $this->method();
        stream_context_set_option($this->streamContext, 'http', 'method', $method);
        return $method;
        
    }

    private function setContextHeaders() {
        $_headers = array();
        $headers = $this->headers();
        //@TODO: Might need to escape this
        foreach ($headers as $header=>$value) {
            $_headers[] = "$header: $value";
        }
            
        stream_context_set_option($this->streamContext, 'http', 'header', implode("\r\n", $_headers));
        return $headers;
    }
    
    private function setContextData() {
        if ($requestData = $this->data()) {
            stream_context_set_option($this->streamContext, 'http', 'content', $requestData);
        }
        return $requestData;
    }

    public function setTimeout($timeout) {
        if ($this->useCurl) {
            $this->setCurlOption(CURLOPT_TIMEOUT, $timeout);
        } else {
            stream_context_set_option($this->streamContext, 'http', 'timeout', $timeout);
        }
    }
    
    public function setFollowLocation($follow) {
        if ($this->useCurl) {
            $this->setCurlOption(CURLOPT_FOLLOWLOCATION, $follow);
        } else {
            stream_context_set_option($this->streamContext, 'http', 'follow_location', $follow ? 1 : 0);
        }
    }

    protected function streamContextOpts($args) {
        $streamContextOpts = array(
            'http'=>array(
                'user_agent'=>Kurogo::KurogoUserAgent()
            )
        );
        
        if (isset($args['HTTP_PROXY_URL'])) {
            $streamContextOpts['http'] = array(
                'proxy'          => $args['HTTP_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }
        
        if (isset($args['HTTPS_PROXY_URL'])) {
            $streamContextOpts['https'] = array(
                'proxy'          => $args['HTTPS_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }

        return $streamContextOpts;        
    }
    
    protected function initStreamContext($args) {
        $this->streamContext = stream_context_create($this->streamContextOpts($args));
    }
    
    /**
     * Returns the url to use for the request. The default implementation will take the base url and
     * append any filters/parameters as query string parameters. Subclasses can override this method 
     * if a more dynamic method of URL generation is needed.
     * @return string
     */
    protected function url() {
        $url = $this->baseURL();
        $parameters = $this->parameters();
        if (count($parameters)>0) {
            $glue = strpos($this->baseURL, '?') !== false ? '&' : '?';
            $url .= $glue . http_build_query($parameters);
        }
        
        return $url;
    }
    
    protected function setAuthUser($user) {
        $this->authUser = $user;
    }

    protected function setAuthPassword($password) {
        $this->authPassword = $password;
    }

    protected function setAuthType($authType) {
        switch ($authType)
        {
            case 'basic':

            case 'digest':
            case 'ntlm':
                $this->authType = $authType;
                break;
            default:
                throw new KurogoConfigurationException("Invalid auth type $authType");
        }
    }
    
    protected function setCredentials($user, $password) {
        if (!strlen($user) || !strlen($password)) {
            return false;
        }
        $this->authUser = $user;
        $this->authPassword = $password;
        $cred = $this->authUser . ':' . $this->authPassword;
        
        switch ($this->authType)
        {
            case 'basic':
                if ($this->useCurl) {
                    $this->setCurlOption(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($this->authType)));
                    $this->setCurlOption(CURLOPT_USERPWD, $cred);
                } else {
                    $this->addHeader('Authorization', 'Basic ' . base64_encode($cred));
                }
                break;
            case 'digest':
            case 'ntlm':
                if ($this->useCurl) {
                    $this->setCurlOption(CURLOPT_USERPWD, $cred);
                    $this->setCurlOption(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($this->authType)));
                    
                } else {
                    throw new KurogoException("Digest and NTLM authentication require cURL");
                }
                break;
            default:
                throw new KurogoConfigurationException("Invalid auth type $this->authType");
        }
    }
    
    protected function baseURL() {
        $this->initRequestIfNeeded();
        return $this->baseURL;
    }
         
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url
     * @return string
     */
    protected function cacheKey() {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }
        
        if (!$url = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
        
        $key = 'url_' . md5($url);

        if ($data = $this->data()) {
            $key .= "_" . md5($data);
        }
        return $key;
    }

    protected function setSaveToFile($saveToFile) {
        $this->saveToFile = $saveToFile;
    }
    
    protected function saveToFile() {
        return $this->saveToFile;
    }
    
    /**
     * Retrieves the data using the config url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @return HTTPDataResponse a DataResponse object
     */
    protected function retrieveResponse() {
    
        $this->initRequestIfNeeded();
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
                
        $this->requestParameters = $this->parameters();
        if ($this->useCurl) {
        	$this->responseHeaders = array();
			$this->requestMethod = $this->setCurlMethod();
			$this->requestHeaders = $this->setCurlHeaders();
			$this->requestData = $this->setCurlData();
		} else {
			$this->requestMethod = $this->setContextMethod();
			$this->requestHeaders = $this->setContextHeaders();
			$this->requestData = $this->setContextData();
		}
        
        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'url_retriever');

        $url_parts = parse_url($this->requestURL);

        if (!isset($url_parts['scheme'])) {
             $this->DEFAULT_RESPONSE_CLASS="FileDataResponse";
        }else {
             $this->DEFAULT_RESPONSE_CLASS="HTTPDataResponse";
        }
        
        $response = $this->initResponse();
        $response->setStartTime(microtime(true));
        if (!$this->showWarnings) {
        	Kurogo::pushErrorReporting(E_ERROR);
        }
        
        if ($this->useCurl) {
			$this->setCurlOption(CURLOPT_URL, $this->requestURL);
			if ($file = $this->saveToFile()) {
				$data = $this->cache->getFullPath($file);
				$this->setCurlOption(CURLOPT_FILE, $data);
				$result = curl_exec($this->curl);
			} else {
				$data = curl_exec($this->curl);
			}
		} else {
			if ($file = $this->saveToFile()) {
				$data = $this->cache->getFullPath($file);
				$result = file_put_contents($data, file_get_contents($this->requestURL, false, $this->streamContext));
			} else {
				$data = file_get_contents($this->requestURL, false, $this->streamContext);
			}
		}
        if (!$this->showWarnings) {
        	Kurogo::popErrorReporting();
        }
        $response->setEndTime(microtime(true));
        
        if ($response instanceOf HTTPDataResponse) {
            $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders, $this->requestData);
        	if ($this->useCurl) {
	            $response->setResponseHeaders($this->responseHeaders);
				if ($error = curl_error($this->curl)) {
					$response->setResponseError($error);
				}
        	} else {
				$http_response_header = isset($http_response_header) ? $http_response_header : array();
	            $response->setResponseHeaders($http_response_header);
			}
            Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes in %.4f seconds", $response->getCode(), strlen($data), $response->getTimeElapsed()), 'url_retriever');
        } elseif ($response instanceOf FileDataResponse) {
            $response->setRequest($this->requestURL);
        }

        $response->setResponse($data);
        
        if ($response->getResponseError()) {
            Kurogo::log(LOG_WARNING, sprintf("%s for %s", $response->getResponseError(), $this->requestURL), 'url_retriever');
        }
        
        return $response;
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
	
	protected function canonicalURL($url) {
        $parts = parse_url($url);
        return $this->buildURL($parts);
	}
    
}
