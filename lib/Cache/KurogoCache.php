<?php

abstract class KurogoCache {

    static $DEFAULT_CACHE_CLASS='KurogoNoCache';
    
	public function __construct() {
	}

	public static function factory($cacheType = '', $args = array()) {
		$args = is_array($args) ? $args : array();

        $cacheType = $cacheType ? $cacheType : self::$DEFAULT_CACHE_CLASS;
        
        if (!class_exists($cacheType)) {
            die("Cache class $cacheType not defined");
            throw new KurogoConfigurationException("Cache class $cacheType not defined");
        }
        $cacheClass = new $cacheType;
        
        if (!$cacheClass instanceOf KurogoCache) {
            throw new KurogoConfigurationException("$cacheType is not a subclass of KurogoCache");
        }

        $cacheClass->init($args);

        return $cacheClass;
	}

	abstract public function get($key);

	abstract public function set($key, $value, $ttl = 0);

	abstract public function delete($key);

	abstract public function clear();
}
