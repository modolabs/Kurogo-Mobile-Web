<?php

class DataCache 
{
    public static function factory($cacheClass, $args)
    {
        if (!class_exists($cacheClass)) {
            throw new KurogoConfigurationException("Data cache class $cacheClass not defined");
        } 
        
        $cache = new $cacheClass();
        
        if (!$cache instanceOf DataCache) {
            throw new KurogoConfigurationException("$cacheClass is not a subclass of DataCache");
        }
        
        $cache->init($args);
        return $cache;
    }
    
    protected function init($args) {
                        
    }
    
}