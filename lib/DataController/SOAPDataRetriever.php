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
    protected $method;
    protected $parameters;
    protected $soapHeaders = array();
    protected $soapFunctions = array();
    
    public function setWSDL($wsdl) {
        if (($wsdl != $this->wsdl) && $this->soapClient) {
            $this->clearInternalCache();
        }
        $this->wsdl = $wsdl;
    }
    
    public function wsdl() {
        return $this->wsdl;
    }
	
	public function getSoapFunctions() {
	    return $this->soapFunctions;
	}
	
	public function getSoapClient() {
		if (!$this->soapClient) {
		    try {
		        $this->soapClient = new KurogoSoapClient($this->wsdl(), $this->soapOptions);
		        
                if ($this->soapHeaders) {
                    $this->soapClient->__setSoapHeaders($this->soapHeaders);
                }
                        
		        if ($functions = $this->soapClient->__getFunctions()) {
		            $this->parseSoapFunctions($functions);
		        }
        
		    } catch (SoapFault $fault) {
		        Kurogo::log(LOG_WARNING, "Error creating SOAP Client: " . $fault->getMessage(), 'soap_retriever');
		        throw new KurogoDataException("Error creating SOAP Client");
		    }
		}
		return $this->soapClient;
	}
	
	protected function parseSoapFunctions($functions) {
	    $soapFunctions = array();
	    foreach ($functions as $function) {
	        if (preg_match("/(.*?) (.*?)\(.*/", $function, $matches)) {
	            if (isset($matches[2]) && $matches[2]) {
	                $soapFunctions[] = $matches[2];
	            }
            }
	    }
	    return $soapFunctions;
    }
    
	public function addSoapHeaders($namespace, $name, $data) {
	    $this->soapHeaders[] = new SOAPHeader($namespace, $name, $data);
    }
    
    protected function init($args) {
        parent::init($args);
    
        //get global options from the site soap section
        $args = array_merge(Kurogo::getOptionalSiteSection('soap'), $args);
        if (isset($args['WSDL']) && $args['WSDL']) {
            $this->setWSDL($args['WSDL']);
        }
        
        if (isset($args['SSL_VERIFY'])) {
            $this->setSoapOption('ssl_verify', $args['SSL_VERIFY']);
        }
    }
    
    protected function method() {
        return $this->method;
    }
    
    protected function parameters() {
        return $this->parameters;
    }

    public function setSoapOption($option, $value) {
        $this->soapOptions[$option] = $value;
    }
    
    public function getSoapOption($option) {
        if (isset($this->soapOptions[$option])) {
            return $this->soapOptions[$option];
        }
        return null;
    }
    
    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the method and the methodParams
     * @return string
     */
    public function getCacheKey() {
    
        $wsdl = $this->wsdl();
        $method = $this->method();
        $parameters = $this->parameters();
        if (is_object($parameters)) {
            $parameters = get_object_vars($parameters);
        }
        
        if (!$wsdl) {
            throw new KurogoDataException("SOAP WSDL not set");
        }
        
        if (!$method) {
            throw new KurogoDataException("SOAP method not set");
        }
        
        return 'soap_' . md5($wsdl) . '-' . md5($method) . '-' . md5(serialize($parameters));
    }

    public function retrieveData() {
    
        $wsdl = $this->wsdl();
        $method = $this->method();
        $parameters = $this->parameters();
        $soapClient = $this->getSOAPClient();

        Kurogo::log(LOG_DEBUG, sprintf("Calling SOAP Method %s from %s", $method, $wsdl), 'soap');

        try {
            $data = $soapClient->{$method}($parameters);
        } catch (SoapFault $fault) {
            throw new KurogoDataException('Retrieving data error');
        }

        if (!$lastResponseHeaders = $this->getSoapClient()->__getLastResponseHeaders()) {
            $lastResponseHeaders = array();
        }
        
        $response = new SOAPDataResponse();
        $response->setRequest($wsdl, $method, $parameters, $this->soapHeaders, $this->soapOptions);
        $response->setResponse($data);

        return $response;
    }
    
    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->soapClient = null;
    }
}
