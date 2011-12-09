<?php

define('AUTOLOAD_FILE_DIR', CACHE_DIR. DIRECTORY_SEPARATOR ."FileLoader");

class FileLoader {
    public static function fileDir() {
        return 'fileloader';
    }
    
    protected static function subDirectory() {
        return '';
    }
    
    protected static function filePath($file, $subdirectory=null) {
        if (!$subdirectory || strpos($file, $subdirectory) !== false) {
            return AUTOLOAD_FILE_DIR."/$file";
        }
        return AUTOLOAD_FILE_DIR."/$subdirectory/$file";
    }
    
    protected static function fullURL($file, $subdirectory=null) {
        if ($subdirectory) {
            return FULL_URL_BASE.self::fileDir()."/$subdirectory/$file";
        } else {
            return FULL_URL_BASE.self::fileDir()."/$file";
        }
    }
    
    public static function load($file) {
        $filePath = self::filePath($file);
        $path = realpath_exists($filePath);
        if (!$path) {
            $loaderInfoPath = realpath_exists("$filePath.needsLoad");
            if ($loaderInfoPath) {
                $loaderInfo = json_decode(file_get_contents($loaderInfoPath), true);
                // TODO do something with loaderInfo
                // e.g. image width/height
                
                if (isset($loaderInfo, $loaderInfo['url'])) {
                    $data = file_get_contents($loaderInfo['url']);
                    if ($data) {
                        //use a temp file to prevent race conditions
                        $tempFile = $filePath . '.' . uniqid();
                        if (file_put_contents($tempFile, $data)) {
                            
                            if (isset($loaderInfo['processMethod'])) {
                                call_user_func($loaderInfo['processMethod'], $tempFile, $loaderInfo);
                            }

                            rename($tempFile, $filePath);
                            $path = realpath_exists($filePath);
                            unlink($loaderInfoPath);
    
                        } else {
                            Kurogo::log(LOG_WARNING,"FileLoader failed to save data to '$filePath'",'data');
                        }
                    } else {
                        Kurogo::log(LOG_WARNING,"FileLoader failed to load '{$loaderInfo['url']}'",'data');
                    }
                } else {
                    Kurogo::log(LOG_WARNING,"FileLoader got invalid loader info",'data');
                }
            } else {
                Kurogo::log(LOG_WARNING,"FileLoader could not find loader info at '$loaderInfoPath'",'data');
            }
        }
        return $path;
    }
    
    protected static function generateLazyURL($file, $contents, $subdirectory=null) {
    
        if (realpath_exists(self::filePath($file, $subdirectory))) {
            return self::fullURL($file,$subdirectory);
        }
        
        $lazyFile = "$file.needsLoad";
        $lazyURL = self::generateURL($lazyFile, $contents, $subdirectory);
        if ($lazyURL) {
            return self::fullURL($file, $subdirectory);
        } else {
            return false;
        }
    }
    
    protected static function generateURL($file, $contents, $subdirectory=null) {
        $path = AUTOLOAD_FILE_DIR;
        if (!realpath_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                Kurogo::log(LOG_WARNING,"could not create $path",'data');
                return;
            }
        }
        $subPath = $path."/$subdirectory";
        if (!realpath_exists($subPath)) {
            if (!mkdir($subPath, 0755, true)) {
                Kurogo::log(LOG_WARNING,"could not create $subPath",'data');
                return;
            }
        }
        if (file_put_contents(self::filePath($file, $subdirectory), $contents)) {
            return self::fullURL($file, $subdirectory);
        } else {
            return false;
        }
    }
  
}