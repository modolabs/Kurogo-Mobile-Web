<?php

define('ROOT_DIR', dirname(__FILE__).'/..'); 
define('KUROGO_VERSION', '1.1');

/* this is a singleton class */
class Kurogo
{
    private static $_instance = NULL;
    private function __construct() {}
    private function __clone() {}
    protected $libDirs = array();
    protected $config;
    protected $deviceClassifier;
    protected $session;

    public static function getSession() {    
        $Kurogo = self::sharedInstance();
        return $Kurogo->session();  
    }
        
    public function session() {
        $this->addPackage('Session');
        if (!$this->session) {
            $args = Kurogo::getSiteSection('authentication');
        
            //default session class
            $controllerClass = 'SessionFiles';
            
            //maintain previous config compatibility
            if (isset($args['AUTHENTICATION_USE_SESSION_DB']) && $args['AUTHENTICATION_USE_SESSION_DB']) {
                $controllerClass = 'SessionDB';
            }
            
            if (isset($args['AUTHENTICATION_SESSION_CLASS'])) {
                $controllerClass = $args['AUTHENTICATION_SESSION_CLASS'];
            }
            
            $this->session = Session::factory($controllerClass, $args);
        }
        
        return $this->session;
    }    
    
    public static function sharedInstance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }
    
    public static function tempDirectory() {
        return Kurogo::getOptionalSiteVar('TMP_DIR', sys_get_temp_dir());
    }
    
    public static function moduleLinkForItem($moduleID, $object, $options=null) {
        $module = WebModule::factory($moduleID);
        return $module->linkForItem($object, $options);
    }

    public static function moduleLinkForValue($moduleID, $value, Module $callingModule, KurogoObject $otherValue=null) {
        $module = WebModule::factory($moduleID);
        return $module->linkForValue($value, $callingModule, $otherValue);
    }

    public static function searchItems($moduleID, $searchTerms, $limit=null, $options=null) {
        $module = WebModule::factory($moduleID);
        return $module->searchItems($searchTerms, $limit, $options);
    }

    public static function includePackage($packageName) {
        $Kurogo = self::sharedInstance();
        return $Kurogo->addPackage($packageName);
    }
    
    public function addPackage($packageName) {

        if (!preg_match("/^[a-zA-Z0-9]+$/", $packageName)) {
            throw new Exception("Invalid Package name $packageName");
        }
    
        $found = false;
        
        $dirs = array(LIB_DIR . "/$packageName");
        if (defined('SITE_LIB_DIR')) {  
            $dirs[] = SITE_LIB_DIR . "/$packageName";
        }
    
        foreach ($dirs as $dir) {
            if (in_array($dir, $this->libDirs)) {
                $found = true;
                continue;
            }
    
            if (is_dir($dir)) {
                $found = true;
                $this->libDirs[] = $dir;
    
                if (is_file("$dir.php")) {
                    include_once("$dir.php");
                }
            }
        }
        
        if (!$found) {
            throw new Exception("Unable to load package $packageName");
        }
    }    
    
   /**
     * This function defines a autoloader that is run when a class needs to be instantiated but the corresponding
     * file has not been loaded. Files MUST be named with the same name as its class
     * currently it will search:
     * 1. If the className has Module in it, it will search the MODULES_DIR
     * 2. The SITE_LIB_DIR  (keep in mind that some files may manually include the LIB_DIR class
     * 3. The LIB_DIR 
     * 
     */
    public function siteLibAutoloader($className) {
        //error_log("Attempting to autoload $className");
        $paths = $this->libDirs;
        
        // If the className has Module in it then use the modules dir
        if (defined('MODULES_DIR') && preg_match("/(.*)(Web|API)Module/", $className, $bits)) {
            $paths[] = MODULES_DIR . '/' . strtolower($bits[1]);
        }
        
        // use the site lib dir if it's been defined
        if (defined('SITE_LIB_DIR')) {
            $paths[] = SITE_LIB_DIR;
        }
        
        $paths[] = LIB_DIR;
        
        foreach ($paths as $path) {
            $file = "$path/$className.php";
            if (file_exists($file)) {
              //error_log("Autoloader found $file for $className");
              include($file);
              return;
            }
        }
        return;
    }
    
    public static function siteTimezone() {
        return Kurogo::sharedInstance()->getTimezone();        
    }

    public function getTimezone() {
        return $this->timezone;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public static function siteConfig() {
        return Kurogo::sharedInstance()->getConfig();        
    }

    public function getDeviceClassifier() {
        return $this->deviceClassifier;
    }

    public static function deviceClassifier() {
        return Kurogo::sharedInstance()->getDeviceClassifier();        
    }
    
    public function initialize(&$path=null) {
      //
      // Constants which cannot be set by config file
      //
      
      define('WEBROOT_DIR',       realpath(ROOT_DIR.'/www')); 
      define('LIB_DIR',           realpath(ROOT_DIR.'/lib'));
      define('MASTER_CONFIG_DIR', realpath(ROOT_DIR.'/config'));
      define('APP_DIR',           realpath(ROOT_DIR.'/app'));
      define('MODULES_DIR',       realpath(APP_DIR.'/modules'));
      define('MIN_FILE_PREFIX', 'file:');
      define('API_URL_PREFIX', 'rest');
      
      //
      // Pull in functions to deal with php version differences
      //
      
      require(LIB_DIR . '/compat.php');
    
      spl_autoload_register(array($this, "siteLibAutoloader"));
      
      //
      // Load configuration files
      //    
      
      $this->config = new SiteConfig();
      ini_set('display_errors', $this->config->getVar('DISPLAY_ERRORS'));
      if (!ini_get('error_log')) {
         ini_set('error_log', LOG_DIR . '/php_error.log');
      }
    
      $timezone = $this->config->getVar('LOCAL_TIMEZONE');
      date_default_timezone_set($timezone);
      $this->timezone = new DateTimeZone($timezone);
      
      //
      // And a double quote define for ini files (php 5.1 can't escape them)
      //
      define('_QQ_', '"');

      //
      // everything after this point only applies to network requests 
      //
      if (PHP_SAPI == 'cli') {
          return;
      }
    
      //
      // Set up host define for server name and port
      //
      
      $host = $_SERVER['SERVER_NAME'];
      if (isset($_SERVER['HTTP_HOST']) && strlen($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        
      } else if (isset($_SERVER['SERVER_PORT'])) {
        $host .= ":{$_SERVER['SERVER_PORT']}";
      }
      define('SERVER_HOST', $host);
    
      //
      // Get URL base
      //
      
      $pathParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI'])));
      
      $testPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
      $urlBase = '/';
      $foundPath = false;
      if (realpath($testPath) != WEBROOT_DIR) {
        foreach ($pathParts as $dir) {
          $test = $testPath.$dir.DIRECTORY_SEPARATOR;
          
          if (realpath_exists($test)) {
            $testPath = $test;
            $urlBase .= $dir.'/';
            if (realpath($test) == WEBROOT_DIR) {
              $foundPath = true;
              break;
            }
          }
        }
      }
      define('URL_BASE', $foundPath ? $urlBase : '/');
      define('IS_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
      define('FULL_URL_BASE', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_BASE);
      define('COOKIE_PATH', URL_BASE); // We are installed under URL_BASE

      // make sure host is all lower case
      if ($host != strtolower($host)) {
        $url = 'http'.(IS_SECURE ? 's' : '').'://' . strtolower($host) . $path;
        header("Location: $url");
        exit();
      }
    
    
      //
      // Install exception handlers
      //
      require(LIB_DIR.'/exceptions.php');
      
      if ($this->config->getVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
        set_exception_handler("exceptionHandlerForProduction");
      } else {
        set_exception_handler("exceptionHandlerForDevelopment");
      }
      
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
    
      //
      // Initialize global device classifier
      //
      
      $device = null;
      $urlPrefix = URL_BASE;
      $urlDeviceDebugPrefix = '/';
      
      // Check for device classification in url and strip it if present
      if ($this->config->getVar('DEVICE_DEBUG') && 
          preg_match(';^device/([^/]+)/(.*)$;', $path, $matches)) {
        $device = $matches[1];  // layout forced by url
        $path = $matches[2];
        $urlPrefix .= "device/$device/";
        $urlDeviceDebugPrefix .= "device/$device/";
      }
      
      define('URL_DEVICE_DEBUG_PREFIX', $urlDeviceDebugPrefix);
      define('URL_PREFIX', $urlPrefix);
      define('FULL_URL_PREFIX', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_PREFIX);
      define('KUROGO_IS_API', preg_match("#^" .API_URL_PREFIX . "/#", $path));
          
      //error_log(__FUNCTION__."(): prefix: $urlPrefix");
      //error_log(__FUNCTION__."(): path: $path");
      $this->deviceClassifier = new DeviceClassifier($device);
      
      //preserved for compatibility
      $GLOBALS['deviceClassifier'] = $this->deviceClassifier;
    }
    
    public static function getLanguages() {
        return array(
            'en'=>'English'
        );
    }

    public static function getLifetimeOptions() {
        return array(
            ""    =>'Default',
            0     =>'None',
            10    =>'10 seconds',
            30    =>'30 seconds',
            60    =>'1 minute',
            120   =>'2 minutes',
            300   =>'5 minutes',
            600   =>'10 minutes',
            900   =>'15 minutes',
            1800  =>'30 minutes',
            3600  =>'1 hour',
            7200  =>'2 hours',
            10800 =>'3 hours',
            21600 =>'6 hours',
            43200 =>'12 hours',
            86400 =>'1 day',
            604800 =>'1 week',
            1209600=>'2 weeks',
            2419200=>'4 weeks',
            15552000=>'180 days',
            31536000=>'1 year'
        );
    }
    
    public static function getHashAlgos() {
        return array_combine(hash_algos(), hash_algos());
    }
    
    public static function getSiteVar($var, $section=null) {
        return Kurogo::siteConfig()->getVar($var, $section);
    }

    public static function getOptionalSiteVar($var, $default='', $section=null) {
        return Kurogo::siteConfig()->getOptionalVar($var, $default, $section);
    }

    public static function getSiteSection($section) {
        return Kurogo::siteConfig()->getSection($section);
    }

    public static function getOptionalSiteSection($section) {
        return Kurogo::siteConfig()->getOptionalSection($section);
    }

    /**
      * Returns a string from the site configuration (strings.ini)
      * @param string $var the key to retrieve
      * @param string $default an optional default value if the key is not present
      * @return string the value of the string or the default 
      */
    public static function getSiteString($var) {
        static $config;
        if (!$config) {
            $config = ConfigFile::factory('strings', 'site');
        }
        
        return $config->getVar($var);
    }
    
    public static function getOptionalSiteString($var, $default='') {
        static $config;
        if (!$config) {
            $config = ConfigFile::factory('strings', 'site');
        }
        
        return $config->getOptionalVar($var, $default);
    }

    public static function getSiteAccessControlListArrays() {
        $acls = array();
        foreach (self::getSiteAccessControlLists() as $acl) {
            $acls[] = $acl->toArray();
        }
        return $acls;
    }

    public static function getSiteAccessControlLists() {
        $config = ConfigFile::factory('acls', 'site', ConfigFile::OPTION_CREATE_EMPTY);
        $acls = array();
        
        foreach ($config->getSectionVars() as $aclArray) {
            if ($acl = AccessControlList::createFromArray($aclArray)) {
                $acls[] = $acl;
            }
        }
        
        return $acls;
    }
    
    public function checkCurrentVersion() {
        $url = "https://modolabs.com/kurogo/checkversion.php?" . http_build_query(array(
            'version'=>KUROGO_VERSION,
            'base'=>FULL_URL_BASE,
            'site'=>SITE_KEY,
            'php'=>phpversion(),
            'uname'=>php_uname("a")
            
        ));
        return trim(file_get_contents($url));
    }
    
    private function rmdir($dir) {
        if (strlen($dir) && is_dir($dir)) {
            if (is_file('/bin/rm')) {
                $exec = sprintf("%s -rf %s", '/bin/rm', escapeshellarg($dir));
                exec($exec, $output, $retval);
                return $retval;
            } else {
                throw new Exception("Cannot find a folder removal tool for this platform. Please report this and include your server operating system and version");
            }
        } else {
            return 1;
        }
    }
    
    public function clearCaches($type=null) {

        if (strlen($type)>0) {
            return $this->rmdir(CACHE_DIR . "/" . $type);
        }
    
        //clear all folders
        
        //exclue session folder
        $excludeDirs = array('session','UserData','.','..');
        $dirs = scandir(CACHE_DIR);
        foreach ($dirs as $dir) {
            if ( is_dir(CACHE_DIR."/$dir") && !in_array($dir, $excludeDirs)) {
                $result = $this->rmdir(CACHE_DIR . "/" . $dir);
                if ($result !==0) {
                    return $result;
                }
            }
        }
        
        return 0;
    }
}

interface KurogoObject 
{
}

/* retained for compatibility */
function includePackage($packageName) {
    Kurogo::includePackage($packageName);
}
