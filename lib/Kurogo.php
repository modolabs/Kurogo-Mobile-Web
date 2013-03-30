<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

//
// Constants which cannot be set by config file
//
define('KUROGO_VERSION', '1.8.3');

define('ROOT_DIR', realpath(dirname(__FILE__).'/..'));
define('ROOT_BASE_DIR', realpath(dirname(__FILE__).'/../..'));
define('WEBROOT_DIR',        ROOT_DIR  . DIRECTORY_SEPARATOR . 'www');
define('LIB_DIR',            ROOT_DIR  . DIRECTORY_SEPARATOR . 'lib');
define('APP_DIR',            ROOT_DIR  . DIRECTORY_SEPARATOR . 'app');
define('MODULES_DIR',        APP_DIR   . DIRECTORY_SEPARATOR . 'modules');
define('SCRIPTS_DIR',        ROOT_DIR . DIRECTORY_SEPARATOR . 'scripts');
define('MIN_FILE_PREFIX',  'file-');
define('API_URL_PREFIX',   'rest'); 
define('KUROGO_REQUIRED_VERSION', '5.3.0');

if (version_compare(PHP_VERSION, KUROGO_REQUIRED_VERSION, '<')) {
    die('Kurogo requires at least PHP version ' . KUROGO_REQUIRED_VERSION . '. You have version ' . PHP_VERSION);
}

//
// And a double quote define for ini files (php 5.1 can't escape them)
//
define('_QQ_', '"');

/* this is a singleton class */
class Kurogo
{
    private static $_instance = NULL;
    private function __clone() {}
    protected $charset='UTF-8';
    protected $configClass = 'ConfigFile';
    protected $startTime;
    protected $libDirs = array();
    protected $site;
    protected $baseConfigStore;
    protected $siteConfigStore;
    protected $configModes = array();
    protected $themeConfig;
    protected $deviceClassifier;
    protected $session;
    protected $logger;
    protected $timezone;
    protected $locale;
    protected $languages=array();
    protected $args=array();
    protected $cookies=array();
    protected $cacher;
    protected $module;
    protected $moduleID;
    protected $configModule;
    protected $request;
    protected $serverHost;
    protected $multiSite = false;
    protected $error_reporting = array();
    protected $contexts;

    const REDIRECT_PERMANENT = 301;
    const REDIRECT_TEMPORARY = 302;
    const REDIRECT_SEE_OTHER = 303;

    private function __construct() {
        $this->startTime = microtime(true);
    }

    public static function KurogoUserAgent() {
        return "Kurogo Server v" . KUROGO_VERSION;
    }
    
    protected function setConfigMode($configMode) {
        if ($configMode && !in_array($configMode, $this->configModes)) {
            Kurogo::log(LOG_DEBUG,"Adding config mode $configMode", 'config');
            $this->configModes[] = $configMode;
        }
    }
    
    public static function getConfigModes() {
        return self::sharedInstance()->configModes;
    }

    public function setCurrentModuleID($moduleID) {
        $this->moduleID = $moduleID;
    }

    public function setCurrentConfigModule($configModule) {
        $this->configModule = $configModule;
    }

    public function setCurrentModule(Module $module) {
        $this->module = $module;
        $this->moduleID = $module->getID();
        $this->configModule = $module->getConfigModule();
    }

    public function setRequest($id, $page, $args) {
        $this->request = array(
            'id'=>$id,
            'page'=>$page,
            'args'=>$args
        );
        //moduleID is used by the autoloader
        $this->moduleID = $id;
    }

    public function getCurrentModule() {
        return $this->module;
    }

    public function getCurrentModuleID() {
        return $this->moduleID;
    }

    public static function getArrayForRequest() {
        $Kurogo = Kurogo::sharedInstance();
        return $Kurogo->request;
    }

    public static function getElapsed() {
        $Kurogo = self::sharedInstance();
        return microtime(true) - $Kurogo->startTime;
    }

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

    public static function tempFile($prefix='kgo') {
        $tempDir = self::tempDirectory();
        if (!is_writable($tempDir)) {
            throw new KurogoConfigurationException("Temporary directory $tempDir not available");
        }

        $umask = umask(0177);
        $tempFile = tempnam($tempDir, $prefix);
        umask($umask);

        return $tempFile;
    }

    public static function moduleLinkForItem($moduleID, $object, $options=null) {
        $args = self::sharedInstance()->getArgs();
        $module = WebModule::factory($moduleID, null, $args);
        return $module->linkForItem($object, $options);
    }

    public static function moduleLinkForValue($moduleID, $value, Module $callingModule, $otherValue=null) {
        $args = self::sharedInstance()->getArgs();
        $module = WebModule::factory($moduleID, null, $args);
        return $module->linkForValue($value, $callingModule, $otherValue);
    }

    public static function searchItems($moduleID, $searchTerms, $limit=null, $options=null) {
        $args = self::sharedInstance()->getArgs();
        $module = WebModule::factory($moduleID, null, $args);
        return $module->searchItems($searchTerms, $limit, $options);
    }

    public static function includePackage($packageName, $subpackageName=null) {
        $Kurogo = self::sharedInstance();
        return $Kurogo->addPackage($packageName, $subpackageName);
    }

    public function addPackage($packageName, $subpackageName=null) {
        // allow Package/Subpackage string
        if (preg_match("#([a-zA-Z0-9]+)/([a-zA-Z0-9]+)#", $packageName, $bits)) {
            $packageName = $bits[1];
            $subpackageName = $bits[2];
        }

        if (!preg_match("/^[a-zA-Z0-9]+$/", $packageName)) {
            throw new KurogoConfigurationException("Invalid Package name $packageName");
        }

        if ($subpackageName !== null) {
            if (!preg_match("/^[a-zA-Z0-9]+$/", $subpackageName)) {
                throw new KurogoConfigurationException("Invalid Subpackage name $packageName");
            }
            $packageName .= DIRECTORY_SEPARATOR.$subpackageName;
        }
        $found = false;

        $dirs = array(LIB_DIR . "/$packageName");
        if (defined('SHARED_LIB_DIR')) {
            $dirs[] = SHARED_LIB_DIR . "/$packageName";
        }
        if (defined('SITE_LIB_DIR')) {
            $dirs[] = SITE_LIB_DIR . "/$packageName";
        }

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                self::log(LOG_INFO, "Adding package $packageName", "autoLoader");
                $found = true;
                if ($this->addLibDir($dir)) {
                    //load Package.php if the package hasn't already been loaded
                    if (is_file("$dir.php")) {
                        include_once("$dir.php");
                    }
                }
            }
        }

        if (!$found) {
            throw new KurogoConfigurationException("Unable to load package $packageName");
        }
    }

    public static function includeModulePackage($packageName) {
        $Kurogo = self::sharedInstance();
        return $Kurogo->addModulePackage($packageName);
    }

    public function addModulePackage($packageName) {
    
        if (!$this->moduleID) {
            throw new KurogoException("Cannot call addModulePackage before a module is loaded");
        }

        if (!preg_match("/^[a-zA-Z0-9]+$/", $packageName)) {
            throw new KurogoException("Invalid Package name $packageName");
        }

        $dirs  = array(
            implode('/', array(MODULES_DIR, $this->moduleID, 'lib', $packageName)),
            implode('/', array(SITE_MODULES_DIR, $this->moduleID, 'lib', $packageName))
        );
        
        $found = false;
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                self::log(LOG_INFO, "Adding module package $this->moduleID/$packageName", "autoLoader");
                $found = true;
                if ($this->addLibDir($dir)) {
                    //load Package.php if the package hasn't already been loaded
                    if (is_file("$dir.php")) {
                        include_once("$dir.php");
                    }
                }
            }
        }

        if (!$found) {
            throw new KurogoConfigurationException("Unable to load package $packageName for module $this->moduleID");
        }
    }

    
    protected function getLibDirs() {
        return $this->libDirs;
    }
    
    public function addLibDir($dir) {
        if (!in_array($dir, $this->libDirs) && is_dir($dir)) {
            self::log(LOG_INFO, "Adding lib dir $dir", "autoLoader");
            $this->libDirs[] = $dir;
            return true;
        }
        
        //it's already there
        return false;
    }
    
    public static function addModuleLib($id) {
        if (defined('MODULES_DIR')) {
            $dir = implode('/', array(MODULES_DIR, $id, 'lib'));
            self::sharedInstance()->addLibDir($dir);
        }

        if (defined('SHARED_MODULES_DIR')) {
            $dir = implode('/', array(SHARED_MODULES_DIR, $id, 'lib'));
            self::sharedInstance()->addLibDir($dir);
        }

        if (defined('SITE_MODULES_DIR')) {
            $dir = implode('/', array(SITE_MODULES_DIR, $id, 'lib'));
            self::sharedInstance()->addLibDir($dir);
        }
    }

   /**
     * This function defines a autoloader that is run when a class needs to be instantiated but the corresponding
     * file has not been loaded. Files MUST be named with the same name as its class
     * currently it will search:
     * 1. If the className has Module in it, it will search the MODULES_DIR
     * 2. The SITE_LIB_DIR   (keep in mind that some files may manually include the LIB_DIR class)
     * 3. The SHARED_LIB_DIR (keep in mind that some files may manually include the LIB_DIR class)
     * 4. The LIB_DIR
     *
     */

    public function siteLibAutoloader($className) {
        if ($classPath = Kurogo::getCache('autoload-' . $className)) {
            include($classPath);
            return ;
        }
        $paths = $this->getLibDirs();

        // If the className has Module in it then use the modules dir
        if (defined('MODULES_DIR') && preg_match("/(.+)(Web|API|Shell)Module$/", $className, $bits)) {
            $paths[] = MODULES_DIR . '/' . strtolower($bits[1]);
        }

        // use the shared lib dir if it's been defined
        if (defined('SHARED_LIB_DIR')) {
            $paths[] = SHARED_LIB_DIR;
        }

        // use the site lib dir if it's been defined
        if (defined('SITE_LIB_DIR')) {
            $paths[] = SITE_LIB_DIR;
        }

        $paths[] = LIB_DIR;
        self::log(LOG_DEBUG, "Autoloader loading $className", "autoLoader");
        foreach ($paths as $path) {
            $file = "$path/$className.php";
            // self::log(LOG_DEBUG, "Autoloader looking for $file for $className", "autoLoader");
            if (file_exists($file)) {
                self::log(LOG_INFO, "Autoloader found $file for $className", "autoLoader");
                Kurogo::setCache('autoload-' . $className, $file);
                include($file);
                return;
            }
        }
        return;
    }

    public static function isValidSiteName($name) {
        return preg_match("/^[a-z][a-z0-9_-]*$/i", $name);
    }

    public static function siteTimezone() {
        return Kurogo::sharedInstance()->getTimezone();
    }

    public function getTimezone() {
        return $this->timezone;
    }

    public static function getHost() {
        return self::sharedInstance()->_getHost();
    }
    public function _getHost() {
        return $this->site->getHost();
    }

    public function getSite() {
        return $this->site;
    }
    
    public static function getSiteConfig($area, $opts=0) {
        return self::sharedInstance()->getConfig($area, 'site', $opts);
    }

    public function saveConfig(Config $config) {
        return $this->siteConfigStore->saveConfig($config);
    }
    
    public function getConfig($area, $type, $opts=0) {
        $module = null;
        if ($type instanceOf Module) {
            $module = $type;
            $type = $module->getConfigModule();
        }
        $key = $type . '-' . $area;
        if (isset($this->configs[$key])) {
            return $this->configs[$key];
        }
        
        if ($config = $this->siteConfigStore->loadConfig($area, $type, $opts)) {
            $this->configs[$key] = $config;
        }
        return $config;
    }
    
    public function getConfigStore() {
        return $this->siteConfigStore;
    }

    public static function getModuleConfig($area, $configModule, $opts=0) {
        return self::sharedInstance()->getConfig($area, $configModule, $opts);
    }

    public function getDeviceClassifier() {
        if (!$this->deviceClassifier) {
            $this->deviceClassifier = new DeviceClassifier();
        } 
        return $this->deviceClassifier;
    }

    public static function deviceClassifier() {
        return Kurogo::sharedInstance()->getDeviceClassifier();
    }

    public static function isWindows() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public static function isLocalhost() {
        return isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));
    }

    private static function checkIP($ip) {
        if (!empty($ip) && ip2long($ip)!=-1 && ip2long($ip)!=false) {
            $private_ips = array (
                array('0.0.0.0','2.255.255.255'),
                array('10.0.0.0','10.255.255.255'),
                array('127.0.0.0','127.255.255.255'),
                array('169.254.0.0','169.254.255.255'),
                array('172.16.0.0','172.31.255.255'),
                array('192.0.2.0','192.0.2.255'),
                array('192.168.0.0','192.168.255.255'),
                array('255.255.255.0','255.255.255.255')
            );

            foreach ($private_ips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public static function determineIP() {

        if (isset($_SERVER['HTTP_CLIENT_IP']) && self::checkIP($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
                if (self::checkIP(trim($ip))) {
                    return $ip;
                }
            }
        }

        if (isset($_SERVER['HTTP_X_FORWARDED']) && self::checkIP($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && self::checkIP($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
            return $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && self::checkIP($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && self::checkIP($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER["REMOTE_ADDR"];
        }

        return false;
    }

    public static function file_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_OK:
                return self::getLocalizedString('UPLOAD_ERR_OK');
            case UPLOAD_ERR_INI_SIZE:
                return self::getLocalizedString('UPLOAD_ERR_INI_SIZE', ini_get('upload_max_filesize'));
            case UPLOAD_ERR_FORM_SIZE:
                return self::getLocalizedString('UPLOAD_ERR_FORM_SIZE');
            case UPLOAD_ERR_PARTIAL:
                return self::getLocalizedString('UPLOAD_ERR_PARTIAL');
            case UPLOAD_ERR_NO_FILE:
                return self::getLocalizedString('UPLOAD_ERR_NO_FILE');
            case UPLOAD_ERR_NO_TMP_DIR:
                return self::getLocalizedString('UPLOAD_ERR_NO_TMP_DIR');
            case UPLOAD_ERR_CANT_WRITE:
                return self::getLocalizedString('UPLOAD_ERR_CANT_WRITE');
            case UPLOAD_ERR_EXTENSION:
                return self::getLocalizedString('UPLOAD_ERR_EXTENSION');
            default:
                return self::getLocalizedString('UPLOAD_ERR_UNKNOWN');
        }
    }

    public static function getAvailableLocales() {
        static $locales=array();
        if ($locales) {
            return $locales;
        }

        if (file_exists('/usr/bin/locale')) {
            exec('/usr/bin/locale -a', $locales, $retval);
            if ($retval!==0) {
                throw new KurogoException("Error retrieving locale values");
            }
        } else {
            throw new KurogoException("Unable to find list of locales on this platform");
        }

        return $locales;
    }

    public function getLocale() {
        return $this->locale;
    }

    public function getSystemLocale() {
        return setLocale(LC_ALL,"");
    }
    
    public function setArgs(array $args) {
        $this->args = $args;
    }

    public function getArgs() {
        return $this->args;
    }
    
    public function setLocale($locale) {
        if ($this->isWindows()) {
            throw new KurogoConfigurationException("Setting locale in Windows is not supported at this time");
        }

        // this is platform dependent.
        if (!$return = setLocale(LC_TIME, $locale)) {
            throw new KurogoConfigurationException("Unknown locale setting $locale");
        }
        $this->locale = $return;
        return $this->locale;
    }

    private function logger() {
        if (!$this->logger && $this->timezone && defined('LOG_DIR')) {
            require_once(LIB_DIR . '/KurogoLog.php');
            $this->logger = new KurogoLog();
            $logFilename = $this->getOptionalSiteVar('KUROGO_LOG_FILENAME', "kurogo.log");
            $logFile = LOG_DIR . DIRECTORY_SEPARATOR . $logFilename;
            $this->logger->setLogFile($logFile);
            $this->logger->setDefaultLogLevel($this->getOptionalSiteVar('DEFAULT_LOGGING_LEVEL', LOG_WARNING));
            if (($loggingLevels = $this->getOptionalSiteVar('LOGGING_LEVEL')) && is_array($loggingLevels)) {
                foreach ($loggingLevels as $area=>$level) {
                    $this->logger->setLogLevel($area, $level);
                }
            }
        }

        return $this->logger;
    }

    public function cacher() {
        return $this->cacher;
    }

    public static function getCache($key) {
        if (!defined('SITE_NAME')) {
            return false;
        }

        $key = SITE_NAME . '-' . $key;
        if ($cacher = Kurogo::sharedInstance()->cacher()) {
            return $cacher->get($key);
        }

        // in the early stages the cacher is not available.
        // Kurogo::log(LOG_DEBUG, "Cacher not available for $key", 'cache');
        return false;
    }

    public static function setCache($key, $value, $ttl = null) {
        if (!defined('SITE_NAME')) {
            return false;
        }

        $key = SITE_NAME . '-' . $key;
        if ($cacher = Kurogo::sharedInstance()->cacher()) {
            $logValue = is_scalar($value) ? $value : gettype($value);
            Kurogo::log(LOG_DEBUG, "Setting $key to $logValue", 'cache');
            return $cacher->set($key, $value, $ttl);
        }
        return false;
    }

    public static function deleteCache($key) {
        if (!defined('SITE_NAME')) {
            return false;
        }

        $key = SITE_NAME . '-' . $key;
        if ($cacher = Kurogo::sharedInstance()->cacher()) {
            return $cacher->delete($key);
        }
        return false;
    }

    public static function clearCache() {
        if ($cacher = Kurogo::sharedInstance()->cacher()) {
            return $cacher->clear();
        }
        return false;
    }

    public function setDefaultLogLevel($level) {
        $logger = $this->logger();
        $logger->setDefaultLogLevel($level);
    }

    public function setLogLevel($area, $level) {
        $logger = $this->logger();
        $logger->setLogLevel($area, $level);
    }


    public static function log($priority, $message, $area, $backtrace=null) {
        static $deferredLogs = array();
        $logger = Kurogo::sharedInstance()->logger();

        if (!$logger) {
            if ($priority <= LOG_WARNING) {
                // we need to log early messages so they go SOMEWHERE
                error_log($message);
            }
            //this is a really early log before we have setup the config environment
            $args = func_get_args();
            $args[] = debug_backtrace();
            $deferredLogs[] = $args;
            return;
        }

        // replay the deferred logs.
        if ($deferredLogs) {
            foreach ($deferredLogs as $args) {
                $logger->log($args[0], $args[1], $args[2], $args[3]);
            }
            $deferredLogs = array();
        }

        return $logger->log($priority, $message, $area, $backtrace);
    }
    
    public static function isRequestSecure() {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        }
        return false;
    }

    public function initialize(&$path=null) {
        includePackage('Cache');
        includePackage('Config');

        require(LIB_DIR.'/compat.php');
        require(LIB_DIR.'/exceptions.php');

        // add autoloader
        spl_autoload_register(array($this, "siteLibAutoloader"));

        //
        // Set up host define for server name and port
        //
        $host = self::arrayVal($_SERVER, 'SERVER_NAME', null);
        // SERVER_NAME never contains the port, while HTTP_HOST may (but we're not using HTTP_HOST for security reasons)
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] !== '80') {
              $host .= ":{$_SERVER['SERVER_PORT']}";
        }

        // It's possible (under apache at least) for SERVER_NAME to contain a comma separated list.
        if(strpos($host, ',') !== false)
        {
            self::log(LOG_DEBUG, "Got multiple hostnames in SERVER_NAME: $host", 'kurogo');
            $host_explode = explode(',', $host);
            // Only sane choice is to use the first one.
            $host = $host_explode[0];
        }

        define('SERVER_HOST', $host);
        self::log(LOG_DEBUG, "Setting server host to $host", "kurogo");

        define('IS_SECURE', self::isRequestSecure());
        define('HTTP_PROTOCOL', IS_SECURE ? 'https' : 'http');
        

        $this->baseConfigStore = new ConfigFileStore();

        // Load main configuration file.
        $kurogoConfig = $this->loadKurogoConfig();

        // get CONFIG_MODE from environment if available.
        $configMode = Kurogo::arrayVal($_SERVER, 'CONFIG_MODE', null);
        if ($configMode = $kurogoConfig->getOptionalVar('CONFIG_MODE', $configMode, 'kurogo')) {
            $this->setConfigMode($configMode);
        }
        
        if ($cacheClass = $kurogoConfig->getOptionalVar('CACHE_CLASS','', 'cache')) {
            $this->cacher = KurogoMemoryCache::factory($cacheClass, $kurogoConfig->getOptionalSection('cache'));
        }        

        // get SITES_DIR from environment if available.
        $_sitesDir = Kurogo::arrayVal($_SERVER, 'SITES_DIR', ROOT_BASE_DIR . DIRECTORY_SEPARATOR . 'sites');
        $_sitesDir = $kurogoConfig->getOptionalVar('SITES_DIR', $_sitesDir , 'kurogo');
        if (!$sitesDir = realpath($_sitesDir)) {
            throw new KurogoConfigurationException("SITES_DIR $_sitesDir does not exist");
        }
        
        define('SITES_DIR',          $sitesDir);

        //
        // Initialize Site
        //
        $this->initSite($path);

        $this->setCharset($this->getOptionalSiteVar('DEFAULT_CHARSET', 'UTF-8'));

        ini_set('default_charset', $this->charset());
        ini_set('display_errors', $this->getSiteVar('DISPLAY_ERRORS'));
        if (!ini_get('error_log')) {
            ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'php_error.log');
        }

        define('KUROGO_IS_API', preg_match("#^" .API_URL_PREFIX . "/#", $path));

        //
        // Install exception handlers
        //

        if ($this->getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
            set_exception_handler("exceptionHandlerForProduction");
        } else {
            set_exception_handler("exceptionHandlerForDevelopment");
        }

        //get timezone from config and set
        $timezone = $this->getSiteVar('LOCAL_TIMEZONE');
        date_default_timezone_set($timezone);
        $this->timezone = new DateTimeZone($timezone);
        self::log(LOG_DEBUG, "Setting timezone to $timezone", "kurogo");

        if ($locale = $this->getOptionalSiteVar('LOCALE')) {
            $this->setLocale($locale);
        } else {
            $this->locale = $this->getSystemLocale();
        }

        if ($languages = $this->getOptionalSiteVar('LANGUAGES')) {
        	$this->setLanguages($languages);
        } else {
        	$this->setLanguages(array('en_US'));
        }

        //
        // everything after this point only applies to http requests
        //
        if (PHP_SAPI == 'cli') {
        	define('FULL_URL_BASE','');
            return;
        }

        define('FULL_URL_BASE', 'http'.(IS_SECURE ? 's' : '').'://'.$this->_getHost().URL_BASE);
        define('COOKIE_PATH', URL_BASE);

        // make sure host is all lower case
        if ($this->_getHost() != strtolower($this->_getHost())) {
            $url = 'http'.(IS_SECURE ? 's' : '').'://' . strtolower($this->_getHost()) . $path;
            self::log(LOG_INFO, "Redirecting to lowercase url $url", 'kurogo');
            Kurogo::redirectToURL($url, Kurogo::REDIRECT_PERMANENT);
          }

        //
        // Initialize global device classifier
        //

        $device = null;
        $deviceCacheTimeout = self::getSiteVar('DEVICE_DETECTION_COOKIE_LIFESPAN', 'cookies');
        $urlPrefix = URL_BASE;
        $urlDeviceDebugPrefix = '/';
        $override = null;

        if (isset($_GET['resetdevice'])) {
            DeviceClassifier::clearDeviceCookie();
        }

        if (isset($_GET['setdevice'])) {
            $device = $_GET['setdevice'];
            $override = 1;
            $deviceCacheTimeout = 0;
        }

        // Check for device classification in url and strip it if present
        if ($this->getSiteVar('DEVICE_DEBUG')) {
            if (preg_match(';^device/([^/]+)/(.*)$;', $path, $matches)) {
                $device = $matches[1];  // layout forced by url
                $path = $matches[2];
                $urlPrefix .= "device/$device/";
                $urlDeviceDebugPrefix .= "device/$device/";
                $deviceCacheTimeout = null;
            } elseif (isset($_GET['_device']) && preg_match(';^device/([^/]+)/$;', $_GET['_device'], $matches)) {
                $device = $matches[1];
                $urlPrefix .= "device/$device/";
                $urlDeviceDebugPrefix .= "device/$device/";
                $deviceCacheTimeout = null;
            }
        }

        define('URL_DEVICE_DEBUG_PREFIX', $urlDeviceDebugPrefix);
        define('URL_PREFIX', $urlPrefix);
        self::log(LOG_DEBUG, "Setting URL_PREFIX to " . URL_PREFIX, "kurogo");
        define('FULL_URL_PREFIX', 'http'.(IS_SECURE ? 's' : '').'://'.$this->_getHost().URL_PREFIX);

        $this->checkCurrentVersion();
        $this->deviceClassifier = new DeviceClassifier($device, $deviceCacheTimeout, $override);

        //preserved for compatibility
        $GLOBALS['deviceClassifier'] = $this->deviceClassifier;
        
        $this->initTheme();
    }
    
    private function initContexts() {
        Kurogo::log(LOG_DEBUG, 'Initializing contexts', 'context');
        $this->contexts = array();
        $this->activeContexts = array();

        $contexts = $this->getOptionalSiteSections('contexts');
        $contextGroups = array();
        foreach ($contexts as $key=>$contextData) {
            $context = UserContext::factory($key, $contextData);
            $this->contexts[$context->getID()] = $context;
        }
        Kurogo::log(LOG_DEBUG, 'Contexts: ' . implode(', ', array_keys($this->activeContexts)), 'context');
    }
    
    public function setUserContext($key) {
        if ($context = $this->getContext($key)) {
            if ($context->setContext(true)) {
                $this->initContexts();
                return true;
            } else {
                throw new KurogoException("Context $key cannot be set");
            }
            
        } else {
            throw new KurogoException("Context $key not found");
        }
    }
    
    public function getContext($key) {
        if (!isset($this->contexts)) {
            $this->initContexts();
        }

        return Kurogo::arrayVal($this->contexts, $key);
    }

    public function getContexts() {
        if (!isset($this->contexts)) {
            $this->initContexts();
        }
                
        return $this->contexts;
    }
    
    public function getActiveContexts() {
        if (!isset($this->contexts)) {
            $this->initContexts();
        }
        $activeContexts = array();
        foreach ($this->contexts as $id=>$context) {
            if ($context->isActive() && $context->getID()!=UserContext::CONTEXT_DEFAULT) {
                $activeContexts[$id] = $context;
            }
        }
        
        return $activeContexts;
    }

    protected function addContext(UserContext $context) {
    }

    private function loadSites() {
        //load sites.ini file
        $path = SITES_DIR . DIRECTORY_SEPARATOR . 'sites.ini';
        $sitesConfig = $this->baseConfigStore->loadConfig($path, 'file', Config::OPTION_IGNORE_SHARED);

        $sitesData = $sitesConfig->getSectionVars();
        if (count($sitesData)==0) {
            throw new KurogoConfigurationException("No valid sites configured");
        }

        $sharedData = Kurogo::arrayVal($sitesData, 'shared', array());
        unset($sitesData['shared']);

        if (!defined('SERVER_KEY')) {
            $serverKey = Kurogo::arrayVal($sharedData, 'SERVER_KEY', md5(SITES_DIR));
            define('SERVER_KEY', $serverKey);
        }
        
        $sites = array();
        $multiSite = null;
        $default = null;
        foreach ($sitesData as $siteName=>$siteData) {
            // [shared] section will be applied as common settings for all sites
            $siteData = array_merge($sharedData, $siteData);
        
            $site = new KurogoSite($siteName, $siteData);
            if ($site->isDefault()) {
                if (strlen($default)) {
                    throw new KurogoConfigurationException("Only 1 site can be labeled default, sites $siteName and $default set to default");
                }
                $default = $siteName;
            }
            
            $sites[$site->getName()] = $site;
        }
        
        $this->multiSite = count($sites) > 1;
        return $sites;
    }
    
    public function getSites() {
        $sites = $this->loadSites();
        return $sites;
    }
    
    //attempt to load kurogo.ini at several known paths
    private function loadKurogoConfig() {
        $paths = array(
            '/etc/kurogo/kurogo.ini',
            implode(DIRECTORY_SEPARATOR, array(ROOT_BASE_DIR, 'sites', 'kurogo.ini')),
            implode(DIRECTORY_SEPARATOR, array(ROOT_BASE_DIR, 'sites', 'kurogo-local.ini')),
            implode(DIRECTORY_SEPARATOR, array(ROOT_DIR, 'config', 'kurogo-local.ini')),
        );
        
        foreach ($paths as $path) {
            try {
                if ($kurogoConfig = $this->baseConfigStore->loadConfig($path, 'file', Config::OPTION_IGNORE_MODE | Config::OPTION_IGNORE_LOCAL | Config::OPTION_IGNORE_SHARED | Config::OPTION_DO_NOT_CREATE)) {
                    break;
                }
            } catch (KurogoConfigurationNotFoundException $e) {
            }
        }
        
        if (!$kurogoConfig instanceOf Config) {
            //give a shout out if they are on a old setup
            if (file_exists(implode(DIRECTORY_SEPARATOR, array(ROOT_DIR,'config','kurogo.ini')))) {
                die("The location of the kurogo.ini file has changed. Please see kurogo.org/docs");
            }

            $kurogoConfig = new EmptyConfig('kurogo', 'kurogo');
        }
        
        return $kurogoConfig;
    }
    
    private function initSite(&$path) {
    
        $sites = $this->loadSites();
		if (count($sites)==1) {
            //there's only one site
            $site = current($sites);
            $urlBase = rtrim($site->getUrlBase(),'/') . '/';
        } elseif (PHP_SAPI == 'cli') {

            //path contains -site parameter if included, otherwise use the default site
            $site = null;
			foreach ($sites as $_site) {
				if ( (strlen($path) && $_site->getName()==$path) ||
				     (strlen($path)==0 && $_site->isDefault())) {
					$site = $_site;
					break;
				}
            }

			if (!$site) {
				if (strlen($path)) {
					throw new KurogoConfigurationException("Site $path is not defined in sites.ini");
				} else {
					throw new KurogoConfigurationException("Unable to determine default site");
				}
			}
            
        } else {
            //multiple sites. A score will be returned. The highest score will be the default
            $scores = array();
            foreach ($sites as $i=>$site) {
            	$scores[$i] = $site->getSiteScore($path, SERVER_HOST);
            }
            $topScores = array_keys($scores, max($scores));
            if (count($topScores)==1) {
            	
				$site = $sites[current($topScores)];
				$urlBase = rtrim($site->getUrlBase(),'/') . '/';
                    
				if (strncmp($path, $urlBase, strlen(rtrim($urlBase,'/'))) !==0) {
					if (strlen($path)) {
						$this->redirectToURL(rtrim($urlBase,'/') . '/' . ltrim($path,'/'));
					}
				}
			} elseif ($path) {
                _404();
			} else {
				throw new KurogoException("Site could not be determined. You should have a default site configured");
			}
        }

        if (!$site instanceOf KurogoSite) {
            throw new KurogoException("Site not determined. This is a bug in Kurogo. Please report your sites.ini");
        }        
        
        $siteDir = realpath($site->getSiteDir());
        if (!is_dir($siteDir)) {
            throw new KurogoConfigurationException("Site Directory $siteDir not found for site " . $site->getName());
        }
        
        $this->site = $site;
        if ($cacher = $site->cacher()) {
            $this->cacher = $cacher;
        }        
        if ($configMode = $site->getConfigMode()) {
            $this->setConfigMode($configMode);
        }


        define('SITE_NAME', 		 $site->getName());
        define('SHARED_DIR',         $site->getSharedDir());
        define('SHARED_LIB_DIR',     SHARED_DIR . DIRECTORY_SEPARATOR . 'lib');
        define('SHARED_APP_DIR',     SHARED_DIR . DIRECTORY_SEPARATOR . 'app');
        define('SHARED_MODULES_DIR', SHARED_APP_DIR . DIRECTORY_SEPARATOR . 'modules');
        define('SHARED_DATA_DIR',    SHARED_DIR . DIRECTORY_SEPARATOR . 'data');
        define('SHARED_CONFIG_DIR',  SHARED_DIR . DIRECTORY_SEPARATOR . 'config');
        define('SHARED_SCRIPTS_DIR', SHARED_DIR . DIRECTORY_SEPARATOR . 'scripts');

        
        if (PHP_SAPI == 'cli') {
            define('URL_BASE', null);
        } else {
               
            if ($site->getURLBaseAuto()) { 
				//verify url base
				// @TODO this likely does not make virtual directories (IIS) work yet.
				$webroot = $_SERVER['DOCUMENT_ROOT'] . $urlBase;
	
				if (realpath($webroot) != WEBROOT_DIR) {
					if (count($sites) == 1) {
						throw new KurogoConfigurationException("URL base ($urlBase) does not match detected webroot (" . $webroot . "). Please update sites.ini");
					}
				}
			}

            define('URL_BASE', $urlBase);

            Kurogo::log(LOG_DEBUG,"Setting site to " . $site->getName() . " with a base of $urlBase", 'kurogo');

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
        define('SITE_LIB_DIR',         $site->getSiteLibDir());
        define('SITE_APP_DIR',         $site->getSiteAppDir());
        define('SITE_MODULES_DIR',     $site->getSiteModulesDir());
        define('DATA_DIR',             $site->getDataDir());
        define('WEB_BRIDGE_DIR',       $site->getWebBridgeDir());
        define('CACHE_DIR',            $site->getCacheDir());
        define('LOG_DIR',              $site->getLogDir());
        define('SITE_CONFIG_DIR',      $site->getConfigDir());
        define('SITE_DISABLED_DIR',    $site->getConfigDisabledDir());
        define('SITE_SCRIPTS_DIR',     $site->getScriptsDir());

        $this->siteConfigStore =  $site->getConfigStore(); 

        define('REDIRECT_ON_EXCEPTIONS', $this->getOptionalSiteVar('REDIRECT_ON_EXCEPTIONS', true, 'error_handling_and_debugging'));

        // attempt to load site key
        $siteKey = $this->getOptionalSiteVar('SITE_KEY', md5($siteDir));
        define('SITE_KEY', $siteKey);
        define('SITE_VERSION', $this->getSiteVar('SITE_VERSION', 'site settings'));
        define('SITE_BUILD', $this->getSiteVar('SITE_BUILD', 'site settings'));

        if ($this->getOptionalSiteVar('SITE_DISABLED')) {
            throw new KurogoConfigurationException("Site disabled");
        }
        
        return $site;

      }
      
      protected function initTheme() {
        // Set up theme define
        if ($theme = $this->getOptionalSiteVar('ACTIVE_THEME')) {
            $themeDir = SITE_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme;
            define('THEME_DIR', $themeDir);
            define('SHARED_THEME_DIR', SHARED_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme);
    
            Kurogo::log(LOG_DEBUG,"Setting theme to $theme.", 'kurogo');
        } else {
            define('THEME_DIR', null);
            define('SHARED_THEME_DIR', null);
        }
      }

    public static function getCharset() {
        return Kurogo::sharedInstance()->charset();
    }

    public function setCharset($charset) {
        $this->charset = $charset;
    }

    public function charset() {
        return $this->charset;
    }

    public static function encrypt($string, $key=SITE_KEY) {
        if (strlen($string)==0) {
            return $string;
        }

        if (!function_exists('mcrypt_encrypt')) {
            throw new KurogoException("mcrypt functions not available");
        }

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
    }

    public static function decrypt($encrypted, $key=SITE_KEY) {
        if (strlen($encrypted)==0) {
            return $encrypted;
        }

        if (!function_exists('mcrypt_decrypt')) {
            throw new KurogoException("mcrypt functions not available");
        }

        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
    }

    public function getLanguages() {
        return $this->languages;
    }

    public function setLanguages($languages) {
    	$validLanguages = self::getAvailableLanguages();
    	if (is_array($languages)) {
    		$this->languages = array();
    		foreach ($languages as $language) {
    			if (!array_key_exists($language, $validLanguages)) {
    				throw new KurogoConfigurationException("Invalid language $language");
    			}
    			$this->languages[] = $language;
    		}
    	} elseif (array_key_exists($languages, $validLanguages)) {
			$this->languages[] = $languages;
    	} else {
			throw new KurogoConfigurationException("Invalid language $languages");
		}

		if (!in_array('en_US', $this->languages)) {
		    $this->languages[] = "en_US"; // always include english US
		}
    }

    public static function getAvailableLanguages() {
		return array(
			'af_ZA'=>'Afrikaans',
			'am_ET'=>'አማርኛ',
			'be_BY'=>'Беларуская',
			'bg_BG'=>'български език',
			'ca_ES'=>'Català',
			'cs_CZ'=>'čeština',
			'da_DK'=>'Dansk',
			'de_AT'=>'Deutsch (Österreich)',
			'de_CH'=>'Deutsch (Schweiz)',
			'de_DE'=>'Deutsch (Deutschland)',
			'el_GR'=>'Ελληνικά',
			'en_AU'=>'English (Australia)',
			'en_CA'=>'English (Canada)',
			'en_GB'=>'English (United Kingdom)',
			'en_IE'=>'English (Ireland)',
			'en_NZ'=>'English (New Zealand)',
			'en_US'=>'English (United States)',
			'es_ES'=>'Español',
			'et_EE'=>'Eesti',
			'eu_ES'=>'Euskara',
			'fi_FI'=>'Suomi',
			'fr_BE'=>'Français (Belgique)',
			'fr_CA'=>'Français (Canada)',
			'fr_CH'=>'Français (Suisse)',
			'fr_FR'=>'Français (France)',
			'he_IL'=>'עברית',
			'hr_HR'=>'Hrvatski',
			'hu_HU'=>'Magyar',
			'hy_AM'=>'Հայերեն',
			'is_IS'=>'Íslenska',
			'it_CH'=>'Italiano (Svizzera)',
			'it_IT'=>'Italiano (Italia)',
			'ja_JP'=>'日本語',
			'kk_KZ'=>'Қазақ тілі',
			'ko_KR'=>'한국어',
			'lt_LT'=>'Lietuvių',
			'nl_BE'=>'Vlaams',
			'nl_NL'=>'Nederlands',
			'no_NO'=>'Norsk',
			'pl_PL'=>'Polski',
			'pt_BR'=>'Português (Brasil)',
			'pt_PT'=>'Português',
			'ro_RO'=>'Română',
			'ru_RU'=>'Pусский',
			'sk_SK'=>'Slovenčina',
			'sl_SI'=>'Slovenščina',
			'sr_YU'=>'Cрпски',
			'sv_SE'=>'Svenska',
			'tr_TR'=>'Türkçe',
			'uk_UA'=>'Yкраїнська',
			'zh_CN'=>'简体中文',
			'zh_TW'=>'繁體中文'
		);
	}

    public static function getLifetimeOptions() {
        return array(
            ""    => self::getLocalizedString('TIMEOUT_DEFAULT'),
            0     => self::getLocalizedString('TIMEOUT_NONE'),
            10    => self::getLocalizedString('TIMEOUT_SECONDS', 10),
            30    => self::getLocalizedString('TIMEOUT_SECONDS', 30),
            60    => self::getLocalizedString('TIMEOUT_MINUTE', 1),
            120   => self::getLocalizedString('TIMEOUT_MINUTES', 2),
            300   => self::getLocalizedString('TIMEOUT_MINUTES', 5),
            600   => self::getLocalizedString('TIMEOUT_MINUTES', 10),
            900   => self::getLocalizedString('TIMEOUT_MINUTES', 15),
            1800  => self::getLocalizedString('TIMEOUT_MINUTES', 30),
            3600  => self::getLocalizedString('TIMEOUT_HOUR', 1),
            7200  => self::getLocalizedString('TIMEOUT_HOURS', 2),
            10800 => self::getLocalizedString('TIMEOUT_HOURS', 3),
            21600 => self::getLocalizedString('TIMEOUT_HOURS', 6),
            43200 => self::getLocalizedString('TIMEOUT_HOURS', 12),
            86400 => self::getLocalizedString('TIMEOUT_DAY', 1),
            604800 => self::getLocalizedString('TIMEOUT_WEEK', 1),
            1209600=> self::getLocalizedString('TIMEOUT_WEEKS', 2),
            2419200=> self::getLocalizedString('TIMEOUT_WEEKS', 4),
            15552000=> self::getLocalizedString('TIMEOUT_DAYS', 180),
            31536000=> self::getLocalizedString('TIMEOUT_YEAR', 1)
        );
    }

    public static function getHashAlgos() {
        return array_combine(hash_algos(), hash_algos());
    }
    
    /** Config **/
    public static function configStore() {
        return self::sharedInstance()->siteConfigStore;
    }

    public static function getModuleSections($area, $module, $applyContexts=Config::IGNORE_CONTEXTS) {
        return self::sharedInstance()->siteConfigStore->getSections($area, $module, $applyContexts);
    }

    public static function getOptionalModuleSections($area, $module, $applyContexts=Config::IGNORE_CONTEXTS) {
        return self::sharedInstance()->siteConfigStore->getOptionalSections($area, $module, $applyContexts);
    }

    public static function getModuleSection($section, $module, $area='module') {
        return self::sharedInstance()->siteConfigStore->getSection($section, $area, $module);
    }

    public static function getOptionalModuleSection($section, $module, $area='module') {
        return self::sharedInstance()->siteConfigStore->getOptionalSection($section, $area, $module);
    }

    public static function getModuleVar($var, $module, $section=null, $area='module') {
        return self::sharedInstance()->siteConfigStore->getVar($var, $section, $area, $module);
    }

    public static function getOptionalModuleVar($var, $default='', $module, $section=null, $area='module') {
        return self::sharedInstance()->siteConfigStore->getOptionalVar($var, $default, $section, $area, $module);
    }
    
    public static function getSiteSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        return self::sharedInstance()->siteConfigStore->getSections($area, 'site', $applyContexts);
    }

    public static function getOptionalSiteSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        return self::sharedInstance()->siteConfigStore->getOptionalSections($area, 'site', $applyContexts);
    }

    public static function getSiteSection($section, $area='site') {
        return self::sharedInstance()->siteConfigStore->getSection($section, $area, 'site');
    }

    public static function getOptionalSiteSection($section, $area='site') {
        return self::sharedInstance()->siteConfigStore->getOptionalSection($section, $area, 'site');
    }

    public static function getSiteVar($var, $section=null, $area='site') {
        return self::sharedInstance()->siteConfigStore->getVar($var, $section, $area, 'site');
    }

    public static function getOptionalSiteVar($var, $default='', $section=null, $area='site') {
        return self::sharedInstance()->siteConfigStore->getOptionalVar($var, $default, $section, $area, 'site');
    }

    /**
      * Returns a string from the site configuration
      * @param string $var the key to retrieve
      * @param string $default an optional default value if the key is not present
      * @return string the value of the string
      */
    public static function getSiteString($var) {
        return self::sharedInstance()->siteConfigStore->getVar($var, 'strings', 'site', 'site');
    }

    public static function getOptionalSiteString($var, $default='') {
        return self::sharedInstance()->siteConfigStore->getOptionalVar($var, $default, 'strings', 'site', 'site');
    }

    public static function getSiteAccessControlListArrays() {
        $acls = array();
        foreach (self::getSiteAccessControlLists() as $acl) {
            $acls[] = $acl->toArray();
        }
        return $acls;
    }

    public static function getSiteAccessControlLists() {
        $aclData = self::getOptionalSiteSections('acls');
        $acls = array();

        foreach ($aclData as $aclArray) {
            if ($acl = AccessControlList::createFromArray($aclArray)) {
                $acls[] = $acl;
            }
        }

        return $acls;
    }

    private function getStringsForLanguage($lang) {
        $stringFiles = array(
            APP_DIR . "/common/strings/".$lang . '.ini',
            SHARED_APP_DIR . "/common/strings/".$lang . '.ini',
            SITE_APP_DIR . "/common/strings/".$lang . '.ini'
        );

        if ($this->module) {
            $stringFiles[] = MODULES_DIR . '/' . $this->module->getID() ."/strings/".$lang . '.ini';
            $stringFiles[] = SHARED_MODULES_DIR . '/' . $this->module->getID() ."/strings/".$lang . '.ini';
            $stringFiles[] = SITE_MODULES_DIR . '/' . $this->module->getID() ."/strings/".$lang . '.ini';
            
            if ($this->module->getID() != $this->module->getConfigModule()) {
                $stringFiles[] = SITE_MODULES_DIR . '/' . $this->module->getConfigModule() ."/strings/".$lang . '.ini';
            }
        }

        $strings = array();
        foreach ($stringFiles as $stringFile) {
            if (is_file($stringFile)) {
                $_strings = parse_ini_file($stringFile);
                $strings = array_merge($strings, $_strings);
            }
        }

        return $strings;
    }

    private function processString($string, $opts) {
        if (!is_array($opts)) {
            return $string;
        } else {
            return vsprintf($string, $opts);
        }
    }

    private function getStringForLanguage($key, $lang, $opts) {
        if (!isset($this->strings[$lang])) {
            $this->strings[$lang] = $this->getStringsForLanguage($lang);
        }

        return isset($this->strings[$lang][$key]) ? $this->processString($this->strings[$lang][$key], $opts) : null;
    }

    public static function getThemeConfig() {
        return Kurogo::sharedInstance()->themeConfig();
    }
    
    public function themeConfig() {
        if (!$this->themeConfig) {
            $this->themeConfig = new ConfigGroup('theme', 'site');
            
            if ($config = Kurogo::getSiteConfig('theme')) {
                $this->themeConfig->addConfig($config);
            }
            
            if ($this->configModule) {
                if ($config = Kurogo::getModuleConfig('theme', $this->module, Config::OPTION_DO_NOT_CREATE)) {
                    $this->themeConfig->addConfig($config);
                }
            }
        }
        return $this->themeConfig;
    }

    public static function getThemeVars() {
        
        if ($config = Kurogo::getThemeConfig()) {
            
            $pagetype = Kurogo::deviceClassifier()->getPagetype();
            $platform = Kurogo::deviceClassifier()->getPlatform();
            $browser  = Kurogo::deviceClassifier()->getBrowser();
            $sections = array(
                'common',
                $pagetype,
                "$pagetype-$platform",
                "$pagetype-$platform-$browser",
            );
            
            $themeVars = array();
            foreach ($sections as $section) {
                if ($sectionVars = $config->getOptionalSection($section)) {
                    $themeVars = array_merge($themeVars, $sectionVars);
                }
            }
        }
        
        return $themeVars;
    }

    public function localizedString($key, $opts=null) {
        if (!preg_match("/^[a-z0-9_]+$/i", $key)) {
            throw new KurogoConfigurationException("Invalid string key $key");
        }

        // use any number of args past the first as options
        $args = func_get_args();
        array_shift($args);
        if (count($args)==0 || is_null($args[0])) {
            $args = null;
        }

        $languages = $this->getLanguages();
        foreach ($languages as $language) {
            $val = $this->getStringForLanguage($key, $language, $args);
            if ($val !== null) {
                return self::getOptionalSiteVar('LOCALIZATION_DEBUG') ?  $key : $val;
            }
        }

        throw new KurogoConfigurationException("Unable to find site string $key");
    }

    public static function getLocalizedString($key, $opts=null) {
        return Kurogo::sharedInstance()->localizedString($key, $opts);
    }

    public function localizedStrings() {
        $strings = array();

        $languages = $this->getLanguages();
        foreach ($languages as $language) {
            $langStrings = $this->getStringsForLanguage($language);
            foreach ($langStrings as $key => $value) {
                if (!isset($strings[$key])) {
                    $strings[$key] = $value;
                }
            }
        }

        return $strings;
    }

    public static function getLocalizedStrings() {
        return Kurogo::sharedInstance()->localizedStrings();
    }

    public function checkCurrentVersion() {
        if ($currentVersion = Kurogo::getCache('currentVersion')) {
            return $currentVersion;
        } elseif (!Kurogo::getOptionalSiteVar('KUROGO_VERSION_CHECK', 1)) {
            return null;
        }

        try {
          $cache = new DiskCache(CACHE_DIR. DIRECTORY_SEPARATOR . '/kurogo', 3600, TRUE);
        } catch (KurogoDataException $e) {
          $cache = null;
        }
        
        $cacheFilename = 'currentVersion';
        
        if ($cache && $cache->isFresh($cacheFilename)) {
          $currentVersion = $cache->read($cacheFilename);
        } else {
            $url = "http://kurogo.org/checkversion.php?" . http_build_query(array(
                'version'=>KUROGO_VERSION,
                'base'=>FULL_URL_BASE,
                'site'=>SITE_KEY,
                'php'=>phpversion(),
                'uname'=>php_uname("a"),
            ));
            $context = stream_context_create(array(
              'http'=>array(
                'timeout'=>5, 
                'user_agent'=>self::KurogoUserAgent(),
                )
            )
            );
            if ($currentVersion = trim(@file_get_contents($url, false, $context))) {
                if ($cache) {
                    $cache->write($currentVersion, $cacheFilename);
                }
            }
        }
        
        if ($currentVersion) {
            Kurogo::setCache('currentVersion', $currentVersion);
        }
        return $currentVersion;
    }

    # Implmentation taken from https://gist.github.com/1407308
    public static function rmdir($path) {
        if (is_dir($path)){
            foreach (scandir($path) as $name){
                if (in_array($name, array('.', '..'))){
                    continue;
                }
                $subpath = $path.DIRECTORY_SEPARATOR.$name;
                Kurogo::rmdir($subpath);
            }
            return rmdir($path);
        } else {
            if (file_exists($path)){
                return unlink($path);
            }
        }
    }

    public static function defaultModule() {
        $platform = strtoupper(Kurogo::deviceClassifier()->getPlatform());
        $pagetype = strtoupper(Kurogo::deviceClassifier()->getPagetype());
        $browser  = strtoupper(Kurogo::deviceClassifier()->getBrowser());

        if (!$module = Kurogo::getOptionalSiteVar("DEFAULT-{$pagetype}-{$platform}-{$browser}",'','urls')) {
            if (!$module = Kurogo::getOptionalSiteVar("DEFAULT-{$pagetype}-{$platform}",'','urls')) {
                if (!$module = Kurogo::getOptionalSiteVar("DEFAULT-{$pagetype}",'', 'urls')) {
                    $homeModuleID = Kurogo::getOptionalSiteVar('HOME_MODULE', 'home', 'modules');
                    $module = Kurogo::getOptionalSiteVar("DEFAULT", $homeModuleID, 'urls');
                }
            }
        }

        return $module;
    }
    
    public static function validateConnection($host, $port=80, $timeout=5) {
		if (@fsockopen($host, $port, $errno, $err, $timeout)) {
			return true;
		} else {
	        self::log(LOG_NOTICE, "Failed connecting to host $host on port $port ($err)", "kurogo");
			return false;
		}
	}

    public static function arrayVal($args, $key, $default=null){
        if(isset($args[$key])){
            return $args[$key];
        }else{
            return $default;
        }
    }

    public function clearCaches($type=null) {

        self::log(LOG_NOTICE, "Clearing site caches", "kurogo");

        if (strlen($type)>0) {
            return self::rmdir(CACHE_DIR . "/" . $type);
        }

        //clear all folders
        $site = Kurogo::sharedInstance()->getSite();
        $baseCacheDir = $site->getBaseCacheDir();
        $cacheDir = $site->getCacheDir();
        
        if ($baseCacheDir != $cacheDir) {
            $cacheDirs = array();
            foreach (scandir($baseCacheDir) as $dir) {
                if (!in_array($dir, array('.','..'))) {
                    $cacheDirs[] = $baseCacheDir . DIRECTORY_SEPARATOR . $dir;
                }
            }
        } else {
            $cacheDirs = array($cacheDir);
        }

        //exclude session folder
        $excludeDirs = array('session','UserData','.','..');
        foreach ($cacheDirs as $cacheDir) {
            $dirs = scandir($cacheDir);
            foreach ($dirs as $dir) {
                if ( is_dir($cacheDir."/$dir") && !in_array($dir, $excludeDirs)) {
                    $result = self::rmdir($cacheDir . "/" . $dir);
                    if (!$result) {
                        return $result;
                    }
                }
            }
        }
        
        if ($this->cacher) {
            $this->cacher->clear();
        }

        return 0;
    }

    public static function getCacheClasses() {
        includePackage('Cache');
        return KurogoMemoryCache::getCacheClasses();
    }
    
    public static function pushErrorReporting($error_level) {
        return Kurogo::sharedInstance()->_pushErrorReporting($error_level);
    }
        
    private function _pushErrorReporting($error_level) {
    	$this->error_reporting[] = error_reporting($error_level);
    }

    public static function popErrorReporting() {
        return Kurogo::sharedInstance()->_popErrorReporting();
    }

    private function _popErrorReporting() {
    	error_reporting(array_pop($this->error_reporting));
    }

    // REDIRECT_PERMANENT (301): Use this when you want search engines to see the redirect.
    // REDIRECT_SEE_OTHER (303): Use when redirecting from forms (POST -> GET).
    // REDIRECT_TEMPORARY (302): Use in all other situations (default).
    public static function redirectToURL($url, $code=self::REDIRECT_TEMPORARY) {
        header("Location: $url", true, $code);
        exit();
    }

    public static function getPagetype(){
        return Kurogo::deviceClassifier()->getPagetype();
    }

    public static function getPlatform(){
        return Kurogo::deviceClassifier()->getPlatform();
    }

    public static function getBrowser(){
        return Kurogo::deviceClassifier()->getBrowser();
    }

    public static function getDevice(){
        return Kurogo::deviceClassifier()->getDevice();
    }
    
    public static function getAppData($platform=null) {
        return $platform ? self::getOptionalSiteSection($platform, 'apps') : self::getOptionalSiteSections('apps');
    }
}

/* retained for compatibility */
function includePackage($packageName, $subpackageName=null) {
    Kurogo::includePackage($packageName, $subpackageName);
}
