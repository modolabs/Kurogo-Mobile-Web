<?php

class SOAPDataResponse extends ExternalDataResponse {
    protected $requestApi;
    protected $requestApiParams;
    protected $requestWSDL;
    protected $requestOptions = array();
    protected $requestCookies = array();
    protected $requestLocation = '';
    protected $requestFunctions = array();
    
    public function setRequest($wsdl, $options, $api, $apiParams, $cookies, $location, $functions, $headers) {
        $this->requestWSDL = $wsdl;
        $this->requestOptions = $options;
        $this->requestApi = $api;
        $this->requestApiParams = $apiParams;
        $this->requestCookies = $cookies;
        $this->requestLocation = $location;
        $this->requestFunctions = $functions;
        
        if ($headers) {
            parent::setRequest($headers);
        }
    }
}
