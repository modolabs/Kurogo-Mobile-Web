<?php

define('AUTOLOAD_IMAGES_DIR', CACHE_DIR."/ImageLoader");

class ImageLoader {
  public static function imageDir() {
    return 'imageloader';
  }

  public static function precache($url, $width, $height, $file) {
    $loaderInfo = array(
      'url'  => $url,
      'width' => $width,
      'height' => $height,
    );
    
    $path = AUTOLOAD_IMAGES_DIR;
    if (!realpath_exists($path)) {
      if (!mkdir($path, 0755, true)) {
        error_log("could not create $path");
      }
    }

    if (file_put_contents("$path/$file.needsLoad", json_encode($loaderInfo))) {
      return FULL_URL_BASE.self::imageDir()."/$file";
    
    } else {
      return false;
    }
  }
  

  public static function deleteLDAP($file) {
  	// TODO
  }
  
  public static function loadLDAP($file) {
  	// TODO
  } 
  
  
  
  public static function load($file) {
    $filePath = AUTOLOAD_IMAGES_DIR."/$file";
    
    $path = realpath_exists($filePath);
    if (!$path) {
      $loaderInfoPath = realpath_exists("$filePath.needsLoad");
      if ($loaderInfoPath) {
        $loaderInfo = json_decode(file_get_contents($loaderInfoPath), true);
  
        if (isset($loaderInfo, $loaderInfo['url'])) {
          $imageData = file_get_contents($loaderInfo['url']);
          
          // Do something with width and height here
          
          if ($imageData && file_put_contents($filePath, $imageData)) {
            $path = realpath_exists($filePath);
            unlink($loaderInfoPath);
          } else {
            error_log("ImageLoader failed to load image '{$loaderInfo['url']}'");
          }
        }
      }
    }
    return $path;
  }
}
