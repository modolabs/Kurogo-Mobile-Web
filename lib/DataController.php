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
abstract class DataController
{
    protected $DEFAULT_PARSER_CLASS='PassthroughDataParser';
    protected $cacheFolder='Data';
    protected $cacheFileSuffix='';
    protected $parser;
    protected $url;
    protected $cache;
    protected $baseURL;
    protected $title;
    protected $filters=array();
    protected $debugMode=false;
    protected $useCache=true;
    protected $cacheLifetime=900;
    
    abstract public function getItem($id);

    protected function cacheFolder()
    {
        return CACHE_DIR . "/" . $this->cacheFolder;
    }
    
    protected function cacheFileSuffix()
    {
        return $this->cacheFileSuffix ? '.' . $this->cacheFileSuffix : '';
    }
    
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }
    
    public function addFilter($var, $value)
    {
        $this->filters[$var] = $value;
        $this->clearInternalCache();
    }

    public function removeFilter($var)
    {
        if (isset($this->filters[$var])) {
            unset($this->filters[$var]);
            $this->clearInternalCache();
        }
    }

    public function removeAllFilters()
    {
        $this->filters = array();
        $this->clearInternalCache();
    }

    protected function clearInternalCache()
    {
    }

    protected function cacheFilename()
    {
        return md5($this->url());
    }

    protected function cacheMetaFile()
    {
        return sprintf("%s/%s-meta.txt", $this->cacheFolder(), md5($this->url()));
    }
    
    public function setParser(DataParser $parser)
    {
        $this->parser = $parser;
    }

    public function setUseCache($useCache)
    {
        $this->useCache = $useCache ? true : false;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }
    
    public function setBaseURL($baseURL)
    {
        $this->baseURL = $baseURL;
        $this->removeAllFilters();
        $this->clearInternalCache();
    }
    
    protected function init($args)
    {
        $args['PARSER_CLASS'] = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : $this->DEFAULT_PARSER_CLASS;
        $parser = DataParser::factory($args['PARSER_CLASS'], $args);
        
        $this->setParser($parser);
        
        if (isset($args['BASE_URL'])) {
            $this->setBaseURL($args['BASE_URL']);
        }

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->setCacheLifetime($args['CACHE_LIFETIME']);
        }
        
    }

    public static function factory($controllerClass, $args=array())
    {
        $args = is_array($args) ? $args : array();

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf DataController) {
            throw new Exception("$controllerClass is not a subclass of DataController");
        }
        
        $controller->init($args);
        
        return $controller;
    }
    
    protected function url()
    {
        $url = $this->baseURL;
        if (count($this->filters)>0) {
            $glue = strpos($this->baseURL, '?') !== false ? '&' : '?';
            $url .= $glue . http_build_query($this->filters);
        }
        
        return $url;
    }
    
    protected function parseData($data, DataParser $parser=null)
    {
        if (!$parser) {
            $parser = $this->parser;
        }
        return $parser->parseData($data);
    }
    
    public function getParsedData(DataParser $parser=null)
    {
        $data = $this->getData();
        return $this->parseData($data, $parser);
    }
    
    public function getData()
    {
        if (!$url = $this->url()) {
            throw new Exception("Invalid URL");
        }

        $this->url = $url;
        if ($this->useCache) {
            $cacheFilename = $this->cacheFilename();
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime, TRUE);
                  $this->cache->setSuffix($this->cacheFileSuffix());
                  $this->cache->preserveFormat();
            }

            if ($this->cache->isFresh($cacheFilename)) {
                $data = $this->cache->read($cacheFilename);
            } else {
                if ($this->debugMode) {
                    error_log(sprintf("Retrieving %s", $url));
                }
                
                if ($data = $this->retrieveData($url)) {
                    $this->cache->write($data, $cacheFilename);
                }
                
                if ($this->debugMode) {
                    file_put_contents($this->cacheMetaFile(), $url);
                }
            }
        } else {
            $data = file_get_contents($url);
        }
        
        return $data;
    }
    
    protected function retrieveData($url) {
        return file_get_contents($url);
    }

    public function setCacheLifetime($seconds)
    {
        $this->cacheLifetime = intval($seconds);
    }

    public function setEncoding($encoding)
    {
        $this->parser->setEncoding($encoding);
    }

    public function getEncoding()
    {
        return $this->parser->getEncoding();
    }
    
    protected function limitItems($items, $start=0, $limit=null)
    {
        $start = intval($start);
        $limit = is_null($limit) ? null : intval($limit);

        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        if (!is_array($items)) {
            throw new Exception("Items list is not an array");
        }
        
        if ($start>0 || !is_null($limit)) {
            $items = array_slice($items, $start, $limit);
        }
        
        return $items;
        
    }

    public function getItemByIndex($index)
    {
        if ($items = $this->items($index,1)) {
            return current($items); 
        } else {
            return false;
        }
    }
    
    public function items($start=0, $limit=null, &$totalItems)
    {
        $items = $this->getParsedData();
        $totalItems = count($items);
        return $this->limitItems($items,$start, $limit);
    }
}

