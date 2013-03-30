<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DataCache 
{
    const CACHE_STATUS_EMPTY=1;
    const CACHE_STATUS_EXPIRED=2;
    const CACHE_STATUS_FRESH=3;
    protected $cacheFolder=CACHE_DIR;
    protected $useMemoryCache = true;
    protected $cacheBaseKey = 'DataCache';
    protected $serialize = true;
    protected $cacheLifetime = 0;
    protected $cacheGroup = null;

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
    
    protected function createCacheFolderIfNeeded() {
        $cacheFolder = $this->getCacheFolder($this->cacheGroup);
        if (!is_dir($cacheFolder)) {
            if (!@mkdir($cacheFolder, 0700, true)) {
                throw new KurogoDataException("Could not create cache folder $cacheFolder");
            }
        }
    }
    
    protected function getCacheFolder($cacheGroup=null) {

        $return = rtrim($this->cacheFolder, DIRECTORY_SEPARATOR);
        
        if ($cacheGroup) {
            $return .= DIRECTORY_SEPARATOR . $this->cacheGroup;
        }

        return $return;
    }
    
    public function setSerialize($serialize) {
        $this->serialize = (bool) $serialize;
    }

    public function setUseMemoaryCache($useMemoryCache) {
        $this->useMemoryCache = (bool) $useMemoryCache;
    }

    public function setCacheLifetime($cacheLifetime) {
        $this->cacheLifetime = intval($cacheLifetime);
    }
    
    public function setCacheGroup($group) {
        $this->cacheGroup = $group;
    }
    
    protected function getValueFromMemory($key) {
        if ($memoryCache = $this->getMemoryCache()) {
            return $memoryCache->get($this->getMemoryCacheKey($key));
        }
        
        return false;
    }
    
    public function cacheStatus($key) {
        $age = $this->getDiskAge($key);
        if (is_null($age)) {
            return self::CACHE_STATUS_EMPTY;
        }
        
        if ($age < $this->cacheLifetime) {
            return self::CACHE_STATUS_FRESH;
        }
        
        return self::CACHE_STATUS_EXPIRED;
    }
    
    protected function getValueFromDisk($key) {
        $path = $this->getFullPath($key);

        if (file_exists($path)) {
            if ($contents = file_get_contents($path)) {
                Kurogo::log(LOG_DEBUG, "Reading cache $path", 'cache');
                if ($this->serialize) {
                    return unserialize($contents);
                } else {
                    return $contents;
                }
            }
        }
        
        return FALSE;
    }

    protected function getDiskAge($key) {
    
        $modified = $this->getModified($key);
        if (!is_null($modified)) {
            return time() - $modified;    
        }
    
        return null;
    }

    public function getModified($key) {
        $path = $this->getFullPath($key);
        if (is_readable($path)) {
            return filemtime($path);
        } else {
            return null;
        }
    }
    
    public function getFullPath($key) {

        $return = $this->getCacheFolder($this->cacheGroup) . DIRECTORY_SEPARATOR . Watchdog::safeFilename($key);
        return $return;
    }

    public function getStaleValue($key) {
        return $this->getValueFromDisk($key);
    }

    public function get($key) {
        if ( ($val = $this->getValueFromMemory($key)) !== false) {
            Kurogo::log(LOG_DEBUG, "Reading $key from memory cache", 'cache');
            return $val;
        }
        
        $val = false;

        switch ($this->cacheStatus($key)) {
            case self::CACHE_STATUS_EMPTY:
                Kurogo::log(LOG_DEBUG, "Cache not available for $key", 'cache');
                break;
            case self::CACHE_STATUS_EXPIRED:
                Kurogo::log(LOG_DEBUG, "Cache expired for $key", 'cache');
                break;
            case self::CACHE_STATUS_FRESH:
                Kurogo::log(LOG_DEBUG, "Reading $key from disk cache", 'cache');
                $val =  $this->getValueFromDisk($key);
                break;
            
        }

        return $val;
    }
    
    protected function getMemoryCache() {
        if ($this->useMemoryCache) {
            return Kurogo::sharedInstance()->cacher();
        }
        return false;
    }
    
    protected function getMemoryCacheKey($key) {
        $return = $this->cacheBaseKey . '-';
        if ($this->cacheGroup) {
            $return .= $this->cacheGroup . '-';
        }
        
        $return .= $key;
        return $return;
    }

    public function delete($key) {
        if ($memoryCache = $this->getMemoryCache()) {
            $memoryCache->delete($this->getMemoryCacheKey($key));
        }
        $this->deleteValueFromDisk($key);
    }

    public function set($key, $data) {

        /* Don't cache on 0 lifetime */
        if ($this->cacheLifetime == 0) {
            return false;
        }
        
        if ($memoryCache = $this->getMemoryCache()) {
            $memoryCache->set($this->getMemoryCacheKey($key), $data, $this->cacheLifetime);
        }
        
        return $this->setValueToDisk($key, $data);
    }

    public function clearCache() {
        $folder = $this->getCacheFolder();
        return Kurogo::rmdir($folder);
    }
    
    public function clearCacheGroup($cacheGroup) {
        if (!$cacheGroup) {
            return false;
        }
        $folder = $this->getCacheFolder($cacheGroup);
        return Kurogo::rmdir($folder);
    }
    
    protected function deleteValueFromDisk($key) {
        $path = $this->getFullPath($key);
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    protected function setValueToDisk($key, $data) {
    
        $this->createCacheFolderIfNeeded();
        $path = $this->getFullPath($key);
        $umask = umask(0077);
        Kurogo::log(LOG_DEBUG, "Saving cache to $path", 'cache');
        $fh = fopen($path, 'w');
        if ($fh !== FALSE) {
            if ($this->serialize) {
                fwrite($fh, serialize($data));
            } else {
                fwrite($fh, $data);
            }
            fclose($fh);
            umask($umask);
            return TRUE;
        }

        umask($umask);
        return false;
    }
        
    public function setCacheFolder($cacheFolder, $create = false) {

        $this->cacheFolder = $cacheFolder;
        if ($create) {
            $this->createCacheFolderIfNeeded();
        }
        
        if (!realpath_exists($this->cacheFolder)) {
            throw new KurogoDataException("Path $this->cacheFolder is not valid for cache");
        }
        
        if (!is_writable($cacheFolder)) {
            throw new KurogoDataException("Path $this->cacheFolder is not writable");
        }
    }
    
        
    protected function init($args) {
        if (isset($args['CACHE_FOLDER']) && !empty($args['CACHE_FOLDER'])) {
            $this->cacheBaseKey = $args['CACHE_FOLDER'];
            $this->setCacheFolder(CACHE_DIR . DIRECTORY_SEPARATOR . $args['CACHE_FOLDER'], true);
        }
        
        if (isset($args['USE_MEMORY_CACHE'])) {
            $this->useMemoryCache = (bool) $args['USE_MEMORY_CACHE'];
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->cacheLifetime = intval($args['CACHE_LIFETIME']);
        }

    }
    
        
}

