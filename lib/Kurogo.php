<?php

define('ROOT_DIR', realpath(dirname(__FILE__).'/..'));
define('KUROGO_VERSION', '1.2');

//
// And a double quote define for ini files (php 5.1 can't escape them)
//
define('_QQ_', '"');

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
    protected $locale;    
    protected $languages=array('en_US');

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
    
    public static function isWindows() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public static function getAvailableLocales() {
        static $locales=array();
        if ($locales) {
            return $locales;
        }
        
        if (file_exists('/usr/bin/locale')) {
            exec('/usr/bin/locale -a', $locales, $retval);
            if ($retval!==0) {
                throw new Exception("Error retrieving locale values");
            }
        } else {
            throw new Exception("Unable to find list of locales on this platform");
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
            throw new Exception("Setting locale in Windows is not supported at this time");
        }

        // this is platform dependent.        
        if (!$return = setLocale(LC_ALL, $locale)) {
            throw new Exception("Unknown locale setting $locale");
        }
        $this->locale = $return;
        return $this->locale;
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

        // add autoloader        
        spl_autoload_register(array($this, "siteLibAutoloader"));
        
        //
        // Load configuration files
        //    
        $this->config = new SiteConfig();
        ini_set('display_errors', $this->config->getVar('DISPLAY_ERRORS'));
        if (!ini_get('error_log')) {
            ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'php_error.log');
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
        
        //get timezone from config and set    
        $timezone = $this->config->getVar('LOCAL_TIMEZONE');
        date_default_timezone_set($timezone);
        $this->timezone = new DateTimeZone($timezone);
        
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
    
        //
        // Get URL base
        //
        if ($urlBase = self::getOptionalSiteVar('URL_BASE','','urls')) {
            $foundPath = true;
            $urlBase = rtrim($urlBase,'/').'/';
        } else {
            //extract the path parts from the url
            $pathParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI'])));
            $testPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
            $urlBase = '/';
            $foundPath = false;

            //once the path equals the WEBROOT_DIR we've found the base. This only works with symlinks
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
        }

        define('URL_BASE', $foundPath ? $urlBase : '/');
        define('IS_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        define('FULL_URL_BASE', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_BASE);
        define('COOKIE_PATH', URL_BASE);

        // make sure host is all lower case
        if ($host != strtolower($host)) {
            $url = 'http'.(IS_SECURE ? 's' : '').'://' . strtolower($host) . $path;
            header("Location: $url");
            exit();
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
    
    public function getLanguages() {
    	return $this->languages;
    }

    public function setLanguages($languages) {
    	$validLanguages = self::getAvailableLanguages();
    	if (is_array($languages)) {
    		$this->languages = array();
    		foreach ($languages as $language) {
    			if (!array_key_exists($language, $validLanguages)) {
    				throw new Exception("Invalid language $language");
    			}
    			$this->languages[] = $language;
    		}
    	} elseif (array_key_exists($languages, $validLanguages)) {
			$this->languages[] = $languages;
    	} else {
			throw new Exception("Invalid language $languages");
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

    private function getStringsForLanguage($lang) {
        $stringFiles = array(
            APP_DIR . "/common/strings/".$lang . '.json',
            SITE_APP_DIR . "/common/strings/".$lang . '.json'
        );
        
        $strings = array();
        foreach ($stringFiles as $stringFile) {
            if (is_file($stringFile)) {
                $_strings = json_decode(file_get_contents($stringFile), true);
                $strings = array_merge($strings, $_strings);
            }
        }
        
        return $strings;
    }
    
    private function processString($string, $opts) {
        if (is_null($opts)) {
            return $string;
        } elseif (is_array($opts)) {
            return vsprintf($string, $opts);
        } else {
            return sprintf($string, $opts);
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
            throw new Exception("Invalid string key $key");
        }
        
        $languages = $this->getLanguages();
        foreach ($languages as $language) {
            $val = $this->getStringForLanguage($key, $language, $opts);
            if ($val !== null) {
                return self::getOptionalSiteVar('LOCALIZATION_DEBUG') ?  $key : $val;
            }
        }
        
        throw new Exception("Unable to find site string $key");
    }
    
    public static function getLocalizedString($key, $opts=null) {
        return Kurogo::sharedInstance()->localizedString($key, $opts);
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
