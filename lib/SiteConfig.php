<?php
/**
  * @package Config
  */

/**
  * @package Config
  */
class SiteConfig extends ConfigGroup {

    private function isValidSiteName($name) {
        return preg_match("/^[a-z][a-z0-9_-]*$/i", $name);
    }
    
  function __construct(&$path) {
    // Load main configuration file
    $config = ConfigFile::factory('kurogo', 'project', ConfigFile::OPTION_IGNORE_MODE | ConfigFile::OPTION_IGNORE_LOCAL);
    $this->addConfig($config);

    define('CONFIG_MODE', $config->getVar('CONFIG_MODE', 'kurogo'));
    define('CONFIG_IGNORE_LOCAL', $config->getVar('CONFIG_IGNORE_LOCAL', 'kurogo'));
    
    //multi site currently only works with a url base of root "/"
    if ($this->getOptionalVar('MULTI_SITE', false, 'kurogo')) {

        // in scripts you can pass the site name to Kurogo::initialize()
        if (PHP_SAPI == 'cli') {
        
            $site = strlen($path)>0 ? $path : $this->getVar('DEFAULT_SITE');

            $siteDir = implode(DIRECTORY_SEPARATOR, array(ROOT_DIR, 'site', $site));
            if (!realpath_exists($siteDir)) {
                die("FATAL ERROR: Site Directory $siteDir not found for site $path");
            }
        } else {        

            $paths = explode("/", $path); // this is url
            $sites = array();
            $siteDir = '';
        
            if (count($paths)>1) {
                $site = $paths[1];
    
                if ($sites = $this->getOptionalVar('ACTIVE_SITES', array(), 'kurogo')) {
                    //see if the site is in the list of available sites
                    if (in_array($site, $sites)) {
                        $siteDir = realpath_exists(implode(DIRECTORY_SEPARATOR, array(ROOT_DIR, 'site', $site)));
                        $urlBase = '/' . $site . '/'; // this is a url
                    }
                } elseif ($this->isValidSiteName($site)) {
    
                    $testPath = implode(DIRECTORY_SEPARATOR, array(ROOT_DIR, 'site', $site));
                    if ($siteDir = realpath_exists($testPath)) {
                        $urlBase = '/' . $site . '/'; // this is a url
                    }
                }
            }
                    
            if (!$siteDir) {
                $site = $this->getVar('DEFAULT_SITE');
                array_splice($paths, 1, 1, array($site, $paths[1]));
                $url = implode("/", $paths);
                header("Location: $url");
                die();
            }
        }
    } else {
        //make sure active site is set    
        if (!$site = $this->getVar('ACTIVE_SITE')) {
            die("FATAL ERROR: ACTIVE_SITE not set");
        }
        
        //make sure site_dir is set and is a valid path
        if (!($siteDir = $this->getVar('SITE_DIR')) || !($siteDir = realpath_exists($siteDir))) {
            die("FATAL ERROR: Site Directory ". $this->getVar('SITE_DIR') . " not found for site " . $site);
        }
        
        if (PHP_SAPI != 'cli') {

            //
            // Get URL base
            //
            if ($urlBase = $config->getOptionalVar('URL_BASE','','kurogo')) {
                $urlBase = rtrim($urlBase,'/').'/';
            } else {
                //extract the path parts from the url
                $pathParts = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));
                $testPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
                $urlBase = '/';
        
                //once the path equals the WEBROOT_DIR we've found the base. This only works with symlinks
                  if (realpath($testPath) != WEBROOT_DIR) {
                    foreach ($pathParts as $dir) {
                          $test = $testPath.$dir.DIRECTORY_SEPARATOR;
                      
                        if (realpath_exists($test)) {
                            $testPath = $test;
                            $urlBase .= $dir.'/';
                            if (realpath($test) == WEBROOT_DIR) {
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    define('SITE_NAME', $site);

    if (PHP_SAPI == 'cli') {
        define('URL_BASE', null);
    } else {
        if (!isset($urlBase)) {
            throw new Exception("URL base not set. Please report the configuration to see why this happened");
        }
        
        define('URL_BASE', $urlBase);
    
        // Strips out the leading part of the url for sites where 
        // the base is not located at the document root, ie.. /mobile or /m 
        // Also strips off the leading slash (needed by device debug below)
        if (isset($path)) {
            // Strip the URL_BASE off the path
            $baseLen = strlen(URL_BASE);
            if ($baseLen && strpos($path, URL_BASE) === 0) {
                $path = substr($path, $baseLen);
            }
        }  
    }

    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $siteDir); //already been realpath'd
    define('SITE_KEY',             md5($siteDir));
    define('SITE_LIB_DIR',         SITE_DIR . DIRECTORY_SEPARATOR . 'lib');
    define('SITE_APP_DIR',         SITE_DIR . DIRECTORY_SEPARATOR . 'app');
    define('SITE_MODULES_DIR',     SITE_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'modules');
    define('DATA_DIR',             SITE_DIR . DIRECTORY_SEPARATOR . 'data');
    define('CACHE_DIR',            SITE_DIR . DIRECTORY_SEPARATOR . 'cache');
    define('LOG_DIR',              SITE_DIR . DIRECTORY_SEPARATOR . 'logs');
    define('SITE_CONFIG_DIR',      SITE_DIR . DIRECTORY_SEPARATOR . 'config');

    //load in the site config file (required);
    $config = ConfigFile::factory('site', 'site');
    $this->addConfig($config);
    
    if ($config->getOptionalVar('SITE_DISABLED')) {
        die("FATAL ERROR: Site disabled");
    }

    // Set up theme define
    if (!$theme = $this->getVar('ACTIVE_THEME')) {
        die("FATAL ERROR: ACTIVE_THEME not set");
    }
    
    define('THEME_DIR', SITE_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme);
  }

}
