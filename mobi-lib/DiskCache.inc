<?php

class DiskCache {

  private $path;
  private $timeout = PHP_INT_MAX;
  private $prefix = "";
  private $suffix = "";

  public function __construct($path, $timeout=NULL, $mkdir=FALSE) {
    $this->path = $path;

    if ($mkdir) {
      if (!file_exists($path)) {
        if (!mkdir($path, 0755))
          error_log("could not create $path");
      }
    }

    if ($timeout !== NULL)
      $this->timeout = $timeout;
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

  private function getFullPath($filename) {
    if ($filename === NULL) {
      return $this->path;
    } else {
      return $this->path . '/' . $this->prefix . $filename . $this->suffix;
    }
  }

  public function write($object, $filename=NULL) {
    $path = $this->getFullPath($filename);
    if ($fh = fopen($path, 'w')) {
      fwrite($fh, serialize($object));
      fclose($fh);
    } else {
      die("could not open $path for writing");
    }
  }

  public function read($filename=NULL) {
    $path = $this->getFullPath($filename);
    if (file_exists($path) && $this->isFresh($filename)) {
      if ($contents = file_get_contents($path))
        return unserialize($contents);
      error_log("could not get contents of $path");
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
    return (filesize($path) > 0 && $this->getAge($path) < $timeout);
  }

  public function getAge($filename=NULL) {
    if ($this->exists($filename)) {
      $path = $this->getFullPath($filename);
      return time() - filemtime($path);
    }
    return PHP_INT_MAX;
  }

}





