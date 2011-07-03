<?php

class DataResponse
{
    protected $response;
    protected $responseCode;
    protected $responseStatus;
    protected $responseHeaders;
    
    public function __construct($response, $http_response_header) {
        $this->response = $response;
        $this->responseRawHeaders = $http_response_header;
        $this->parseHTTPResponseHeaders($http_response_header);
    }
    
    protected function parseHTTPResponseHeaders($http_response_header) {
        foreach ($http_response_header as $http_header) {
            list($header, $value) = $this->parseHTTPHeader($http_header);
            if ($header) {
                $this->responseHeaders[$header] = $value;
            } elseif (preg_match("#^(HTTP/1.\d) (\d\d\d) (.+)$#", $http_header, $bits)) {
                $this->responseCode = $bits[2];
                $this->responseStatus = $bits[3];
            }
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
    
    public function getResponse() {
        return $this->response;
    }

    public function getCode() {
        return $this->responseCode;
    }

    public function getStatus() {
        return $this->responseStatus;
    }

    public function getHeaders() {
        return $this->responseHeaders;
    }

    public function getHeader($header) {
        return isset($this->responseHeaders[$header]) ? $this->responseHeaders[$header] : null;
    }
}
