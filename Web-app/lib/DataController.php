<?php

abstract class DataController
{
    protected $parser;
    protected $cacheFile;
    protected $cache;
    protected $baseURL;
    protected $filters=array();
    protected $debugMode=false;
    protected $useCache=true;
    
    abstract protected function cacheFolder();
    abstract protected function cacheLifespan();
    abstract protected function cacheFileSuffix();
    abstract public function getItem($id);
    
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }
    
    public function addFilter($var, $value)
    {
        $this->filters[$var] = $value;
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
    
    public function setBaseURL($baseURL)
    {
        $this->baseURL = $baseURL;
    }
    
    public function __construct($baseURL, DataParser $parser)
    {
        $this->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
        $this->setBaseURL($baseURL);
        $this->setParser($parser);
    }
    
    protected function url()
    {
        $url = $this->baseURL;
        if (count($this->filters)>0) {
            $url .= "?" . http_build_query($this->filters);
        }
        
        return $url;
    }
    
    public function parseData($data)
    {
        return $this->parser->parseData($data);
    }
    
    public function getData()
    {
        if ($this->useCache) {
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifespan(), TRUE);
                  $this->cache->setSuffix($this->cacheFileSuffix());
                  $this->cache->preserveFormat();
            }
    
            if ($this->cache->isFresh($this->cacheFilename())) {
                $data = $this->cache->read($this->cacheFilename());
            } else {
                if (!$url = $this->url()) {
                    throw new Exception("Invalid URL");
                }
                
                if ($this->debugMode) {
                    error_log(sprintf("Retrieving %s", $this->url()));
                }
                $data = file_get_contents($this->url());
                $this->cache->write($data, $this->cacheFilename());
                if ($this->debugMode) {
                    file_put_contents($this->cacheMetaFile(), $this->url());
                }
            }
        } else {
            $data = file_get_contents($this->url());
        }
        
        return $data;
    }
    
    public function items()
    {
        $data = $this->getData();
        return $this->parseData($data);
    }
}

