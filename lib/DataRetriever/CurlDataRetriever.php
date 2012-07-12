<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
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
 
class CurlDataRetriever extends URLDataRetriever {
	protected $curl;
	protected $responseHeaders = array();
	
    public function __wakeup() {
        parent::__wakeup();
        $this->initCurl($this->initArgs);
    }
    
    protected function initCurl($args) {
        $this->curl = curl_init();
        
        $this->setCurlOption(CURLOPT_USERAGENT, Kurogo::KurogoUserAgent());
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_HEADERFUNCTION, array($this, 'processHeader'));
        
        //setup the option which configured in feed file
        foreach($args as $key=>$value) {
        	if (preg_match("/CURLOPT_/", $key)) {
        	    $this->setCurlOption(constant($key), $value);
        	}
        }
    }
    
    protected function processHeader($curl, $header) {
        $this->responseHeaders[] = trim($header);
        return strlen($header);
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
        $this->responseHeaders = array();
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
        
        if(!function_exists("curl_init")) {
            throw new KurogoDataException("cURL library can't be found");
        }
        
        $this->requestParameters = $this->parameters();
        $this->requestMethod = $this->setCurlMethod();
        $this->requestHeaders = $this->setCurlHeaders();
        $this->requestData = $this->setCurlData();
        
        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'curl_retriever');

        $url_parts = parse_url($this->requestURL);

        if (!isset($url_parts['scheme'])) {
             return parent::retrieveResponse();
        } else {
             $this->DEFAULT_RESPONSE_CLASS="HTTPDataResponse";
        }
        
        $this->setCurlOption(CURLOPT_URL, $this->requestURL);
        $response = $this->initResponse();
        $response->setStartTime(microtime(true));

        if ($file = $this->saveToFile()) {
            $data = $this->cache->getFullPath($file);
        	$this->setCurlOption(CURLOPT_FILE, $data);
            $result = curl_exec($this->curl);
        } else {
        	$data = curl_exec($this->curl);
        	$response->setResponseError(curl_error($this->curl));
        }
        $response->setEndTime(microtime(true));

        if ($response instanceOf HTTPDataResponse) {
            $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders, $this->requestData);
            $response->setResponseHeaders($this->responseHeaders);
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
