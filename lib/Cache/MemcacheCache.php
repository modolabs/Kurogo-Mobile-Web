<?php

if (!function_exists('memcache_connect')) {
    throw new KurogoException('MemcacheCache requires the Memcache Extension');
}

class MemcacheCache extends KurogoCache {
	private $mem;
	private $compressed;
    protected $description = 'Memcache';

	protected function init($args) {
	    parent::init($args);
	    
		$this->mem = new Memcache;
		if(!isset($args['CACHE_HOST'])) {
			throw new KurogoConfigurationException("Memcache host is not defined");
		}else {
			$host = $args['CACHE_HOST'];
		}
		if(!isset($args['CACHE_PORT'])) {
			$port = 11211;
		}else {
			$port = $args['CACHE_PORT'];
		}
		if(!isset($args['CACHE_PERSISTENT'])) {
			$persistent = false;
		}else {
			$persistent = (boolean) $args['CACHE_PERSISTENT'];
		}

		if(!isset($args['CACHE_TIMEOUT'])) {
			$timeout = 1;
		}else {
			$timeout = (int) $args['CACHE_TIMEOUT'];
		}
		if(isset($args['CACHE_COMPRESSED'])) {
			$this->setCompressed($args['CACHE_COMPRESSED']);
		}else {
			$this->setCompressed(true);
		}
		if(isset($args['CACHE_DEBUG'])) {
			$this->setDebug($args['CACHE_DEBUG']);
		}else {
			$this->setDebug(false);
		}

        if ($persistent) {
            $result = @$this->mem->pconnect($host, $port, $timeout);
        }  else {
            $result = @$this->mem->connect($host, $port, $timeout);
        }
        
        if (!$result) {
            throw new KurogoConfigurationException("Memcache server $host not available");
        }
        
	}

	public function setDebug($debug) {
		if($debug) {
			memcache_debug(true);
		}else {
			memcache_debug(false);
		}
	}

	public function setCompressed($compressed) {
		if($compressed) {
			$this->compressed = MEMCACHE_COMPRESSED;
		}else {
			$this->compressed = false;
		}
	}

	public function get($key) {
		return $this->mem->get($key);
	}

	public function set($key, $value, $ttl = false) {
		if($ttl === false) {
			$ttl = $this->ttl;
		}
		return $this->mem->set($key, $value, $this->compressed, $ttl);
	}

	public function delete($key) {
		return $this->mem->delete($key);
	}

	public function add($key, $value, $ttl = false) {
		if($ttl === false) {
			$ttl = $this->ttl;
		}
		return $this->mem->add($key, $value, $this->compressed, $ttl);
	}

	public function clear() {
		return $this->mem->flush();
	}
}
