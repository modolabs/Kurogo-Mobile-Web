<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * use soap api
 * @package ExternalData
 */
includePackage('DataRetriever','SOAP'); 
class SOAPDataRetriever extends DataRetriever {

    protected $DEFAULT_RESPONSE_CLASS = 'SOAPDataResponse';
    protected $wsdl;
    protected $soapClient;
    protected $soapOptions = array('trace' => 1); //use it and wsdl to instantiate SoapClient
    protected $method;
    protected $parameters=array();
    protected $location;
    protected $uri;
    protected $action;
    protected $saveToFile = false;
    
    protected $soapHeaders = array();
    
    public function setWSDL($wsdl) {
        if (($wsdl != $this->wsdl) && $this->soapClient) {
            $this->clearInternalCache();
        }
        $this->wsdl = $wsdl;
    }
    
    public function wsdl() {
        $this->initRequestIfNeeded();
        return $this->wsdl;
    }
	
	protected function getSoapClient() {
		if (!$this->soapClient) {
		    try {
		        $this->soapClient = KurogoSoapClient::factory($this->initArgs, $this->wsdl(), $this->soapOptions);
		        
                if ($this->soapHeaders) {
                    $this->soapClient->__setSoapHeaders($this->soapHeaders);
                }
                        
		    } catch (SoapFault $fault) {
		        throw new KurogoDataException("Error creating SOAP Client " . $fault->getMessage());
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
    }

    protected function setMethod($method) {
        $this->method = $method;
    }
    
    protected function method() {
        $this->initRequestIfNeeded();
        return $this->method;
    }

    protected function setParameters(array $parameters) {
        $this->parameters = $parameters;    
    }
    
    protected function parameters() {
        $this->initRequestIfNeeded();
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

    protected function setSaveToFile($saveToFile) {
        $this->saveToFile = $saveToFile;
    }
    
    protected function saveToFile() {
        return $this->saveToFile;
    }

    protected function retrieveResponse() {
    
        $this->initRequestIfNeeded();
        $method = $this->method();
        $parameters = $this->parameters();
        $soapClient = $this->getSOAPClient();
        $options = array();
        if ($this->location) {
            $options['location'] = $this->location;
        }

        if ($this->uri) {
            $options['uri'] = $this->uri;
        }

        if ($this->action) {
            $options['soapaction'] = $this->action;
        }

        $headers = $this->soapHeaders;

        Kurogo::log(LOG_DEBUG, sprintf("Calling SOAP Method %s", $method), 'soap');


        $response = $this->initResponse();
        $response->setStartTime(microtime(true));
        try {
            $data = $soapClient->__soapCall($method, $parameters, $options, $headers, $outputHeaders);
        } catch (SoapFault $fault) {
			$response->setContext('soapRequestHeaders', $soapClient->__getLastRequestHeaders());
			$response->setContext('soapRequest', $soapClient->__getLastRequest());
			$response->setContext('soapResponse', $soapClient->__getLastResponse());
			$response->setContext('soapResponseHeaders', $soapClient->__getLastResponseHeaders());
            throw new KurogoDataException($fault->getMessage(), $fault->getCode());
        }
        $response->setEndTime(microtime(true));

        if (!$lastResponseHeaders = $soapClient->__getLastResponseHeaders()) {
            $lastResponseHeaders = array();
        }
        
        $response->setContext('soapRequestHeaders', $soapClient->__getLastRequestHeaders());
        $response->setContext('soapRequest', $soapClient->__getLastRequest());
        $response->setContext('soapResponse', $soapClient->__getLastResponse());
        $response->setContext('soapResponseHeaders', $soapClient->__getLastResponseHeaders());

        if ($file = $this->saveToFile()) {
            $filePath = $this->cache->getFullPath($file);
            file_put_contents($filePath, $data);
            $data = $filePath;
        }

        if ($this->authority) {
            $response->setContext('authority', $this->authority);
        }
        $response->setRequest($this->location, $method, $parameters, $this->soapHeaders, $this->soapOptions);
        $response->setResponse($data);

        return $response;
    }
    
    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->soapHeaders = array();
        $this->soapClient = null;
    }
}
