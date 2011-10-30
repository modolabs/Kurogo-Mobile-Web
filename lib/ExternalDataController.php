<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * 
 * Handles retrieval, caching and parsing of data. 
 * @package ExternalData
 */
Kurogo::includePackage("DataController");
 
abstract class ExternalDataController {
    
    protected $DEFAULT_RETRIEVER_CLASS='URLDataRetriever';
    protected $DEFAULT_PARSER_CLASS = 'PassthroughDataParser';
    protected $RETRIEVER_INTERFACE = 'DataRetriever';
    protected $PARSER_INTERFACE = 'DataParser';
    protected $initArgs=array();
    protected $cacheFolder='Data';
    protected $retriever;
    protected $parser;
    protected $response;
    protected $cache;
    protected $title;
    protected $debugMode=false;
    protected $useCache=true;
    protected $useStaleCache=true;
    protected $options = array();
    protected $cacheLifetime=900;

    /**
     * Returns the folder used to store caches. Subclasses should simply set the $cacheFolder property
	 * @return string
     */
    protected function cacheFolder() {
        return $this->retriever->cacheFolder(CACHE_DIR . DIRECTORY_SEPARATOR . $this->cacheFolder);
    }
    
    /**
     * Turns on or off debug mode. In debug mode, URL requests and information are logged to the php error log
     * @param bool 
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }
   
    /**
     * Returns a base filename for the cache file that will be used. it will call getCacheKey method 
     * of retriever
     * @return string
     */
    protected function cacheFilename() {
        return $this->retriever->getCacheKey();
    }

    protected function setOption($option, $value) {
        $this->options[$option] = $value;
        $this->retriever->setOption($option, $value);
        $this->parser->setOption($option, $value);
    }

    protected function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Sets the data retriever to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the retriever dynamically.
     * @param retriever a instantiated DataRetriever object
     */
    public function setRetriever(DataRetriever $retriever) {
        if ($retriever instanceOf $this->RETRIEVER_INTERFACE) {
            $this->retriever = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $this->RETRIEVER_INTERFACE");
        }
    }
    
    /**
     * Returns the data retriever
     * @return DataRetriever
     */
    public function getRetriever() {
        return $this->retriever;
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
     * The initialization function. Sets the common parameters based on the $args. This method is
     * called by the public factory method. Subclasses can override this method, but must call parent::init()
     * FIRST. Optional parameters include TITLE and CACHE_LIFETIME. Arguments
     * are also passed to the data parser object and the data retieve object
     * @param array $args an associative array of arguments and paramters
     */
    protected function init($args) {
        $this->initArgs = $args;

        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        // use a retriever class if set, otherwise use the default retrieve class from the controller
        $args['RETRIEVER_CLASS'] = isset($args['RETRIEVER_CLASS']) ? $args['RETRIEVER_CLASS'] : $this->DEFAULT_RETRIEVER_CLASS;

        //instantiate the retriever class and add it to the controller
        $retriever = DataRetriever::factory($args['RETRIEVER_CLASS'], $args);
        $this->setRetriever($retriever);
        
        if (!isset($args['PARSER_CLASS'])) {
            //use the retriever parser class if it has a default
            if (!$args['PARSER_CLASS'] = $retriever->getDefaultParserClass()) {

                // otherwise use the controll parser class
                $args['PARSER_CLASS'] = $this->DEFAULT_PARSER_CLASS;
            }
        }
        
        // instantiate the parser class
        $parser = DataParser::factory($args['PARSER_CLASS'], $args);
        $parser->setDataController($this);
        $parser->setDataRetriever($retriever);
        $this->setParser($parser);

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->setCacheLifetime($args['CACHE_LIFETIME']);
        }
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
     * @param string $data the data from a request (could be from the cache)
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseData($data, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseData($data);
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
            $parser = $this->parser;
        }
        $parsedData = $parser->parseFile($file);
        return $parsedData;
    }

    /**
     * Parse the response
     * @param DataResponse $response the DataResponse from a request (could be from the cache)
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseResponse(DataResponse $response, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseResponse($response);
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

        switch ($parser->getParseMode()) {
            case DataParser::PARSE_MODE_STRING:
                $data = $this->getData();
                return $this->parseData($data, $parser);
                break;
        
           case DataParser::PARSE_MODE_FILE:
                $file = $this->getDataFile();
                return $this->parseFile($file, $parser);
                break;

           case DataParser::PARSE_MODE_RESPONSE:
                $response = $this->getResponse();
                return $this->parseResponse($response, $parser);
                break;
            default:
                throw new KurogoConfigurationException("Unknown parse mode");
        }
    }
    
    /**
     * Returns the target encoding of the result.
     * @return string. Default is utf-8
     */
    public function getEncoding() {
        return $this->parser->getEncoding();
    }
    
    
    /**
     * Retrieves the data and saves it to a file. 
     * @return string a file containing the data
     */
    public function getDataFile() {
        $dataFile = $this->cacheFilename() . '-data';
        $data = $this->retrieveData();
        $cache = $this->getCache();
        $cache->write($data, $dataFile);
        return $cache->getFullPath($dataFile);
    }

    
    
    /**
     * Public factory method. This is the designated way to instantiated data controllers. Takes a string
     * for the classname to load and an array of arguments. Subclasses should generally not override this
     * method, but instead override init() to provide initialization behavior
     * @param string $controllerClass the classname to instantiate
     * @param array $args an associative array of arguments that get passed to init()
     * @return DataController a data controller object
     */
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();
        Kurogo::log(LOG_DEBUG, "Initializing ExternalDataController $controllerClass", "data");

        if (!class_exists($controllerClass)) {
            throw new KurogoConfigurationException("ExternalDataController class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf ExternalDataController) {
            throw new KurogoConfigurationException("$controllerClass is not a subclass of ExternalDataController");
        }

        $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));

        //get global options from the site data_controller section
        $args = array_merge(Kurogo::getOptionalSiteSection('data_controller'), $args);
        $controller->init($args);

        return $controller;
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
        if (!$cacheFilename = $this->cacheFilename()) {
            return false;
        }
        return $cache->isFresh($cacheFilename);
    }

    /**
     * Returns the cached data based on the cacheFilename() custom caching. Subclasses could override 
     * this if they implement custom caching 
     * @return string 
     */
    protected function getCachedResponse() {
        $cache = $this->getCache();
        $data = $cache->read($this->cacheFilename());
        if ($response = @unserialize($data)) {
            return $response;
        }
        return new DataResponse();
    }
    
    public function getResponse() {

        if (!$this->response) {
            if ($this->useCache) {
                if ($this->cacheIsFresh()) {
                    $this->response = $this->getCachedResponse();
                } else {
                    $this->response = $this->retriever->retrieveData();
                    if ($this->response->getResponse()) {
                        $this->writeCache($this->response);
                    } elseif ($this->useStaleCache) {
                        $this->response = $this->getCachedResponse();
                    }
                }
            } else {
                $this->response = $this->retriever->retrieveData();
            }
        }
        
        if (!$this->response instanceOf DataResponse) {
            throw new KurogoDataException("Response must be instance of DataResponse");
        }
        
        return $this->response;
    }
    
    public function getResponseError() {
        if ($response = $this->getResponse()) {
            return $response->getResponseError();
        }
    }

    public function getResponseCode() {
        if ($response = $this->getResponse()) {
            return $response->getCode();
        }
    }

    /**
     * Writes the included data to the file based on cacheFilename(). Subclasses could override 
     * this if they implement custom caching 
     * @param string the data to cache
     */
    protected function writeCache($response) {
        $cache = $this->getCache();
        $data = $response;
        if ($response instanceOf DataResponse) {
            $data = serialize($response);
        } elseif (!is_scalar($response)) {
            throw new KurogoException("Invalid response while attempting to save cache");
        }
        
        if ($cacheFilename = $this->cacheFilename()) {
            $cache->write($data, $cacheFilename, $this->cacheTimestamp($data));
        }
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
     * Retrieves the data from the retriever
     * @return string the data
     */
    public function getData() {

        $response = $this->getResponse();
        return $response->getResponse();
    }

    /**
     * Sets the cache lifetime in seconds. Will be called if the initialization args contains CACHE_LIFETIME
     * @param int seconds to cache results (default for base class is 900 seconds / 15 minutes)
     */
    public function setCacheLifetime($seconds) {
        $this->cacheLifetime = intval($seconds);
    }
    
    /**
     * Interceptor. router the method that not exists in this class to the retriverDataController.
     */
    public function __call($method, $arguments) {
        if (is_callable(array($this->retriever, $method))) {
            return call_user_func_array(array($this->retriever, $method), $arguments);
        } else {
            throw new KurogoDataException("Call of unknown function '$method'.");
        }
    }
}

