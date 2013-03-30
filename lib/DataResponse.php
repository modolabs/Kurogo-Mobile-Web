<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataResponse');
class DataResponse
{
    protected $retriever;
    protected $cacheLifeTime=0;
    protected $responseTimestamp;
    protected $response;
    protected $responseCode;
    protected $responseStatus;
    protected $responseError;
    protected $responseStartTime;
    protected $responseEndTime;
    protected $responseTimeElapsed;
    // target encoding
    protected $sourceEncoding;
    protected $fromCache = false;
    protected $context=array(); // response defined.
    
    public function setStartTime($time) {
        $this->responseStartTime = $time;
        $this->responseTimeElapsed = $this->responseEndTime - $this->responseStartTime;
    }

    public function setEndTime($time) {
        $this->responseEndTime = $time;
        $this->responseTimeElapsed = $this->responseEndTime - $this->responseStartTime;
    }
    
    public function setFromCache($cache) {
    	$this->fromCache = (bool) $cache;
    }
    
	public function getFromCache() {
		return $this->fromCache;
	}

    public function getStartTime() {
        return $this->responseStartTime;
    }

    public function getEndTime() {
        return $this->responseEndTime;
    }

    public function getTimeElapsed() {
        return $this->responseTimeElapsed;
    }

    public function getResponseFile() {
        throw new KurogoDataException("getResponseFile() does not yet work with " . get_Class($this));
    }
    
    public function setRetriever(DataRetriever $retriever) {
        $this->retriever = $retriever;
    }
    
    public function getRetriever() {
        return $this->retriever;
    }
    
    public function clearRetriever() {
        $this->retriever = null;
    }
    
    public static function factory($responseClass, $args) {
        if (!class_exists($responseClass)) {
            throw new KurogoConfigurationException("Response class $responseClass not defined");
        }
        
        $response = new $responseClass;
        
        if (!$response instanceOf DataResponse) {
            throw new KurogoConfigurationException(get_class($response) . " is not a subclass of DataResponse");
        }

        $response->init($args);
        return $response;
    }
    
    public function init($args) {
        if(isset($args['SOURCE_ENCODING']) && strlen($args['SOURCE_ENCODING']) > 0) {
            $this->sourceEncoding = $args['SOURCE_ENCODING'];
        }
    }
    
    public function getResponse() {
        return $this->response;
    }

    public function getResponseError() {
        return $this->responseError;
    }
    
    public function setResponseError($error) {
        $this->responseError = $error;
    }

    public function getCode() {
        return $this->responseCode;
    }
    
    public function setResponse($response) {
        $this->responseTimestamp = time();
        // only string can be converted to specified encoding
        if($this->sourceEncoding && is_string($response)) {
            $response = mb_convert_encoding($response, "UTF-8", $this->sourceEncoding);
        }
        $this->response = $response;
    }    

    public function setCode($code) {
        $this->responseCode = $code;
    }    
    
    public function getContext($var) {
        return isset($this->context[$var]) ? $this->context[$var] : null;
    }

    public function setContext($var, $value) {
        $this->context[$var] = $value;
    }

    public function setCacheLifetime($cacheLifetime) {
        $this->cacheLifeTime = $cacheLifetime;
    }
    
    public function __construct() {
        $this->responseTimestamp = time();
    }
}
