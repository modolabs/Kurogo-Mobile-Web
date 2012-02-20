<?php

class SOAPDataResponse extends DataResponse {
    protected $requestLocation;
    protected $requestMethod;
    protected $requestParameters;
    protected $requestHeaders;
    protected $requestOptions;

    public function setRequest($location, $method, $parameters, $headers, $options) {
        $this->requestLocation = $location;
        $this->requestMethod = $method;
        $this->requestParameters = $parameters;
        $this->requestHeaders = $headers;
        $this->requestOptions = $options;
    }

    public function getRequestMethod() {
        return $this->requestMethod;
    }
}
