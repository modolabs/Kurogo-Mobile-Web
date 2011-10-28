<?php

class DataResponse
{
    protected $cacheLifeTime=0;
    protected $responseTimestamp;
    protected $response;
    protected $responseCode;
    protected $responseStatus;
    protected $responseError;
    protected $context=array(); // response defined.
    
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
