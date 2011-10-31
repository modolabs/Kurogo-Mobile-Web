<?php

class SOAPDataResponse extends DataResponse {
    protected $wsdl;
    protected $requestMethod;
    protected $requestParameters;
    protected $requestHeaders;
    protected $requestOptions;

    public function setRequest($wsdl, $method, $parameters, $headers, $options) {
        $this->wsdl = $wsdl;
        $this->requestMethod = $method;
        $this->requestParameters = $parameters;
        $this->requestHeaders = $headers;
        $this->requestOptions = $options;
    }
}
