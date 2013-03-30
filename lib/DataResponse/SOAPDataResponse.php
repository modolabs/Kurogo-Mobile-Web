<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
