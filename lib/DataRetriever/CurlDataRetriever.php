<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * by URL
 * @package ExternalData
 */
 
class CurlDataRetriever extends URLDataRetriever {
	protected $curl;
	protected $returnHeader = false;
	
    public function __wakeup() {
        $this->initCurl($this->initArgs);
    }
    
    protected function initCurl($args) {
        $this->curl = curl_init();
        
        curl_setopt($this->curl, CURLOPT_USERAGENT, Kurogo::KurogoUserAgent());
        //if CURLOPT_RETURNTRANSFER is not set false in feed file, it will be set to true by default
        if(!array_key_exists('CURLOPT_RETURNTRANSFER', $args)) {
        	curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        }
        
        //setup the option which configured in feed file
        foreach($args as $key=>$value) {
        	if(preg_match("/CURLOPT_/", $key)) {
        		curl_setopt($this->curl, constant($key), $value);
        		//if CURLOPT_HEADER is set in feed, the headers will return in data returns
        		if($key == 'CURLOPT_HEADER' && $value == 1) {
        			$this->returnHeader = true;
        		}
        	}
        }
    }
    
    protected function setCurlOption($key, $value) {
        curl_setopt($this->curl, $key, $value);
    }
	
    protected function setCurlMethod() {
        $method = $this->method();
        switch($method) {
            case "POST":
                $this->setCurlOption(CURLOPT_POST, 1);
                break;
            default:
                $this->setCurlOption(CURLOPT_HTTPGET, 1);
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
    
    protected function init($args) {
        parent::init($args);
        $this->initCurl($args);
    }
    
    /**
     * Retrieves the data using the curl methods, you can setup the options in the form of CURLOPT_xxx in feed files.
     * @return HTTPDataResponse a DataResponse object
     */
    protected function retrieveResponse() {
    
        $this->initRequestIfNeeded();
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
        
        if(!function_exists("curl_init")) {
            throw new KurogoDataException("cURL library can't be found");
        }
        
        $this->requestParameters = $this->parameters();
        $this->requestMethod = $this->setCurlMethod();
        $this->requestHeaders = $this->setCurlHeaders();
        $this->requestData = $this->data();
        
        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'curl_retriever');

        $url_parts = parse_url($this->requestURL);

        if (!isset($url_parts['scheme'])) {
             return parent::retrieveResponse();
        }
        
        $response = $this->initResponse();

        if ($file = $this->saveToFile()) {
            $data = $this->cache->getFullPath($file);
        	$this->setCurlOption(CURLOPT_URL, $this->requestURL);
            $result = file_put_contents($data, curl_exec($this->curl));
        } else {
        	$this->setCurlOption(CURLOPT_URL, $this->requestURL);
        	$data = curl_exec($this->curl);
        	//if returned header, explode it into header and data.
        	if($this->returnHeader) {
		        list($header,$data) = explode("\r\n\r\n", $data, 2);
		        $http_response_header = array();
		        $http_response_header = explode("\r\n", $header);
        	}
        }

        if ($response instanceOf HTTPDataResponse) {
            $http_response_header = isset($http_response_header) ? $http_response_header : array();
            $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders, $this->requestData);
            $response->setResponseHeaders($http_response_header);
            Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $response->getCode(), strlen($data)), 'curl_retriever');
        } elseif ($response instanceOf FileDataResponse) {
            $response->setRequest($this->requestURL);
        }

        $response->setResponse($data);
        
        if ($response->getResponseError()) {
            Kurogo::log(LOG_WARNING, sprintf("%s for %s", $response->getResponseError(), $this->requestURL), 'curl_retriever');
        }
        
        return $response;
    }
}
