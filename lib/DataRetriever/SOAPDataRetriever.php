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

    protected $DEFAULT_RESPONSE_CLASS = 'SOAPDataResponse';
    protected $wsdl;
    protected $soapClient;
    protected $soapOptions = array('trace' => 1); //use it and wsdl to instantiate SoapClient
    protected $method;
    protected $parameters;
    protected $soapHeaders = array();
    
    public function setWSDL($wsdl) {
        if (($wsdl != $this->wsdl) && $this->soapClient) {
            $this->clearInternalCache();
        }
        $this->wsdl = $wsdl;
    }
    
    public function wsdl() {
        return $this->wsdl;
    }
	
	protected function getSoapClient() {
		if (!$this->soapClient) {
		    try {
		        $this->soapClient = new KurogoSoapClient($this->wsdl(), $this->soapOptions);
		        
                if ($this->soapHeaders) {
                    $this->soapClient->__setSoapHeaders($this->soapHeaders);
                }
                        
		    } catch (SoapFault $fault) {
		        Kurogo::log(LOG_WARNING, "Error creating SOAP Client: " . $fault->getMessage(), 'soap_retriever');
		        throw new KurogoDataException("Error creating SOAP Client");
		    }
		}
		return $this->soapClient;
	}
	
	public function addSoapHeader($namespace, $name, $data) {
	    $this->soapHeaders[] = new SOAPHeader($namespace, $name, $data);
    }
    
    protected function init($args) {
        parent::init($args);
    
        //get global options from the site soap section
        $args = array_merge(Kurogo::getOptionalSiteSection('soap'), $args);
        if (isset($args['WSDL']) && $args['WSDL']) {
            $this->setWSDL($args['WSDL']);
            $this->location = $args['WSDL'];
        }
        
        if (isset($args['BASE_URL'])) {
            $this->location = $args['BASE_URL'];
            $this->setSoapOption('location', $args['BASE_URL']);

            if (isset($args['URI'])) {
                $this->setSoapOption('uri', $args['URI']);
            } else {
                $this->setSoapOption('uri', FULL_URL_BASE);
            }
        }

        if (isset($args['METHOD'])) {
            $this->setMethod($args['METHOD']);
        }

        if (isset($args['PARAMETERS'])) {
            if (!is_array($args['PARAMETERS'])) {
                throw new KurogoConfigurationException("Parameters must be an array");
            }
            
            $this->setParameters($args['PARAMETERS']);
        }
        
        if (isset($args['SSL_VERIFY'])) {
            $this->setSoapOption('ssl_verify', $args['SSL_VERIFY']);
        }
    }

    protected function setMethod($method) {
        $this->method = $method;
    }
    
    protected function method() {
        return $this->method;
    }

    protected function setParameters(array $parameters) {
        $this->parameters = $parameters;    
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
    protected function cacheKey() {
    
        $wsdl = $this->wsdl();
        $method = $this->method();
        $parameters = $this->parameters();
        if (is_object($parameters)) {
            $parameters = get_object_vars($parameters);
        }
        
        if ($wsdl) {
            $location = $wsdl;
        } elseif (!$location = $this->getSoapOption('location')) {
            throw new KurogoDataException("SOAP WSDL not set");
        }
        
        if (!$method) {
            throw new KurogoDataException("SOAP method not set");
        }
        
        return 'soap_' . md5($location) . '-' . md5($method) . '-' . md5(serialize($parameters));
    }

    protected function initRequest() {
    }

    protected function retrieveResponse() {
    
        $this->initRequest();
        $method = $this->method();
        $parameters = $this->parameters();
        $soapClient = $this->getSOAPClient();

        Kurogo::log(LOG_DEBUG, sprintf("Calling SOAP Method %s", $method), 'soap');

        try {
            $data = $soapClient->__soapCall($method, $parameters);
        } catch (SoapFault $fault) {
            throw new KurogoDataException($fault->getMessage(), $fault->getCode());
        }

        if (!$lastResponseHeaders = $soapClient->__getLastResponseHeaders()) {
            $lastResponseHeaders = array();
        }
        
        $response = $this->initResponse();
        if ($this->authority) {
            $response->setContext('authority', $this->authority);
        }
        $response->setRequest($this->location, $method, $parameters, $this->soapHeaders, $this->soapOptions);
        $response->setResponse($data);

        return $response;
    }
    
    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->soapClient = null;
    }
}
