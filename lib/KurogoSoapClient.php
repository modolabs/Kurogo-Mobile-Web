<?php 

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* this SOAP client uses CURL to do its work when authentication is needed. */
class KurogoSoapClient extends SoapClient 
{ 
    protected $returnHeaders = array();
    protected $auth;
    protected $cred;
    protected $ssl_verify = true;

	protected function buildURL($parts, $include_resource=false) {
        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? $parts['host'] : '';
        $path = (isset($parts['path'])) ? ($include_resource ? $parts['path'] : dirname($parts['path']))  : '';
    
        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
          $host = "$host:$port";
        }
        return rtrim("$scheme://$host$path",'/').'/';
	}
	
	protected function retrieveWSDL($wsdl) {
            
        $parts = parse_url($wsdl);
        
        if (!isset($parts['scheme'], $parts['host'])) {
            return $wsdl;
        }
                
        //use the md5 of the baseURL as the folder
        $baseURL = $this->buildURL($parts);
        $cacheFolder = CACHE_DIR . '/WSDL/' . md5($baseURL);
        
        //save the filename as is
        $cacheFile = basename($this->buildURL($parts, true));
        
        if (!$wsdl_cache = ini_get('soap.wsdl_cache_ttl')) {
            $wsdl_cache = 86400; // one day
        }

        $cache = new DiskCache($cacheFolder, $wsdl_cache, true);
        $cache->preserveFormat(); //make sure we save the raw data

        if ($cache->isFresh($cacheFile)) {
            $wsdl_data = $cache->read($cacheFile);
        } else {
            $ch = curl_init($wsdl); 
            if (!$this->ssl_verify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_setopt($ch, CURLOPT_HTTPAUTH, $this->auth);
            curl_setopt($ch, CURLOPT_USERPWD, $this->cred);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            if (!$wsdl_data = curl_exec($ch)) {
                if (!$error = curl_error($ch)) {
                    $error = "Unable to retrieve WSDL $wsdl";
                } 
                Kurogo::log(LOG_WARNING, "Unable to retrieve WSDL $wsdl: $error", 'soap');
                throw new KurogoException($error);
            }
                
            //save the WSDL to cache
            $cache->write($wsdl_data, $cacheFile);
            curl_close($ch); 
        }

        /* retrieve the imports */
        $parsedWSDL = $this->parseWSDL($wsdl_data);
        foreach ($parsedWSDL->getImports() as $import) {
            $url = $baseURL . $import;
            $this->retrieveWSDL($url);
        }
        
        // use the local cached WSDL file
        $wsdl = $cache->getFullPath($cacheFile);
        return $wsdl;
    }
    
    function parseWSDL($wsdl_data) {
        $parser = DataParser::factory('WSDLParser', array());
        $wsdl = $parser->parseData($wsdl_data);
        return $wsdl;
    }
	
    function __construct($wsdl, $options) { 

        // use auth
        if (isset($options['auth'])) {
            switch ($options['auth']) {
                case CURLAUTH_BASIC:
                case CURLAUTH_DIGEST:
                case CURLAUTH_NTLM:
                    $this->auth = $options['auth'];
                    break;
                default:
                    throw new KurogoConfigurationException("Unhandled auth parameter " . $options['auth']);
            }
        
            unset($options['auth']);
        }
        
        if (isset($options['ssl_verify'])) {
            $this->ssl_verify = $options['ssl_verify'] ? true : false;
            unset($options['ssl_verify']);
        }

        if (isset($options['login']) && isset($options['password'])) {
            $this->cred = $options['login'] .':' . $options['password'];
        } elseif ($this->auth) {
            //probably should deal with the fact that credentials haven't been included
        }
        
        if ($this->auth && $this->cred) {
            $wsdl = $this->retrieveWSDL($wsdl);
        }
        
        parent::__construct($wsdl, $options); 
    }

	public function __doRequest($request, $location, $action, $version, $one_way=0) {

        //use curl if there is auth
		if ($this->auth) {
            $headers = array(
                'Content-Type: text/xml; charset=' . Kurogo::getCharset()
            );

            $ch = curl_init($location); 
            if (!$this->ssl_verify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_setopt($ch, CURLOPT_HTTPAUTH, $this->auth);
            curl_setopt($ch, CURLOPT_USERPWD, $this->cred);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            Kurogo::log(LOG_WARNING, "SOAP result: $result, $location", 'soap'); 
            return $result;
        } else {
            return parent::__doRequest($request, $location, $action, $version, $one_way);
        }
    }
}
