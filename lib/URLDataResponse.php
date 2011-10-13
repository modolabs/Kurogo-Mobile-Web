<?php

class URLDataResponse extends ExternalDataResponse {
    protected $requestMethod;
    protected $requestURL;
    protected $requestParameters=array();
    
    public function setRequest($method, $url, $parameters, $headers) {
        $this->requestMethod = $method;
        $this->requestURL = $url;
        $this->requestParameters = $parameters;
        if ($headers) {
            parent::setRequest($headers);
        }
    }

}
