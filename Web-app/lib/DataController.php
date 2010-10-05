<?php

abstract class DataController
{
    protected $parser;
    protected $cacheFile;
    protected $cache;
    protected $baseURL;
    protected $filters=array();
    
    abstract protected function cacheFolder();
    abstract protected function cacheLifespan();
    abstract protected function cacheFileSuffix();
    abstract public function getItem($id);
    
    protected function setFilter($var, $value)
    {
        $this->filters[$var] = $value;
    }
    
    protected function cacheFilename()
    {
        return md5($this->url());
    }
    
    public function setParser(DataParser $parser)
    {
        $this->parser = $parser;
    }
    
    public function setBaseURL($baseURL)
    {
        $this->baseURL = $baseURL;
    }
    
    public function __construct($baseURL, DataParser $parser)
    {
        $this->setBaseURL($baseURL);
        $this->setParser($parser);
    }
    
    protected function url()
    {
        $url = sprintf("%s?%s", $this->baseURL, http_build_query($this->filters));
        return $url;
    }
    
    public function parseData($data)
    {
        return $this->parser->parseData($data);
    }
    
    public function getData()
    {
        if ($this->cache === NULL) {
              $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifespan(), TRUE);
              $this->cache->setSuffix($this->cacheFileSuffix());
              $this->cache->preserveFormat();
        }

        if ($this->cache->isFresh($this->cacheFilename())) {
            $data = $this->cache->read($this->cacheFilename());
        } else {
            error_log(sprintf("Retrieving %s", $this->url()));
            $data = file_get_contents($this->url());
            $this->cache->write($data, $this->cacheFilename());
        }
        
        return $data;
    }
    
    public function items()
    {
        $data = $this->getData();
        return $this->parseData($data);
    }
}

