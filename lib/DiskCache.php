<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @copyright 2010 Modo Labs Inc.
  * @package Core
  *
   */

/**
  * Provides an abstraction for an on disk cache
  * @package Core
  *
  */
class DiskCache {

    private $path;
    private $timeout = PHP_INT_MAX;
    private $error;
    private $prefix = "";
    private $suffix = "";
    private $serialize = TRUE;

    public function __construct($path, $timeout=NULL, $mkdir=FALSE) {
        if (empty($path)) {
            throw new KurogoDataException("Invalid path");
        }
    
        $this->path = $path;
    
        if ($mkdir) {
            if (!file_exists($path)) {
                if (!@mkdir($path, 0700, true)) {
                    throw new KurogoDataException("Could not create cache folder $path");
                }
            }
            if (!realpath_exists($path)) {
                throw new KurogoDataException("Path $path is not valid for cache");
            }
        }
        
        if (!is_writable($path)) {
            throw new KurogoDataException("Path $path is not writable");
        }
        
        if ($timeout !== NULL) {
            $this->timeout = $timeout;
        }
    }

  public function preserveFormat() {
    $this->serialize = FALSE;
  }

  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }

  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

  public function getPrefix() {
    return $this->prefix;
  }

  public function getSuffix() {
    return $this->suffix;
  }

  public function setSuffix($suffix) {
    $this->suffix = $suffix;
  }

  public function getError() {
    return $this->error;
  }

  public function getFullPath($filename=NULL) {
    if ($filename === NULL) {
      return $this->path;
    } else {
      return $this->path.'/'.Watchdog::safeFilename($this->prefix.$filename.$this->suffix);
    }
  }

  public function writeImage($image, $filename) {
    $success = FALSE;
    $path = $this->getFullPath($filename);
    $suffix = $this->suffix ? $this->suffix : substr($filename, -4);
    switch ($suffix) {
     case '.png':
       $success = imagepng($image, $path);
       break;
     case '.jpg':
       $success = imagejpeg($image, $path);
       break;
     case '.gif':
       $success = imagegif($image, $path);
       break;
    }
    return $success;
  }

  public function readImage($filename) {
    $path = $this->getFullPath($filename);
    $suffix = $this->suffix ? $this->suffix : substr($filename, -4);
    switch ($suffix) {
     case '.png':
       return imagecreatefrompng($path);
       break;
     case '.jpg':
         return imagecreatefromjpeg($path);
       break;
     case '.gif':
       return imagecreatefromgif($path);
       break;
    }
  }

  public function write($object, $filename=NULL, $date=null) {
    if (!$object) {
      $this->error = "tried to cache a non-object";
    }

    $path = $this->getFullPath($filename);
    $umask = umask(0077);
    Kurogo::log(LOG_DEBUG, "Saving cache to $path", 'cache');
    $fh = fopen($path, 'w');
    if ($fh !== FALSE) {
      if ($this->serialize) {
        fwrite($fh, serialize($object));
      } else {
        fwrite($fh, $object);
      }
      fclose($fh);
      // set the modification time if present
      if ($date) {
        touch($this->getFullPath($filename), $date);
      }
      umask($umask);
      return TRUE;

    } else {
      $this->error = "could not open $path for writing";
    }

    // stop doing this here after users handle error on their own
    if ($this->error)
      Kurogo::log(LOG_WARNING, $this->error, 'data');

    umask($umask);
    return FALSE;
  }

  public function getImageSize($filename=NULL) {
    $path = $this->getFullPath($filename);
    list($width, $height, $type, $attr) = getimagesize($path);
    return array($width, $height);
  }

  public function readIfFresh($filename=NULL) {
    if ($this->isFresh($filename)) {
        return $this->read($filename);
    } 
    
    return FALSE;
  }
  
  public function flush($filename=NULL) {
    $path = $this->getFullPath($filename);
    if (file_exists($path)) {
        Kurogo::log(LOG_DEBUG, "Flushing cache $path", 'cache');
        return unlink($path);
    }
    return false;
  }

  public function read($filename=NULL) {
    $path = $this->getFullPath($filename);
    if (file_exists($path)) {
      if ($contents = file_get_contents($path)) {
        Kurogo::log(LOG_DEBUG, "Reading cache $path", 'cache');
        if ($this->serialize) {
          return unserialize($contents);
        } else {
          return $contents;
        }
      }
      $this->error = "could not get contents of $path";
      Kurogo::log(LOG_WARNING,$this->error, 'data');
    }
    return FALSE;
  }

  public function exists($filename) {
    $path = $this->getFullPath($filename);
    return (file_exists($path) && filesize($path) > 0);
  }

  public function isFresh($filename=NULL, $timeout=NULL) {
    $path = $this->getFullPath($filename);
    if ($timeout === NULL)
      $timeout = $this->timeout;

    // this has to be strictly less than, otherwise
    // for files with null timeout that don't yet exist
    // both will return PHP_INT_MAX and evaluate to true
    return ($this->getAge($filename) < $timeout);
  }

  public function isEmpty($filename=NULL) {
    $path = $this->getFullPath($filename);
    return file_exists($path) && filesize($path) == 0;
  }

  public function getModified($filename) {
    if ($this->exists($filename)) {
      $path = $this->getFullPath($filename);

      //clear_realpath_cache and filename parameters valid starting in 5.3
      if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
          clearstatcache(true, $path);
      } else {
          clearstatcache(); 
      }
      return filemtime($path);
    }
    return null;
  }

  public function getAge($filename=NULL) {
    if ($modified = $this->getModified($filename)) {
        return time() - $modified;    
    }

    return PHP_INT_MAX;
  }

}





