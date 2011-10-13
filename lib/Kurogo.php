<?php

define('ROOT_DIR', realpath(dirname(__FILE__).'/..'));
define('KUROGO_VERSION', '1.3');

//
// And a double quote define for ini files (php 5.1 can't escape them)
//
define('_QQ_', '"');

/* this is a singleton class */
class Kurogo
{
    private static $_instance = NULL;
    private function __clone() {}
    protected $startTime;
    protected $libDirs = array();
    protected $config;
    protected $deviceClassifier;
    protected $session;
    protected $logger;
    protected $locale;    
    protected $languages=array();

    private function __construct() {
        $this->startTime = microtime(true);
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

    public static function includePackage($packageName, $subpackageName=null) {
        $Kurogo = self::sharedInstance();
        return $Kurogo->addPackage($packageName, $subpackageName);
    }
    
    public function addPackage($packageName, $subpackageName=null) {
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
        if (defined('SITE_LIB_DIR')) {  
            $dirs[] = SITE_LIB_DIR . "/$packageName";
        }
    
        foreach ($dirs as $dir) {
            if (in_array($dir, $this->libDirs)) {
                $found = true;
                continue;
            }
    
            if (is_dir($dir)) {
                self::log(LOG_INFO, "Adding package $packageName", "autoLoader");
                $found = true;
                $this->libDirs[] = $dir;
    
                if (is_file("$dir.php")) {
                    include_once("$dir.php");
                }
            }
        }
        
        if (!$found) {
            throw new KurogoConfigurationException("Unable to load package $packageName");
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
        
        self::log(LOG_DEBUG, "Autoloader loading $className", "autoLoader"); 
        foreach ($paths as $path) {
            $file = "$path/$className.php";
            self::log(LOG_DEBUG, "Autoloader looking for $file for $className", "autoLoader");
            if (file_exists($file)) {
                self::log(LOG_INFO, "Autoloader found $file for $className", "autoLoader");
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
    
    public static function isWindows() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
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
        if (!$this->logger && $this->config) {
            require_once(LIB_DIR . '/KurogoLog.php');
            $this->logger = new KurogoLog();
            $logFile = $this->config->getOptionalVar('KUROGO_LOG_FILE', LOG_DIR . "/kurogo.log");
            $this->logger->setLogFile($logFile);
            $this->logger->setDefaultLogLevel($this->config->getOptionalVar('DEFAULT_LOGGING_LEVEL', LOG_WARNING));
            if (($loggingLevels = $this->config->getOptionalVar('LOGGING_LEVEL')) && is_array($loggingLevels)) {
                foreach ($loggingLevels as $area=>$level) {
                    $this->logger->setLogLevel($area, $level);
                }
            }
        }
        
        return $this->logger;
    }
    
    public static function log($priority, $message, $area, $backtrace=null) {
        static $deferredLogs = array();
        $logger = Kurogo::sharedInstance()->logger();
        
        if (!$logger) {
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
        
    public function initialize(&$path=null) {
        //
        // Constants which cannot be set by config file
        //
        
        define('WEBROOT_DIR',       ROOT_DIR . DIRECTORY_SEPARATOR . 'www'); 
        define('LIB_DIR',           ROOT_DIR . DIRECTORY_SEPARATOR . 'lib');
        define('MASTER_CONFIG_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'config');
        define('APP_DIR',           ROOT_DIR . DIRECTORY_SEPARATOR . 'app');
        define('MODULES_DIR',       APP_DIR  . DIRECTORY_SEPARATOR . 'modules');
        define('MIN_FILE_PREFIX',  'file:');
        define('API_URL_PREFIX',   'rest');
        
        //
        // Pull in functions to deal with php version differences
        //
        
        require(LIB_DIR . '/compat.php');
        require(LIB_DIR.'/exceptions.php');

        // add autoloader        
        spl_autoload_register(array($this, "siteLibAutoloader"));
        
        //
        // Load configuration files
        //    
        $this->config = new SiteConfig($path);
        
        ini_set('display_errors', $this->config->getVar('DISPLAY_ERRORS'));
        if (!ini_get('error_log')) {
            ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'php_error.log');
        }

        //
        // Install exception handlers
        //
      
        if ($this->config->getVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
            set_exception_handler("exceptionHandlerForProduction");
        } else {
            set_exception_handler("exceptionHandlerForDevelopment");
        }
        
        //get timezone from config and set    
        $timezone = $this->config->getVar('LOCAL_TIMEZONE');
        date_default_timezone_set($timezone);
        $this->timezone = new DateTimeZone($timezone);
        self::log(LOG_DEBUG, "Setting timezone to $timezone", "kurogo");

        if ($locale = $this->config->getOptionalVar('LOCALE')) {
            $this->setLocale($locale);
        } else {
            $this->locale = $this->getSystemLocale();
        }
        
        if ($languages = $this->config->getOptionalVar('LANGUAGES')) {
        	$this->setLanguages($languages);
        } else {
        	$this->setLanguages(array('en_US'));
        }
        
        //
        // everything after this point only applies to http requests 
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
        self::log(LOG_DEBUG, "Setting server host to $host", "kurogo");
    
        define('IS_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        define('FULL_URL_BASE', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_BASE);
        define('COOKIE_PATH', URL_BASE);

        // make sure host is all lower case
        if ($host != strtolower($host)) {
            $url = 'http'.(IS_SECURE ? 's' : '').'://' . strtolower($host) . $path;
            self::log(LOG_INFO, "Redirecting to lowercase url $url", 'kurogo');
            header("Location: $url");
            exit();
          }
                  
        //
        // Initialize global device classifier
        //
        
        $device = null;
        $urlPrefix = URL_BASE;
        $urlDeviceDebugPrefix = '/';
        
        // Check for device classification in url and strip it if present
        if ($this->config->getVar('DEVICE_DEBUG')) {
            if (preg_match(';^device/([^/]+)/(.*)$;', $path, $matches)) {
                $device = $matches[1];  // layout forced by url
                $path = $matches[2];
                $urlPrefix .= "device/$device/";
                $urlDeviceDebugPrefix .= "device/$device/";
            } elseif (isset($_GET['_device']) && preg_match(';^device/([^/]+)/$;', $_GET['_device'], $matches)) {
                $device = $matches[1];
                $urlPrefix .= "device/$device/";
                $urlDeviceDebugPrefix .= "device/$device/";
            }
        }
      
        define('URL_DEVICE_DEBUG_PREFIX', $urlDeviceDebugPrefix);
        define('URL_PREFIX', $urlPrefix);
        self::log(LOG_DEBUG, "Setting URL_PREFIX to " . URL_PREFIX, "kurogo");
        define('FULL_URL_PREFIX', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_PREFIX);
        define('KUROGO_IS_API', preg_match("#^" .API_URL_PREFIX . "/#", $path));
          
        //error_log(__FUNCTION__."(): prefix: $urlPrefix");
        //error_log(__FUNCTION__."(): path: $path");
        $this->deviceClassifier = new DeviceClassifier($device);
      
        //preserved for compatibility
        $GLOBALS['deviceClassifier'] = $this->deviceClassifier;
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

    private function getStringsForLanguage($lang) {
        $stringFiles = array(
            APP_DIR . "/common/strings/".$lang . '.ini',
            SITE_APP_DIR . "/common/strings/".$lang . '.ini'
        );
        
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
    
    public function checkCurrentVersion() {
        $url = "http://kurogo.org/checkversion.php?" . http_build_query(array(
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
                throw new KurogoException("Cannot find a folder removal tool for this platform. Please report this and include your server operating system and version");
            }
        } else {
            return 1;
        }
    }
    
    public function clearCaches($type=null) {

        self::log(LOG_NOTICE, "Clearing site caches", "kurogo");

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
function includePackage($packageName, $subpackageName=null) {
    Kurogo::includePackage($packageName, $subpackageName);
}
