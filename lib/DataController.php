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
 * @package DataController
 */

/**
 * A generic class to handle the retrieval of external data
 * 
 * Handles retrieval, caching and parsing of data. 
 * @package DataController
 */
includePackage('DataController');
includePackage('DataResponse');
abstract class DataController
{
    protected $DEFAULT_PARSER_CLASS='PassthroughDataParser';
    protected $initArgs=array();
    protected $cacheFolder='Data';
    protected $parser;
    protected $url;
    protected $cache;
    protected $baseURL;
    protected $title;
    protected $method='GET';
    protected $filters=array();
    protected $requestHeaders=array();
    protected $response;
    protected $totalItems = null;
    protected $debugMode=false;
    protected $useCache=true;
    protected $useStaleCache=true;
    protected $cacheLifetime=900;
    protected $streamContext = null;
    
    /**
     * This method should return a single item based on the id
     * @param mixed $id the id to retrieve. The value of this id is data dependent.
	 * @return mixed The return value is data dependent. Subclasses should return false or null if the item could not be found
     */
    abstract public function getItem($id);

    /**
     * Returns the folder used to store caches. Subclasses should simply set the $cacheFolder property
	 * @return string
     */
    protected function cacheFolder() {
        return CACHE_DIR . "/" . $this->cacheFolder;
    }
    
    /**
     * Turns on or off debug mode. In debug mode, URL requests and information are logged to the php error log
     * @param bool 
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }
    
    /**
     * Adds a parameter to the url request. In the subclass has not overwritten url() then it will be added to the
     * url as a query string. Note that you can only have 1 value per parameter at this time. This method
     * will call clearInternalCache() since this will cause any previous data to be invalid.
     * @param string $var the parameter to add
     * @param mixed $value the value to assign. Must be a scalar value
     */
    public function addFilter($var, $value) {
        $this->filters[$var] = $value;
        $this->clearInternalCache();
    }

    /**
     * Removes a parameter from the url request. This method will call clearInternalCache() since this 
     * will cause any previous data to be invalid.
     * @param string $var the parameter to remove
     */
    public function removeFilter($var) {
        if (isset($this->filters[$var])) {
            unset($this->filters[$var]);
            $this->clearInternalCache();
        }
    }

    /**
     * Remove all parameters from the url request. This method will call clearInternalCache() since 
     * this will cause any previous data to be invalid.
     */
    public function removeAllFilters() {
        $this->filters = array();
        $this->clearInternalCache();
    }

    /**
     * Clears the internal cache of data. Subclasses can override this method to clean up any necessary
     * state, if necessary. Subclasses should call parent::clearInternalCache()
     */
    protected function clearInternalCache() {
        $this->setTotalItems(null);
    }

    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url
     * @return string
     */
    protected function cacheFilename($url = null) {
        $url = $url ? $url : $this->url();
        return md5($url);
    }

   /**
     * Sets the data parser to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the parser dynamically.
     * @param DataParser a instantiated DataParser object
     */
    public function setParser(DataParser $parser) {
        $this->parser = $parser;
    }

    /**
     * Turns on or off using cache. You could also set cacheLifetime to 0
     * @param bool
     */
    public function setUseCache($useCache) {
        $this->useCache = $useCache ? true : false;
    }
    
    /**
     * Sets the title of the controller. Subclasses could use this if the title is dynamic.
     * @param string
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Returns the title of the controller.
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the total number of items in the request. If subclasses override parseData() this method
     * should be called when the number of items is known. The value is usually set by retrieving the
     * the value of getTotalItems() from the DataParser.
     * @param int
     */
    protected function setTotalItems($totalItems) {
        $this->totalItems = $totalItems;
    }
    
    /**
     * Returns the total number of items in the request
     * @return int
     */
    public function getTotalItems() {
        return $this->totalItems;
    }
    
    /**
     * Sets the base url for the request. This value will be set automatically if the BASE_URL argument
     * is included in the factory method. Subclasses that have fixed URLs (i.e. web service data controllers)
     * can set this in the init() method.
     * @param string $baseURL the base url including protocol
     * @param bool clearFilters whether or not to clear the filters when setting (default is true)
     */
    public function setBaseURL($baseURL, $clearFilters=true) {
        $this->baseURL = $baseURL;
        if ($clearFilters) {
            $this->removeAllFilters();
        }
        $this->clearInternalCache();
    }
    
    /**
     * The initialization function. Sets the common parameters based on the $args. This method is
     * called by the public factory method. Subclasses can override this method, but must call parent::init()
     * FIRST. Optional parameters include PARSER_CLASS, BASE_URL, TITLE and CACHE_LIFETIME. Arguments
     * are also passed to the data parser object
     * @param array $args an associative array of arguments and paramters
     */
    protected function init($args) {
        $this->initArgs = $args;

        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        // use a parser class if set, otherwise use the default parser class from the controller
        $args['PARSER_CLASS'] = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : $this->DEFAULT_PARSER_CLASS;

        // instantiate the parser class and add it to the controller
        $parser = DataParser::factory($args['PARSER_CLASS'], $args);
        $this->setParser($parser);
        //$parser->setDataController($this);
        
        if (isset($args['BASE_URL'])) {
            $this->setBaseURL($args['BASE_URL']);
        }

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->setCacheLifetime($args['CACHE_LIFETIME']);
        }

        $this->initStreamContext($args);
    }

    public function getResponse() {
        return $this->response->getResponse();
    }

    public function getResponseHeaders() {
        return $this->response->getHeaders();
    }

    public function getResponseStatus() {
        return $this->response->getStatus();
    }

    public function getResponseCode() {
        return $this->response->getCode();
    }

    public function getResponseHeader($header) {
        return $this->response->getHeader($header);
    }

    public function addHeader($header, $value) {
        $this->requestHeaders[$header] = $value;
        $headers = array();
        //@TODO: Might need to escape this
        foreach ($this->requestHeaders as $header=>$value) {
            $headers[] = "$header: $value";
        }
            
        stream_context_set_option($this->streamContext, 'http', 'header', implode("\r\n", $headers));
    }
    
    public function getHeaders() {
        return $this->requestHeaders;
    }

    public function setMethod($method) {
        if (!in_array($method, array('POST','GET','DELETE','PUT'))) {
            throw new KurogoConfigurationException("Invalid method $method");
        }
        
        $this->method = $method;
        stream_context_set_option($this->streamContext, 'http', 'method', $method);
    }

    public function setTimeout($timeout) {
        stream_context_set_option($this->streamContext, 'http', 'timeout', $timeout);
    }
    
    protected function initStreamContext($args) {
        $streamContextOpts = array();
        
        if (isset($args['HTTP_PROXY_URL'])) {
            $streamContextOpts['http'] = array(
                'proxy'          => $args['HTTP_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }
        
        if (isset($args['HTTPS_PROXY_URL'])) {
            $streamContextOpts['https'] = array(
                'proxy'          => $args['HTTPS_PROXY_URL'], 
                'request_fulluri'=> TRUE
            );
        }
        
        $this->streamContext = stream_context_create($streamContextOpts);
    }

    /**
     * Public factory method. This is the designated way to instantiated data controllers. Takes a string
     * for the classname to load and an array of arguments. Subclasses should generally not override this
     * method, but instead override init() to provide initialization behavior
     * @param string $controllerClass the classname to instantiate
     * @param array $args an associative array of arguments that get passed to init() and the data parser
     * @return DataController a data controller object
     */
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();
        Kurogo::log(LOG_DEBUG, "Initializing DataController $controllerClass", "data");

        if (!class_exists($controllerClass)) {
            throw new KurogoConfigurationException("Controller class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf DataController) {
            throw new KurogoConfigurationException("$controllerClass is not a subclass of DataController");
        }

        $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));

        //get global options from the site data_controller section
        $args = array_merge(Kurogo::getOptionalSiteSection('data_controller'), $args);
        $controller->init($args);

        return $controller;
    }
    
    /**
     * Returns the url to use for the request. The default implementation will take the base url and
     * append any filters/parameters as query string parameters. Subclasses can override this method 
     * if a more dynamic method of URL generation is needed.
     * @return string
     */
    protected function url() {
        $url = $this->baseURL;
        if (count($this->filters)>0) {
            $glue = strpos($this->baseURL, '?') !== false ? '&' : '?';
            $url .= $glue . http_build_query($this->filters);
        }
        
        return $url;
    }
    
    /**
     * Parse the data. This method will also attempt to set the total items in a request by calling the
     * data parser's getTotalItems() method
     * @param string $data the data from a request (could be from the cache)
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseData($data, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseData($data);
        $this->setTotalItems($parser->getTotalItems());
        return $parsedData;
    }

    protected function parseResponse(DataResponse $response, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseResponse($response);
        $this->setTotalItems($parser->getTotalItems());
        return $parsedData;
    }

    /**
     * Parse a file. This method will also attempt to set the total items in a request by calling the
     * data parser's getTotalItems() method
     * @param string $file a file containing the contents of the data
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseFile($file, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseFile($file);
        $this->setTotalItems($parser->getTotalItems());
        return $parsedData;
    }
    
    /**
     * Return the parsed data. The default implementation will retrive the data and return value of
     * parseData()
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    public function getParsedData(DataParser $parser=null) {
        if (!$parser) {
            $parser = $this->parser;
        }

        switch ($parser->getParseMode()) 
        {
            case DataParser::PARSE_MODE_STRING:
                $data = $this->getData();
                return $this->parseData($data, $parser);
                break;
        
           case DataParser::PARSE_MODE_FILE:
                $file = $this->getDataFile();
                return $this->parseFile($file, $parser);
                break;

           case DataParser::PARSE_MODE_RESPONSE:
                $this->getData();
                return $this->parseResponse($this->response, $parser);
                break;
            default:
                throw new KurogoConfigurationException("Unknown parse mode");
        }
    }
    
    /**
     * Returns a unix timestamp to use for the cache file. Return null to use the current time. Subclasses
     * can override this method to use a timestamp based on the returning data if appropriate.
     * @param string $data the unparsed data included by the request
     * @return int a unix timestamp or null to use the current time
     */
    protected function cacheTimestamp($data) {
        return null;
    }
    
    /**
     * Returns whether the cache is fresh or not. Subclasses could override this if they implement
     * custom caching 
     * @return bool 
     */
    protected function cacheIsFresh() {
        $cache = $this->getCache();
        return $cache->isFresh($this->cacheFilename());
    }

    /**
     * Returns the cached data based on the cacheFilename() custom caching. Subclasses could override 
     * this if they implement custom caching 
     * @return string 
     */
    protected function getCacheData() {
        $cache = $this->getCache();
        $data = $cache->read($this->cacheFilename());
        if ($response = @unserialize($data)) {
            $this->response = $response;
            return $response->getResponse();
        }
        return null;
    }

    /**
     * Writes the included data to the file based on cacheFilename(). Subclasses could override 
     * this if they implement custom caching 
     * @param string the data to cache
     */
    protected function writeCache($data) {
        $cache = $this->getCache();
        $cache->write($data, $this->cacheFilename(), $this->cacheTimestamp($data));
    }
    
    /**
     * Returns the a DiskCache object for this controller. Subclasses could override this if they
     * need to provide a custom object for caching. It should implement the DiskCache interface
     * @return DiskCache object
    */
    protected function getCache() {
        if ($this->cache === NULL) {
              $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime, TRUE);
              $this->cache->setSuffix('.cache');
              $this->cache->preserveFormat();
        }
        
        return $this->cache;
    }
    
    /**
     * Retrieves the data and saves it to a file. 
     * @return string a file containing the data
     */
    public function getDataFile() {
        $dataFile = $this->cacheFilename() . '-data';
        $cache = $this->getCache();
        if ($this->useCache) {
            if ($cache->isFresh($dataFile)) {
                $data = $cache->read($dataFile);

            } else {
                if ($data = $this->getData()) {
                    $cache->write($data, $dataFile);
                } elseif ($this->useStaleCache) {
                    // return stale cache if the data is unavailable
                    $data = $cache->read($dataFile);
                }
            }
        } else {
            $data = $this->getData();
            $cache->write($data, $dataFile);
        }
        
        return $cache->getFullPath($dataFile);
    }
    
    /**
     * Retrieves the data.  The default implementation will use the url returned by the url() 
     * function. If the cache is still fresh than it will return the data saved in the cache,
     * otherwise it will retrieve the data using the retrieveData() method and save the cache.
     * Subclasses should only need to override this method if an alternative caching scheme is needed.
     * @return string the data
     */
    public function getData() {

        if (!$url = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }

        $this->url = $url;
        $this->totalItems = 0;

        if ($this->useCache) {
            if ($this->cacheIsFresh()) {
                Kurogo::log(LOG_DEBUG, "Using cache for $url", 'data');
                $data = $this->getCacheData();
            } else {
                if ($data = $this->retrieveData($url)) {
                    if ($this->response) {
                        $this->writeCache(serialize($this->response));
                    }
                } elseif ($this->useStaleCache) {
                    // return stale cache if the data is unavailable
                    Kurogo::log(LOG_DEBUG, "Using stale cache for $url", 'data');
                    $data = $this->getCacheData();
                }
            }
        } else {
            $data = $this->retrieveData($url);
        }
        
        return $data;
    }

    /**
     * Retrieves the data using the given url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @param string the url to retrieve
     * @return string the response from the server
     * @TODO support POST requests and custom headers and perhaps proxy requests
     */
    protected function retrieveData($url) {
        Kurogo::log(LOG_INFO, "Retrieving $url", 'data');
        
        $data = file_get_contents($url, false, $this->streamContext);
        $http_response_header = isset($http_response_header) ? $http_response_header : array();

        $this->response = DataResponse::factory('HTTPDataResponse', array());
        $this->response->setRequest($this->method, $url, $this->filters, $this->requestHeaders);
        $this->response->setResponse($data);
        $this->response->setResponseHeaders($http_response_header);
        
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $this->getResponseCode(), strlen($data)), 'data');
        
        return $data;
    }
    
    /**
     * Sets the cache lifetime in seconds. Will be called if the initialization args contains CACHE_LIFETIME
     * @param int seconds to cache results (default for base class is 900 seconds / 15 minutes)
     */
    public function setCacheLifetime($seconds) {
        $this->cacheLifetime = intval($seconds);
    }

    /**
     * Sets the target encoding of the result. Defaults to utf-8.
     * @param string
     */
    public function setEncoding($encoding) {
        $this->parser->setEncoding($encoding);
    }

    /**
     * Returns the target encoding of the result.
     * @return string. Default is utf-8
     */
    public function getEncoding() {
        return $this->parser->getEncoding();
    }
    
    /**
     * Utility function to return a subset of items. Essentially is a robust version of array_slice.
     * @param array items
     * @param int $start 0 indexed value to start
     * @param int $limit how many items to return (use null to return all items beginning at $start)
     * @return array
     */
    protected function limitItems($items, $start=0, $limit=null) {
        $start = intval($start);
        $limit = is_null($limit) ? null : intval($limit);

        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        if (!is_array($items)) {
            throw new KurogoDataException("Items list is not an array");
        }
        
        if ($start>0 || !is_null($limit)) {
            $items = array_slice($items, $start, $limit);
        }
        
        return $items;
        
    }

    /**
     * Returns an item at a particular index
     * @param int index
     * @return mixed the item or false if it's not there
     */
    public function getItemByIndex($index) {
        if ($items = $this->items($index,1)) {
            return current($items); 
        } else {
            return false;
        }
    }
    
    /**
     * Default implementation of items. Will retrieve the parsed items based on the current settings
     * and return a filtered list of items
     * @param int $start 0 based index to start
     * @limit int $limit number of items to return
     */
    public function items($start=0, $limit=null) {
        $items = $this->getParsedData();
        return $this->limitItems($items,$start, $limit);
    }
}

