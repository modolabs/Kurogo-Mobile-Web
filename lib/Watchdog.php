<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class Watchdog {
    private static $_instance = NULL;
    protected $workingRealpath = false;
    protected $kurogoPathDirs = array();
    protected $safePathDirs = array();
    protected $safePathREs = array();

    private function __clone() {}

    protected function __construct() {
        // realpath returns true if the directory exists even if the file doesn't on PHP < 5.3
        $this->workingRealpath = version_compare(PHP_VERSION, '5.3.0') >= 0;
    }

    //
    // Internal implementation of functions to generate lists and
    // regular expressions describing acceptable file paths
    //

    protected function _getKurogoPathDirs() {
        if (!$this->kurogoPathDirs) {
            $rootDir = realpath(ROOT_DIR).DIRECTORY_SEPARATOR;
            $kurogoPathDirs = array($rootDir);

            if (defined('SITES_DIR')) {
                $kurogoPathDirs[] = SITES_DIR . DIRECTORY_SEPARATOR;
            }
            
            if (!defined('SITE_DIR')) {
                return $kurogoPathDirs;
            }
            
            $this->kurogoPathDirs = $kurogoPathDirs;

            
            //site dir is ok
            $siteDir = realpath(SITE_DIR).DIRECTORY_SEPARATOR;
            if (strncmp($siteDir, $rootDir, strlen($rootDir)) != 0) {
                $this->kurogoPathDirs[] = $siteDir;
            }

            //shared dir is also ok            
            if (defined('SHARED_DIR')) {
                $sharedDir = SHARED_DIR . DIRECTORY_SEPARATOR;
                if (strncmp($sharedDir, $rootDir, strlen($rootDir)) != 0) {
                    $this->kurogoPathDirs[] = $sharedDir;
                }
            }
            
            // support for future feature to allow cache to be moved to a fast disk
            $cacheDir = realpath(CACHE_DIR).DIRECTORY_SEPARATOR;
            if (strncmp($cacheDir, $rootDir, strlen($rootDir)) != 0) {
                $this->kurogoPathDirs[] = $cacheDir;
            }
            //error_log('Kurogo files: '.print_r($this->kurogoPathDirs, true));
        }
        
        return $this->kurogoPathDirs;
    }

    protected function _getSafePathDirs() {
        if (!$this->safePathDirs && defined('SITE_DIR')) {
            $this->safePathDirs = array(
                realpath(SITE_DIR).DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR,
                realpath(CACHE_DIR).DIRECTORY_SEPARATOR.'FileLoader'.DIRECTORY_SEPARATOR,
            );
            //error_log('Safe files: '.print_r($this->safePathDirs, true));
        }
        
        return $this->safePathDirs;
    }
    
    // these files may be anywhere
    protected function _getSafeFileNames() {
        return array(
            DIRECTORY_SEPARATOR . 'kurogo.ini',
        );
    }

    protected function _getSafePathREs() {
        if (!$this->safePathREs && defined('SITE_DIR')) {
            $delimiter = ';';
            $this->safePathREs = array(
                $delimiter.
                '^('. preg_quote(realpath(SITE_DIR).DIRECTORY_SEPARATOR, $delimiter).'app|'.
                      preg_quote(realpath(SHARED_DIR).DIRECTORY_SEPARATOR, $delimiter).'app|'.
                      preg_quote(realpath(ROOT_DIR).DIRECTORY_SEPARATOR, $delimiter).'app)'.
                  preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                  '(common|modules'.preg_quote(DIRECTORY_SEPARATOR, $delimiter).'[^'.preg_quote(DIRECTORY_SEPARATOR, $delimiter).']+)'.
                  preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                  '(css|images|javascript)'.
                  preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                $delimiter,
            );
            //error_log('Safe REs: '.print_r($this->safePathREs, true));
        
            if (defined('THEME_DIR') && strlen(THEME_DIR)) {
                $this->safePathREs[] =
                    $delimiter.
                    '^('. preg_quote(realpath(THEME_DIR), $delimiter).'|'.
                          preg_quote(realpath(SHARED_THEME_DIR), $delimiter).')'.        
                      preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                      '(common|modules'.preg_quote(DIRECTORY_SEPARATOR, $delimiter).'[^'.preg_quote(DIRECTORY_SEPARATOR, $delimiter).']+)'.
                      preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                      '(css|images|javascript)'.
                      preg_quote(DIRECTORY_SEPARATOR, $delimiter).
                    $delimiter;
			}
		}
        return $this->safePathREs;
    }

    //
    // Internal implementation of path checking functions
    //

    protected function _safePath($path) {
        // realpath_exists calls Watchdog::kurogoPath()
        $test = realpath($path);
        if ($test && ($this->workingRealpath || file_exists($test))) {
            // Check safe directories first because strncmp is fast
            foreach ($this->_getSafePathDirs() as $dir) {
                if (strncmp($test, $dir, strlen($dir)) == 0) {
                    return $test;
                }
            }
            
            foreach ($this->_getSafePathREs() as $re) {
                if (preg_match($re, $test)) {
                    return $test;
                }
            }
            Kurogo::log(LOG_WARNING, "WARNING! Blocking attempt from ".$_SERVER['REMOTE_ADDR']." to access unsafe path '$test'", 'security');
       }
        
        // path is invalid or refers to an unsafe file
        return false;
    }

    protected function _kurogoPath($path) {
        // realpath_exists calls Watchdog::kurogoPath()
        $test = realpath($path);
        if ($test && ($this->workingRealpath || file_exists($test))) {

            foreach ($this->_getSafeFileNames() as $file) {
                if (substr($test, 0-strlen($file))==$file) {
                    return $test;
                }
            }
            
            foreach ($this->_getKurogoPathDirs() as $dir) {
                if (strncmp($test, $dir, strlen($dir)) == 0) {
                    return $test;
                }
            }
            Kurogo::log(LOG_WARNING, "WARNING! Blocking attempt from ".$_SERVER['REMOTE_ADDR']." to access non-Kurogo path '$test'", 'security');
        }
        
        // path is invalid or outside Kurogo directories
        return false;
    }
    
    protected function _safeFilename($filename) {
        $ext = '';
        $parts = explode('.', $filename);
        if (count($parts) > 1 && ctype_alnum(end($parts))) {
            $ext = array_pop($parts);
            $filename = implode('.', $parts);
        }
        
        // Make sure not to double url encode if the name is already encoded
        $filename = urlencode(urldecode($filename));
        if ($ext) {
            $filename .= ".$ext";
        }
        return $filename;
    }

    //
    // Shared instance of Watchdog class
    //

    protected static function sharedInstance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }

    //
    // Public static helper functions
    //

    public static function safeFilename($filename) {
        return self::sharedInstance()->_safeFilename($filename);
    }

    public static function safePath($path) {
        return self::sharedInstance()->_safePath($path);
    }

    public static function kurogoPath($path) {
        return self::sharedInstance()->_kurogoPath($path);
    }
}
