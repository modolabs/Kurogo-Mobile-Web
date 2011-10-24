<?php 

/* this SOAP client uses CURL to do its work when authentication is needed. */
class KurogoSoapClient extends SoapClient 
{ 
    protected $returnHeaders = array();
    protected $auth;
    protected $cred;

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

        if (isset($options['login']) && isset($options['password'])) {
            $this->cred = $options['login'] .':' . $options['password'];
        } elseif ($this->auth) {
            //probably should deal with the fact that credentials haven't been included
        }
        
        if ($this->auth && $this->cred) {
            //cache the WSDL
            $cacheFile = md5($wsdl) . '.wsdl';
            if (!$wsdl_cache = ini_get('soap.wsdl_cache_ttl')) {
                $wsdl_cache = 86400; // one day
            }

            $cache = new Diskcache(CACHE_DIR . '/WSDL', $wsdl_cache, true);
            $cache->preserveFormat(); //make sure we save the raw data

            if (!$cache->isFresh($cacheFile)) {
                $ch = curl_init($wsdl); 
                curl_setopt($ch, CURLOPT_HTTPAUTH, $this->auth);
                curl_setopt($ch, CURLOPT_USERPWD, $this->cred);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                if (($wsdl_data = curl_exec($ch)) === false) { 
                    throw new KurogoException(curl_error($ch));
                }
    
                //save the WSDL to cache
                $cache->write($wsdl_data, $cacheFile);
                curl_close($ch); 
            }
            
            // use the local cached WSDL file
            $wsdl = $cache->getFullPath($cacheFile);
        }
        
        parent::__construct($wsdl, $options); 
    }

	public function __doRequest($request, $location, $action, $version, $one_way) {

        //use curl if there is auth
		if ($this->auth) {
            $headers = array(
                'Content-Type: text/xml; charset=utf-8',
            );
		
            $ch = curl_init($location); 
            curl_setopt($ch, CURLOPT_HTTPAUTH, $this->auth);
            curl_setopt($ch, CURLOPT_USERPWD, $this->cred);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_POST, true );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            return curl_exec($ch);
        } else {
            return parent::__doRequest($request, $location, $action, $version, $one_way);
        }
    }
}
