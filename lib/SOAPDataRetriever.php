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
    protected $soapOptions = array(); //use it and wsdl to instantiate SoapClient
    protected $method = ''; //soapclient call the method to retrieve data
    protected $methodParams = array();
    
    public function setWSDL($wsdl) {
        $this->wsdl = $wsdl;
    }
    
    public function getWSDL() {
        return $this->wsdl;
    }
	
	public function setMethod($method) {
	    $this->method = $method;
	}
	
	public function getMethod() {
	    return $this->method;
	}
	
	public function setMethodParams($params) {
	    return $this->methodParams;
	}
	
	public function getSoapClient() {
	    return $this->soapClient;
	}
	
    public function init($args) {
        //get global options from the site soap section
        $args = array_merge(Kurogo::getOptionalSiteSection('soap'), $args);
        if (!isset($args['WSDL']) && $args['WSDL']) {
            throw new KurogoConfigurationException("wsdl for SOAP not defined");
        }
        $this->setWSDL($args['WSDL']);
        $this->initSoapOptions($args);
        $this->initSoapClient();
        
        if (isset($args['method'])) {
            $this->setMethod($args['method']);
        }
        
        if (isset($args['methodParams'])) {
            $this->setMethodParams($args['methodParams']);
        }
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
             'trace' => 0,
             'exceptions' => false,
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
    
    public function initSoapClient() {
		if (!$this->soapClient) {
		    try {
		        $this->soapClient = new SoapClient($this->wsdl, $this->soapOptions);
		        if ($functions = $this->soapClient->__getFunctions()) {
		            $this->parseSoapFunctions($functions);
		        }
		    } catch (Exception $e) {
		        Kurogo::log(LOG_WARNING, sprintf("instantiate SoapClient failed for %s", $e->getMessage()), 'soap_retriever');
		    }
		}
		return $this->soapClient;
	}
    
    protected function parseSoapFunctions($functions) {
        exit;
    }
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url
     * @return string
     */
    public function getCacheKey() {
        return 1;
    }
    
    /**
     * Retrieves the data using the config url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @return string the response from the server
     * @TODO support POST requests and custom headers and perhaps proxy requests
     */
    public function retrieveData() {
        return array();
    }
}
