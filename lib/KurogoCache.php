<?php
abstract class KurogoCache {

	public function __construct() {
	}

	public static function factory($cacheType, $args = array()) {
		$args = is_array($args) ? $args : array();

		includePackage("Cache/{$cacheType}.php");
        if (!class_exists($cacheType)) {
            throw new KurogoConfigurationException("Cache class $cacheType not defined");
        }
        
        $cacheClass = new $cacheType;
        
        if (!$cacheClass instanceOf KurogoCache) {
            throw new KurogoConfigurationException("$cacheType is not a subclass of KurogoCache");
        }

        $args = array_merge(Kurogo::getOptionalSiteSection('cache'), $args);
        $cacheClass->init($args);

        return $cacheClass;
	}

	abstract public function get($key);

	abstract public function set($key, $value, $ttl = 0);

	abstract public function delete($key);
}
