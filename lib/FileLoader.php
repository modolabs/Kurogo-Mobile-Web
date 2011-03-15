<?php

define('AUTOLOAD_FILE_DIR', CACHE_DIR."/FileLoader");

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
                        if (file_put_contents($filePath, $data)) {
                            $path = realpath_exists($filePath);
                            unlink($loaderInfoPath);
                        } else {
                            error_log("FileLoader failed to save data to '$filePath'");
                        }
                    } else {
                        error_log("FileLoader failed to load '{$loaderInfo['url']}'");
                    }
                } else {
                    error_log("FileLoader got invalid loader info");
                }
            } else {
                error_log("FileLoader could not find loader info at '$loaderInfoPath'");
            }
        }
        return $path;
    }
    
    protected static function generateLazyURL($file, $contents, $subdirectory=null) {
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
                error_log("could not create $path");
                return;
            }
        }
        $subPath = $path."/$subdirectory";
        if (!realpath_exists($subPath)) {
            if (!mkdir($subPath, 0755, true)) {
                error_log("could not create $subPath");
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