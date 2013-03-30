<?php 

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
    protected $initArgs = array();
    protected $authType = 'basic';
    protected $authUser;
    protected $authPassword;
    protected $useCurl = false;

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
            foreach($this->initArgs as $key=>$value) {
                if (preg_match("/CURLOPT_/", $key)) {
                    curl_setopt($ch, constant($key), $value);
                }
            }
            
            if ($this->authUser) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($this->authType)));
                curl_setopt($ch, CURLOPT_USERPWD, $this->authUser . ':' . $this->authPassword);
            }
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
    
    protected function setAuthUser($user) {
        $this->authUser = $user;
    }

    protected function setAuthPassword($password) {
        $this->authPassword = $password;
    }

    protected function setAuthType($authType) {
        switch ($authType)
        {
            case 'basic':
            case 'digest':
            case 'ntlm':
                $this->authType = $authType;
                break;
            default:
                throw new KurogoConfigurationException("Invalid auth type $authType");
        }
    }
    
    protected function parseWSDL($wsdl_data) {
        $parser = DataParser::factory('WSDLParser', array());
        $wsdl = $parser->parseData($wsdl_data);
        return $wsdl;
    }
    
    public static function factory($args, $wsdl, $options) {
        $options['initArgs'] = $args;
        $client = new KurogoSoapClient($wsdl, $options);
        return $client;
    }
	
    public function __construct($wsdl, $options) { 
        $args = $options['initArgs'];
        unset($options['initArgs']);
        $this->initArgs = $args;

        if (isset($args['USE_CURL'])) {
        	$this->useCurl = (bool) $args['USE_CURL'];
        }
        
        if (isset($args['AUTH_TYPE'])) {
            $this->setAuthType($args['AUTH_TYPE']);            
        }
        
        if (isset($args['AUTH_USER'])) {
            $this->setAuthUser($args['AUTH_USER']);
            $this->useCurl = true;
        }

        if (isset($args['AUTH_PASSWORD'])) {
            $this->setAuthPassword($args['AUTH_PASSWORD']);
            $this->useCurl = true;
        }

        if (strlen($this->authUser) && strlen($this->authPassword)) {
            $wsdl = $this->retrieveWSDL($wsdl);
        }

        parent::__construct($wsdl, $options); 
    }

    // icky function to deal with addresses returned by the server that
    // are not properly encoded (sharepoint sites that contain spaces may
    // return urls that contain unencoded spaces in the soap:address tag)
    protected function getRequestLocation($location) {
        return str_replace(' ', '%20', $location);
    }

    public function __doRequest($request, $location, $action, $version, $one_way=0) {
        $location = $this->getRequestLocation($location);
        //use curl if there is auth
        if ($this->useCurl) {
            $headers = array(
                'Content-Type: text/xml; charset=' . Kurogo::getCharset()
            );

            $ch = curl_init($location); 
            foreach($this->initArgs as $key=>$value) {
                if (preg_match("/CURLOPT_/", $key)) {
                    curl_setopt($ch, constant($key), $value);
                }
            }
            
            if ($this->authUser) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($this->authType)));
                curl_setopt($ch, CURLOPT_USERPWD, $this->authUser . ':' . $this->authPassword);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            $result = curl_exec($ch);
            Kurogo::log(LOG_WARNING, "SOAP result: $result, $location", 'soap'); 
            return $result;
        } else {
            return parent::__doRequest($request, $location, $action, $version, $one_way);
        }
    }
}
