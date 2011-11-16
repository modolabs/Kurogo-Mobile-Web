<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * @package ExternalData
 */
includePackage('DataRetriever');
includePackage('DataResponse');
abstract class DataRetriever {

    protected $DEFAULT_RESPONSE_CLASS = 'DataResponse';
    protected $DEFAULT_PARSER_CLASS=null; 
    protected $DEFAULT_CACHE_LIFETIME = 900; // 15 min
    protected $authority;
    protected $debugMode = false;
    protected $supportsSearch = false;
    protected $options = array();
    protected $cache;
    protected $cacheKey;
    protected $cacheGroup;
    protected $cacheLifetime = null; //if null it will use cache default.

    abstract protected function retrieveData();
    
    public function setCacheLifeTime($cacheLifetime) {
        $this->cacheLifetime = $cacheLifetime;
    }

    public function setCacheKey($cacheKey) {
        $this->cacheKey = $cacheKey;
    }

    public function setCacheGroup($cacheGroup) {
        $this->cacheGroup = $cacheGroup;
    }
    
    protected function cacheKey() {
        return $this->cacheKey;
    }
    
    protected function cacheGroup() {
        return $this->cacheGroup;
    }

    protected function cacheLifetime() {
        return is_null($this->cacheLifetime) ? $this->DEFAULT_CACHE_LIFETIME : $this->cacheLifetime;
    }
    
    protected function getCachedResponse($cacheKey, $cacheGroup) {
        if ($cacheKey) {
            $this->cache->setCacheGroup($cacheGroup);
            $this->cache->setCacheLifetime($this->cacheLifetime());
            return $this->cache->get($cacheKey);
        } else {
            Kurogo::log(LOG_DEBUG, "Not getting cache since cacheKey is empty", 'dataRetriever');
        }
        
        return null;
    }
    
    protected function cacheResponse($cacheKey, $cacheGroup, DataResponse $response) {
        if ($cacheKey) {
            $this->cache->setCacheGroup($cacheGroup);
            $this->cache->setCacheLifetime($this->cacheLifetime());
            return $this->cache->set($cacheKey, $response);
        } else {
            Kurogo::log(LOG_DEBUG, "Not caching since cacheKey is empty", 'dataRetriever');
        }
        
    }
    
    public function getData() {
        $cacheKey = $this->cacheKey();
        $cacheGroup = $this->cacheGroup();
        
        if (!$response = $this->getCachedResponse($cacheKey, $cacheGroup)) {

            $response = $this->retrieveData();
            if (!$response instanceOf DataResponse) {
                throw new KurogoDataException("Response must be instance of DataResponse");
            }
            if (!$response->getResponseError()) {
                $this->cacheResponse($cacheKey, $cacheGroup, $response);
            }
        }
        
        return $response;
    }
    
    protected function initResponse() {
        $response = DataResponse::factory($this->DEFAULT_RESPONSE_CLASS, array());
        if ($this->authority) {
            $response->setContext('authority', $this->authority);
        }
        
        return $response;
    }
    
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }

    protected function getAuthority() {
        return $this->authority;
    }
    
    public function supportsSearch() {
        return $this->supportsSearch;
    }
    
    public function getCurrentUser() {
        if ($this->authority) {
            return $this->authority->getCurrentUser();
        } else {
            $session = Kurogo::getSession();
            return $session->getUser();
        }
    }
    
    protected function setAuthority(AuthenticationAuthority $authority) {
        $this->authority = $authority;
    }
    
    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }
    
    protected function init($args) {

        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        if (isset($args['AUTHORITY'])) {
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($args['AUTHORITY'])) {
                $this->setAuthority($authority);
            }
        }
        
        $cacheClass = isset($args['CACHE_CLASS']) ? $args['CACHE_CLASS'] : 'DataCache';
        $this->cache = DataCache::factory($cacheClass, $args);
    }
    
    public function getDefaultParserClass() {
        return $this->DEFAULT_PARSER_CLASS;
    }
    
    public function clearInternalCache() {
    }
    
    public static function factory($retrieverClass, $args) {
        Kurogo::log(LOG_DEBUG, "Initializing DataRetriever $retrieverClass", "data");
        if (!class_exists($retrieverClass)) {
            throw new KurogoConfigurationException("Retriever class $retrieverClass not defined");
        }
        
        $retriever = new $retrieverClass;
        
        if (!$retriever instanceOf DataRetriever) {
            throw new KurogoConfigurationException(get_class($retriever) . " is not a subclass of DataRetriever");
        }

        $retriever->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        
        $retriever->init($args);
        return $retriever;
    }
}
