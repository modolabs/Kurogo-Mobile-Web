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
 
abstract class ExternalDataController {
    
    protected $DEFAULT_PARSER_CLASS='PassthroughDataParser';
    protected $DEFAULT_RETRIEVE_CLASS='URLDataRetriever';
    protected $initArgs=array();
    protected $cacheFolder='Data';
    protected $retriever;
    protected $cache;
    protected $title;
    protected $totalItems = null;
    protected $debugMode=false;
    protected $useCache=true;
    protected $useStaleCache=true;
    protected $cacheLifetime=900;
    
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
     * Returns a base filename for the cache file that will be used. it will call getCacheKey method 
     * of retriever
     * @return string
     */
    protected function cacheFilename() {
        return $this->retriever->getCacheKey();
    }

    /**
     * Sets the data retriever to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the parser dynamically.
     * @param DataParser a instantiated DataParser object
     */
    public function setRetriever(DataRetriever $retriever) {
        $this->retriever = $retriever;
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
    public function setTotalItems($totalItems) {
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
     * The initialization function. Sets the common parameters based on the $args. This method is
     * called by the public factory method. Subclasses can override this method, but must call parent::init()
     * FIRST. Optional parameters include PARSER_CLASS, BASE_URL, TITLE and CACHE_LIFETIME. Arguments
     * are also passed to the data parser object and the data retieve object
     * @param array $args an associative array of arguments and paramters
     */
    protected function init($args) {
        $this->initArgs = $args;

        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        // use a retriever class if set, otherwise use the default retrieve class from the controller
        $args['RETRIEVER_CLASS'] = isset($args['RETRIEVER_CLASS']) ? $args['RETRIEVER_CLASS'] : $this->DEFAULT_RETRIEVE_CLASS;
        //instantiate the retriever class and add it to the controller
        $retriever = DataRetriever::factory($args['RETRIEVER_CLASS'], $args);
        $retriever->init($args);
        $retriever->setDataController($this);
        $this->setRetriever($retriever);

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->setCacheLifetime($args['CACHE_LIFETIME']);
        }
    }
    
    public function getResponse() {
        return $this->retriever->getResponse();
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
            //$this->response = $response;
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

        $this->totalItems = 0;

        if ($this->useCache) {
            if ($this->cacheIsFresh()) {
                //Kurogo::log(LOG_DEBUG, "Using cache for $url", 'data');
                $data = $this->getCacheData();
            } else {
                if ($data = $this->retriever->retrieveData()) {
                    if ($this->getResponse()) {
                        $this->writeCache(serialize($this->getResponse()));
                    }
                } elseif ($this->useStaleCache) {
                    // return stale cache if the data is unavailable
                    //Kurogo::log(LOG_DEBUG, "Using stale cache for $url", 'data');
                    $data = $this->getCacheData();
                }
            }
        } else {
            $data = $this->retriever->retrieveData();
        }
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
        var_dump($items);
        var_dump("k");
        exit;
        return $this->limitItems($items,$start, $limit);
    }
    
    /**
     * Interceptor. router the method that not exists in this class to the retriverDataController.
     */
    public function __call($method, $arguments) {
        if ($this->retriever && $this->retriever instanceOf DataRetriever) {
            return call_user_func_array(array($this->retriever, $method), $arguments);
        } else {
            throw new KurogoDataException("Call of unknown function '$method'.");
        }
    }
}

