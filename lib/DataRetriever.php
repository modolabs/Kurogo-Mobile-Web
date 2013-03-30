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
 * @package ExternalData
 */
includePackage('DataRetriever');
includePackage('DataResponse');
includePackage('DataParser');
abstract class DataRetriever {

    protected $DEFAULT_RESPONSE_CLASS = 'DataResponse';
    protected $DEFAULT_PARSER_CLASS;
    protected $PARSER_INTERFACE = 'DataParser';
    protected $DEFAULT_CACHE_LIFETIME = 900; // 15 min
    protected $initArgs=array();
    protected $authority;
    protected $dataModel;
    protected $debugMode = false;
    protected $options = array();
    protected $context = array(); // sent to the response
    protected $cache;
    protected $cacheKey;
    protected $cacheGroup;
    protected $cacheRequest = true;
    protected $cacheLifetime = null; //if null it will use cache default.
    protected $requestInit = false; //whether initRequest has been called or not
    protected $lastResponse;
    protected $showWarnings = true; // if false then data retrievers should properly suppress error messages
    protected $lastResponseTime;
    protected $lastParseTime;
    protected $parser;

    abstract protected function retrieveResponse();
    
    public function canSearch() {
    	return $this instanceOf SearchDataRetriever;
    }
    
    public function setDataModel(DataModel $dataModel) {
    	$this->dataModel = $dataModel;
    }
    
    public function setCacheLifeTime($cacheLifetime) {
        $this->cacheLifetime = $cacheLifetime;
        $this->cache->setCacheLifetime($cacheLifetime);
    }

    protected function setCacheKey($cacheKey) {
        $this->cacheKey = $cacheKey;
    }
    
    protected function setCacheRequest($cacheRequest) {
        $this->cacheRequest = $cacheRequest ? true : false;
    }

    protected function setCacheGroup($cacheGroup) {
        $this->cacheGroup = $cacheGroup;
    }
    
    protected function cacheKey() {
        $this->initRequestIfNeeded();
        return $this->cacheKey;
    }
    
    protected function clearCacheGroup($cacheGroup) {
        $this->cache->clearCacheGroup($cacheGroup);
    }

    protected function clearCache() {
        $this->cache->clearCache();
    }
    
    protected function cacheGroup() {
        $this->initRequestIfNeeded();
        return $this->cacheGroup;
    }

    protected function cacheLifetime() {
        return is_null($this->cacheLifetime) ? $this->DEFAULT_CACHE_LIFETIME : $this->cacheLifetime;
    }
    
    protected function getCachedResponse($cacheKey, $cacheGroup) {
    	$response = null;
        if ($cacheKey) {
            $this->cache->setCacheGroup($cacheGroup);
            $this->cache->setCacheLifetime($this->cacheLifetime());
            if ($response = $this->cache->get($cacheKey)) {
            	$response->setFromCache(true);
            }
            
        } else {
            Kurogo::log(LOG_DEBUG, "Not getting cache since cacheKey is empty", 'dataRetriever');
        }
        
        return $response;
    }
    
    protected function clearResponseCache($cacheKey, $cacheGroup) {
        if ($cacheKey) {
            $this->cache->setCacheGroup($cacheGroup);
            return $this->cache->delete($cacheKey);
        }
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
    
    /* subclasses can override this method to return a dynamic parser PER request */
    protected function parser() {
        return $this->parser;
    }
    
    public function getParser() {
        return $this->parser();
    }
    
    protected function shouldCacheRequest() {
        return $this->cacheRequest;
    }
    
    public function getResponse() {
        $this->lastResponse = null;
        $cacheKey = $this->shouldCacheRequest() ? $this->cacheKey() : null;
        $cacheGroup = $this->cacheGroup();
        
        if (!$response = $this->getCachedResponse($cacheKey, $cacheGroup)) {

            $startTime = microtime(true);
            $response = $this->retrieveResponse();
            $endTime = microtime(true);
            if (!$response instanceOf DataResponse) {
                throw new KurogoDataException("Response must be instance of DataResponse");
            }
            // if the retriever did not set the start/end time, set it here. it will include some overhead
            if (!$response->getEndTime()) {
                $response->setStartTime($startTime);
                $response->setEndTime($endTime);
            }
            if (!$response->getResponseError()) {
                $this->cacheResponse($cacheKey, $cacheGroup, $response);
            }
            $this->lastResponseTime = $response->getTimeElapsed();
        } else {
            $this->lastResponseTime = null;
        }
        
        $response->setRetriever($this);
        $this->lastResponse = $response;
        return $response;
    }
    
    protected function initResponse() {
        $response = DataResponse::factory($this->DEFAULT_RESPONSE_CLASS, $this->initArgs);
        foreach ($this->context as $var=>$value) {
            $response->setContext($var, $value);
        }
        
        return $response;
    }
    
    protected function setContext($var, $value) {
        $this->context[$var] = $value;
    }
    
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }

    protected function getAuthority() {
        return $this->authority;
    }
    
    protected function getCurrentUser() {
        if ($this->authority) {
            return $this->authority->getCurrentUser();
        } else {
            $session = Kurogo::getSession();
            return $session->getUser();
        }
    }
    
    protected function setAuthority(AuthenticationAuthority $authority) {
        $this->authority = $authority;
        $this->setContext('authority', $this->authority);
    }
    
    public function setOption($option, $value) {
        $this->options[$option] = $value;
        $this->parser()->setOption($option, $value);
    }
    
    public function setOptions($options) {
        foreach ($options as $arg=>$value) {
            $this->setOption($arg, $value);
        }
    }

    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    protected function initRequest() {
    }
    
    protected function initRequestIfNeeded() {
        if (!$this->requestInit) {
            $this->initRequest();
            $this->requestInit = true;
        }
    }
    
    protected function init($args) {
        //get global options from the site data_retriever section
        $args = array_merge(Kurogo::getOptionalSiteSection('data_retriever'), $args);

        $this->initArgs = $args;
        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        if (isset($args['DEFAULT_CACHE_LIFETIME'])) {       
            $this->DEFAULT_CACHE_LIFETIME = $args['DEFAULT_CACHE_LIFETIME'];        
        }
        
        if (isset($args['OPTIONS']) && is_array($args['OPTIONS'])) {
            $this->setOptions($args['OPTIONS']);
        }

        if (isset($args['AUTHORITY'])) {
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($args['AUTHORITY'])) {
                $this->setAuthority($authority);
            }
        }

        if (!isset($args['PARSER_CLASS'])) {
            if ($this->DEFAULT_PARSER_CLASS) {
                $args['PARSER_CLASS'] = $this->DEFAULT_PARSER_CLASS;
            } elseif (isset($args['DEFAULT_PARSER_CLASS']) && strlen($args['DEFAULT_PARSER_CLASS'])) {
                $args['PARSER_CLASS'] = $args['DEFAULT_PARSER_CLASS'];
            } else {
                $args['PARSER_CLASS'] = 'PassthroughDataParser';
            }            
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->cacheLifetime = $args['CACHE_LIFETIME'];
        } else {
            $args['CACHE_LIFETIME'] = $this->DEFAULT_CACHE_LIFETIME;
        }

        if (isset($args['SHOW_WARNINGS'])) {
           $this->showWarnings = (bool) $args['SHOW_WARNINGS'];
        }

        // instantiate the parser class
        $parser = DataParser::factory($args['PARSER_CLASS'], $args);
        $this->setParser($parser);
                
        $cacheClass = isset($args['CACHE_CLASS']) ? $args['CACHE_CLASS'] : 'DataCache';
        $this->cache = DataCache::factory($cacheClass, $args);
    }
    
    public function clearInternalCache() {
        $this->options = array();
        $this->context = array();
        $this->requestInit = false;
        $this->parser()->clearInternalCache();
    }
    
    public static function factory($retrieverClass, $args) {
        Kurogo::log(LOG_DEBUG, "Initializing DataRetriever $retrieverClass", "data");
        
        if (isset($args['PACKAGE'])) {
            Kurogo::includePackage($args['PACKAGE']);
        }
                
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

   /**
     * Sets the data parser to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the parser dynamically.
     * @param DataParser a instantiated DataParser object
     */
    public function setParser(DataParser $parser) {
        if ($parser instanceOf $this->PARSER_INTERFACE) {
            $this->parser = $parser;
        } else {
            throw new KurogoException("Data Parser " . get_class($parser) . " must conform to $this->PARSER_INTERFACE");
        }
    }
    
    /**
     * Parse the data.
     * @param string $data the data from a request
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseData($data, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser();
        }
        $startTime = microtime(true);
        $parsedData = $parser->parseData($data);
        $endTime = microtime(true);
        $this->lastParseTime = $endTime - $startTime;
        return $parsedData;
    }

    /**
     * Parse a file. 
     * @param string $file a file containing the contents of the data
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseFile($file, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser();
        }
        $startTime = microtime(true);
        $parsedData = $parser->parseFile($file);
        $endTime = microtime(true);
        $this->lastParseTime = $endTime - $startTime;
        return $parsedData;
    }

    /**
     * Parse the response
     * @param DataResponse $response the DataResponse from a request
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseResponse(DataResponse $response, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser();
        }
        $startTime = microtime(true);
        $parsedData = $parser->parseResponse($response);
        $endTime = microtime(true);
        $this->lastParseTime = $endTime - $startTime;
        Kurogo::log(LOG_INFO, sprintf("%s took %.2f seconds to parse", get_class($parser), $this->lastParseTime), 'data_parser');
        return $parsedData;
    }
    
    public function getLastResponse() {
    	return $this->lastResponse;
    }

    public function getResponseError() {
        if ($this->lastResponse) {
            return $this->lastResponse->getResponseError();
        }
        
        if ($response = $this->getResponse()) {
            return $response->getResponseError();
        }
    }

    public function getResponseCode() {
        if ($this->lastResponse) {
            return $this->lastResponse->getCode();
        }

        if ($response = $this->getResponse()) {
            return $response->getCode();
        }
    }

    /**
     * Return the parsed data. 
     * @return mixed the parsed data. This value is data dependent
     */
    public function getData(&$response=null) {

        $response = $this->getResponse();
        $parser = $this->parser();
                
        switch ($parser->getParseMode()) {
            case DataParser::PARSE_MODE_STRING:
                $data = $response->getResponse();
                $data = $this->parseData($data, $parser);
                break;
        
           case DataParser::PARSE_MODE_FILE:
                $file = $response->getResponseFile();
                $data =  $this->parseFile($file, $parser);
                break;

           case DataParser::PARSE_MODE_RESPONSE:
                $data = $this->parseResponse($response, $parser);
                if ($response->getResponseError()) {
                    $cacheKey = $this->shouldCacheRequest() ? $this->cacheKey() : null;
                    $cacheGroup = $this->cacheGroup();
                    $this->clearResponseCache($cacheKey, $cacheGroup);
                }
                break;
            default:
                throw new KurogoConfigurationException("Unknown parse mode");
        }
        
        return $data;
    }

    /**
     * Returns the target encoding of the result.
     * @return string. Default is utf-8
     */
    public function getEncoding() {
        return $this->parser()->getEncoding();
    }
        
}
