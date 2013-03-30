<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class HTTPDataResponse extends DataResponse
{
    protected $requestMethod;
    protected $requestURL;
    protected $requestParameters=array();
    protected $requestHeaders=array();
    protected $requestData;
    protected $responseStatus;
    protected $responseHeaders=array();
        
    public function setRequest($method, $url, $parameters=null, $headers=null, $data=null) {
        $this->requestMethod = $method;
        $this->requestURL = $url;
        $this->requestParameters = $parameters;
        $this->requestHeaders = $headers;
        $this->requestData = $data;
    }
    public function getRequest()
    {
        return array(
            'method'     => $this->requestMethod,
            'url'        => $this->requestURL,
            'parameters' => $this->requestParameters,
            'headers'    => $this->requestHeaders,
            'data'       => $this->requestData
        );
    }
    
    public function getRequestMethod() {
        return $this->requestMethod;
    }
    
    public function setResponseHeaders($http_response_header) {
        if (is_array($http_response_header)) {
            $this->parseHTTPResponseHeaders($http_response_header);
        }
    }
    
    protected function parseHTTPResponseHeaders($http_response_header) {
        foreach ($http_response_header as $http_header) {
            list($header, $value) = $this->parseHTTPHeader($http_header);
            if ($header) {
                $this->responseHeaders[$header] = $value;
            } elseif (preg_match("#^(HTTP/1.\d) (\d\d\d) (.+)$#", $http_header, $bits)) {
                $this->responseCode = intval($bits[2]);
                $this->responseStatus = $bits[3];
                if ($this->responseCode>=400) {
                    $this->responseError = $bits[3];
                } else {
                    $this->responseError = null;
                }
            }
        }
        
        if (!$this->responseCode) { 
            $this->responseError = 'Error Retrieving Data';
        }
    }
    
    protected function parseHTTPHeader($header) {
        if (preg_match("/(.*?):\s*(.*)/", $header, $bits)) {
            return array(
                trim($bits[1]),
                trim($bits[2])
            );
        }
    }
    
    public function getHeaders() {
        return $this->responseHeaders;
    }

    public function getHeader($header) {
        return isset($this->responseHeaders[$header]) ? $this->responseHeaders[$header] : null;
    }
}
