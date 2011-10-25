<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * use soap api
 * @package ExternalData
 */
 
class SOAPDataRetriever extends DataRetriever {

    protected $wsdl;
    protected $soapClient;
    protected $soapOptions = array('trace' => 1); //use it and wsdl to instantiate SoapClient
    protected $soapApi = ''; //soapclient use the method to retrieve data
    protected $soapApiParams = array();
    protected $soapHeaders = array();
    protected $cookies = array();
    protected $location = '';
    protected $soapFunctions = array();
    
    public function setWSDL($wsdl) {
        $this->wsdl = $wsdl;
    }
    
    public function getWSDL() {
        return $this->wsdl;
    }
	
	public function setSoapApi($api) {
	    $this->soapApi = $api;
	}
	
	public function getSoapApi() {
	    return $this->soapApi;
	}
	
	public function setSoapApiParams($params) {
	    $this->soapApiParams = $params;
	}
	
	public function getSoapApiParams() {
	    return $this->soapApiParams;
	}
	
	public function getSoapFunctions() {
	    return $this->soapFunctions;
	}
	
	public function getSoapClient() {
		if (!$this->soapClient) {
		    try {
                $this->soapOptions = array("login" => "brent.wang", "password" => "symbio");
		        $this->soapClient = new KurogoSoapClient($this->wsdl, $this->soapOptions);
		        if ($functions = $this->soapClient->__getFunctions()) {
		            $this->parseSoapFunctions($functions);
		        }
		        //Sets the location of the Web service to use
                if ($this->location) {
                    $soapClient->__setLocation($this->location);
                }
        
                //defines a cookie to be sent along with the SOAP requests
                if ($this->cookies) {
                    foreach ($this->cookies as $name => $value) {
                        $soapClient->__setCookie($name, $value);
                    }
                }
        
                //Defines headers to be sent along with the SOAP requests
                if ($this->soapHeaders) {
                    $soapClient->__setSoapHeaders($this->soapHeaders);
                }
		    } catch (SoapFault $fault) {
		        Kurogo::log(LOG_WARNING, "Instantiate SoapClient failed", 'soap_retriever');
		        throw new KurogoDataException("Instantiate SoapClient failed");
		    }
		}
		return $this->soapClient;
	}
	
	protected function parseSoapFunctions($functions) {
	    foreach ($functions as $function) {
	        if (preg_match("/(.*?) (.*?)\(.*/", $function, $matches)) {
	            if (isset($matches[2]) && $matches[2]) {
	                $this->soapFunctions[] = $matches[2];
	            }
            }
	    }
    }
    
	public function addSoapHeaders($namespace, $name, $data) {
	    $this->soapHeaders[] = new SOAPHeader($namespace, $name, $data);
    }
    
    public function removeAllSoapHeaders() {
        $this->soapHeaders = array();
    }
    
    public function setCookies($name, $value = '') {
        if ($name) {
            $this->cookies[$name] = $value;
        }
    }
    
    public function clearCookies($name) {
        if (isset($this->cookies[$name])) {
            unset($this->cookies[$name]);
        }
    }
    
    public function clearAllCookies() {
        $this->cookies = array();
    }
    
    public function setLocation($location) {
        if ($location) {
            $this->location = $location;
        }
    }
    
    public function clearLocation() {
        $this->location = '';
    }
    
    public function init($args) {
        //get global options from the site soap section
        $args = array_merge(Kurogo::getOptionalSiteSection('soap'), $args);
        if (!isset($args['WSDL']) && $args['WSDL']) {
            throw new KurogoConfigurationException("wsdl for SOAP not defined");
        }
        $this->setWSDL($args['WSDL']);

        if (isset($args['api'])) {
            $this->setSoapApi($args['api']);
        }
        
        if (isset($args['apiParams'])) {
            $this->setSoapApiParams($args['apiParams']);
        }

        $this->initSoapOptions($args);
    }

    protected function initSoapOptions($args) {
        foreach ($this->validSoapOptions() as $option => $value) {
            if (isset($args[$option])) {
                if ($value && in_array($args[$option], $value)) {
                    $this->soapOptions[$option] = $args[$option];
                } elseif ($args[$option]) {
                    $this->soapOptions[$option] = $args[$option];
                }
            }
        }
    }

    protected function validSoapOptions() {
        return array(
             'soap_version' => array(SOAP_1_1, SOAP_1_2),
             'encoding' => '',
             'exceptions' => false,
             'login' => '',
             'password' => '',
             'proxy_host' => '',
             'proxy_port' => '',
             'proxy_login' => '',
             'proxy_password' => '',
             'compression' => '',
             'connection_timeout' => 0,
             'cache_wsdl' => array(WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY, WSDL_CACHE_BOTH),
             'user_agent' => '',
             'features' => array(SOAP_SINGLE_ELEMENT_ARRAYS, SOAP_USE_XSI_ARRAY_TYPE, SOAP_WAIT_ONE_WAY_CALLS)
        );
    }
    
    public function addSoapOption($option, $value) {
        $validSoapOptions = $this->validSoapOptions();
        if ($option && array_key_exists($option, $validSoapOptions)) {
            $this->soapOptions[$option] = $value;
        }
    }
    
    public function getSoapOptions($option) {
        if (isset($this->soapOptions[$option])) {
            return $this->soapOptions[$option];
        }
        return '';
    }
    
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the method and the methodParams
     * @return string
     */
    public function getCacheKey() {
        $wsdl = $this->getWSDL();
        $api = $this->getSoapApi();
        $apiParams = $this->getSoapApiParams();
        
        if (!$wsdl || !$api) {
            throw new KurogoDataException("soap wsdl or api could not be determined");
        }
        
        return 'soap_' . md5($wsdl. $api . var_export($this->getSoapApiParams(), true));
    }

    public function getData() {
        return $this->retrieveData();
    }
    
    public function retrieveData() {

        Kurogo::log(LOG_DEBUG, sprintf("Retrieving soap api of wsdl:%s,api:%s,params:%s", $this->getWSDL(), $this->getSoapApi(), var_export($this->getSoapApiParams(), true)), 'soap_retriever');
        
        $data = $this->call($this->soapApi, $this->soapApiParams);
        if (!$lastResponseHeaders = $this->getSoapClient()->__getLastResponseHeaders()) {
            $lastResponseHeaders = array();
        }

        /*
        $this->response = new SOAPDataResponse();
        $this->response->setRequest($this->wsdl, $this->soapOptions, $this->soapApi, $this->soapApiParams, $this->cookies, $this->location, $this->soapFunctions, $this->soapHeaders);

        $this->response->setResponse($data, $lastResponseHeaders);
        
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d", $this->getResponseCode()), 'soap_retriever');
        */
        return $data;
    }
    
    protected function call($method, $params = array()) {
        $result = array();
        $soapClient = $this->getSoapClient();
        if (!$method) {
            throw new KurogoDataException("soap api not defined");
        }
        $functions = $this->getSoapFunctions();
        if (!in_array($method, $functions)) {
            throw new KurogoDataException("Soap api $method not exists in soap api");
        }
        try {
            $data = $soapClient->{$method}($params);
        } catch (SoapFault $fault) {
            throw new Exception('Retrieving data error');
        }
        return $data;
    }
    
    /**
     * Interceptor. router the method that not exists in this class to the soapClient.
     * if the method exists in soap functions, then will call the api to retrieve data.
     */
    public function __call($method, $arguments) {
        $soapClient = $this->getSoapClient();
        if (in_array($method, $this->getSoapFunctions()) && count($arguments) <= 1) {
            $this->setSoapApi($method);
            $this->setSoapApiParams(current($arguments));
            return $this->getData();
        } else {
            throw new KurogoDataException("Call of unknown function '$method'.");
        }
    }
}
