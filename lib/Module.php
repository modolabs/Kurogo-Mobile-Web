<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package Module
 */

/**
 * @package Module
 */ 
 
define ('FILTER_SANITIZE_KUROGO_DEFAULT', 'KurogoDefaultSanitizeFilter');
abstract class Module
{
    protected $id='none';
    protected $configModule;
    protected $moduleName = '';
    protected $homeModuleID;
    protected $loginModuleID;
    protected $args = array();
    protected $configStore;
    protected $logView = true;
    protected $logData = null;
    protected $logDataLabel = null;
    private $strings = array();

    /**
      * Returns the module id
      * @return string
      */
    public function getID() {
        return $this->id;
    }

    /**
      * Returns the id used for config (typically the id)
      * @return string
      */
    public function getConfigModule() {
        return $this->configModule;
    }

    /**
      * Sets the id used for configuration
      * @param string $id
      */
    public function setConfigModule($id) {
        return $this->configModule = $id;
    }
  
    /**
      * Loads the data in the feeds configuration file. It will get merged with a feeds 
      * section in the module.ini file
      * @return array
      */
    protected function loadFeedData() {
        $default = $this->getOptionalModuleSection('feeds','module');
        $_feeds = $this->getModuleSections('feeds');
        $feeds = array();
        foreach ($_feeds as $index=>$feedData) {
            $feedData = array_merge($default, $feedData);
            if (!isset($feedData['CACHE_FOLDER'])) {
                $feedData['CACHE_FOLDER'] = $this->configModule;
            }
            $feedData['INDEX'] = $index;
            if (Kurogo::arrayVal($feedData, 'ENABLED', true)) {
                $feeds[$index] = $feedData;
            }
        }
        return $feeds;
    }
  
    /**
      * Sets the arugments from the incoming request
      * @param array $args the array of arguments
      */
    protected function setArgs($args) {
      $this->args = is_array($args) ? $args : array();
    }

    protected function setLogData($data, $dataLabel='') {
        $this->logData = strval($data);
        $this->logDataLabel = strval($dataLabel);
    }
  
    private static function cacheKey($id, $type) {
        return "module-factory-{$id}-{$type}";
    }
    
    public static function isValidModuleName($name) {
        return preg_match("/^[a-z][a-z0-9_-]*$/i", $name);
    }
    
    /* this function returns a module's parent ID, given it's tag/url */
    public static function getParentModuleID($moduleID) {
        return Kurogo::getOptionalModuleVar('id', $moduleID, $moduleID, 'module', 'module');
    }
    
    /**
      * Factory method. Used to instantiate a subclass
      * @param string $id, the module id to load
      * @param string $type, the type of module to load (web/api)
      */
    public static function factory($id, $type=null) {
    	if ($id == 'kurogo') {
			set_exception_handler("exceptionHandlerForError");
    	}
    	
    	if (!self::isValidModuleName($id)) {
            throw new KurogoException(Kurogo::getLocalizedString('ERROR_INVALID_MODULE'));
    	}
  
        Kurogo::log(LOG_INFO, "Initializing $type module $id", 'module');
        $configModule = $id;
        $configStore = Kurogo::configStore();
        
        //attempt to load config/$id/module.ini  
        
        if ($moduleData = $configStore->getOptionalSection('module', 'module', $configModule)) {
            $id = Kurogo::arrayVal($moduleData, 'id', $configModule);
        } else {
            throw new KurogoModuleNotFound(Kurogo::getLocalizedString('ERROR_MODULE_NOT_FOUND', $id));
        }

        // see if the class location has been cached 
        if ($moduleFile = Kurogo::getCache(self::cacheKey($id, $type))) {
            $className = basename($moduleFile,'.php');
            include_once($moduleFile);
            $module = new $className();
            if (is_a($module, KurogoWebBridge::STUB_API_CLASS)) {
                $module->setID($id);
            }
            $module->setConfigModule($configModule);
            Kurogo::addModuleLib($id);
            return $module;
        }
        

        // when run without a type it will find either
        $classNames = array(
            'web'=>ucfirst($id).'WebModule',
            'api'=>ucfirst($id).'APIModule',
            'shell'=>ucfirst($id).'ShellModule'
        );
        
        // if we specified a type, include only that type in the array
        if ($type) {
            if (isset($classNames[$type])) {
                $classNames = array($classNames[$type]);
            } else {
                throw new KurogoException("Invalid module type $type");
            }
        }
    
        // possible module paths. 
        // 1. Site Folder SiteMODULEIDXXXModule
        // 2. Site Folder MODULEIDXXXModule
        // 3. Project folder MODULEIDXXXModule
        
        // Note: The PHP class name MUST be the basename of the file path.
        $modulePaths = array(
            SITE_MODULES_DIR."/$id/Site%s.php",
            SITE_MODULES_DIR."/$id/%s.php",
            SHARED_MODULES_DIR."/$id/Site%s.php",
            SHARED_MODULES_DIR."/$id/%s.php",
            MODULES_DIR."/$id/%s.php",
        );
        
        if ($type == 'api' && KurogoWebBridge::moduleHasMediaAssets($configModule)) {
            $modulePaths[] = LIB_DIR . '/' . KurogoWebBridge::STUB_API_CLASS . ".php";
        }
        
        //cycle module paths 
        foreach($modulePaths as $path) {
            $className = basename($path, '.php');
            //cycle class names to find a valid module
            foreach ($classNames as $class) {
                $className = sprintf($className, $class);
                $path = sprintf($path, $class);
                Kurogo::log(LOG_DEBUG, "Looking for $path for $id", 'module');
                
                // see if it exists
                $moduleFile = realpath_exists($path);
                if ($moduleFile && include_once($moduleFile)) {
                    //found it
                    $info = new ReflectionClass($className);
                    if (!$info->isAbstract()) {
                        Kurogo::log(LOG_INFO, "Found $moduleFile for $id", 'module');
                        $module = new $className();
                        if (is_a($module, KurogoWebBridge::STUB_API_CLASS)) {
                            $module->setID($id);
                        }
                        $module->setConfigModule($configModule);
                
                        // cache the location of the class (which also includes the classname)
                        Kurogo::setCache(self::cacheKey($id, $type), $moduleFile);
                        Kurogo::addModuleLib($id);
                        return $module;
                    }
                    Kurogo::log(LOG_NOTICE, "$class found at $moduleFile is abstract and cannot be used for $id", 'module');
                    return false;
                }
            }
        }
       
        Kurogo::log(LOG_NOTICE, "No valid $type class found for module $id", 'module');
        throw new KurogoModuleNotFound(Kurogo::getLocalizedString('ERROR_MODULE_NOT_FOUND', $id));
    }
    
    /**
      * Constructor
      */
    public function __construct() {
        //set the config module if it's not defined in the class definition
        if (!$this->configModule) {
            $this->configModule = $this->id;
        }
    }
    
    /**
      * Returns whether the module is disabled or not
      * @return bool
      */
    protected function isDisabled() {
        return 
            Kurogo::getOptionalSiteVar($this->configModule, false, 'disabled_modules') ||
                $this->getOptionalModuleVar('disabled',false, 'module');
    }  
    
    protected function initConfigStore() {
        $this->configStore = Kurogo::configStore();
    }  
   
    /**
      * Common initialization. Checks access.
      */
    protected function init() {

        if ($this->isDisabled()) {
            Kurogo::log(LOG_NOTICE, "Access to $this->configModule is disabled", 'module');
            $this->moduleDisabled();
        }

		
		$forceInsecure = $this->getOptionalModuleVar('FORCE_INSECURE', 0);
		$secureRequired = ((Kurogo::sharedInstance()->getSite()->getRequiresSecure()) || $this->getOptionalModuleVar('secure',false, 'module')) && !$forceInsecure;
		
        if ($secureRequired && !IS_SECURE) {
            Kurogo::log(LOG_NOTICE, "$this->configModule requires HTTPS", 'module');
            $this->secureModule();
        } elseif ($this->getConfigModule() == Kurogo::sharedInstance()->getCurrentModuleID() &&  //only if it's the running module
        		!$secureRequired && IS_SECURE && $forceInsecure) {
            $this->unsecureModule();
        }
        
        if (Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            Kurogo::includePackage('Authentication');
            if (!$this->getAccess()) {
                $this->unauthorizedAccess();
            }
        }
        $this->logView = Kurogo::getOptionalSiteVar('STATS_ENABLED', true) ? true : false;
    }
    
    public function isEnabled() {
        return !$this->getOptionalModuleVar('disabled', false, 'module');
    }
    
    /**
      * Evaluates whether the current user has access to this Module
      * @return boolean
      */
    protected function getAccess() {

        if ($this->getOptionalModuleVar('protected',false, 'module')) {
            if (!$this->isLoggedIn()) {
                Kurogo::log(LOG_NOTICE, "Access to $this->configModule denied by protected attribute", 'module');
                return false;
            }
        }
                
        if (!$this->evaluateACLS(AccessControlList::RULE_TYPE_ACCESS)) {
            Kurogo::log(LOG_NOTICE, "Access to $this->configModule denied by Access Control List", 'module');
            return false;
        }
    
        return true;
    }


    /**
      * Determines whether a user is logged in. Kurogo supports multiple simultaneous identities
      * @param mixed $authority. See if a user from a particular authority is logged in. Could be 1. the authority's index, 2. the authority's class, 3. a user class
      * @param bool whether a user is logged in
      */
    public function isLoggedIn($authority=null) {
        $session = $this->getSession();
        return $session->isLoggedIn($authority);
    }

    /**
      * Returns the active user, optionally specifying the authority
      * @param mixed $authority. See if a user from a particular authority is logged in. Could be 1. the authority's index, 2. the authority's class, 3. a user class
      * @return User object
      */
    public function getUser($authority=null) {
        $session = $this->getSession();
        return $session->getUser($authority);
    }

    /**
      * Returns the active users
      * @param bool $returnAnonymous. If true it will always return a user (will return anonymous if not logged in). 
      * @return array 
      */
    public function getUsers($returnAnonymous=false) {
        $session = $this->getSession();
        return $session->getUsers($returnAnonymous);
    }
      
    /**
      * Returns the current login session
      */
    protected function getSession() {
        return Kurogo::getSession();
    }
  
    protected static function sanitizeArgValue($value) {
        $search = array('@<\s*script[^>]*?'.'>.*?<\s*/\s*script\s*>@si'
        );  // Strip out javascript 
        return preg_replace($search, "", $value);
    }
    
    /**
      * Convenience method for retrieving a key from an array. It can also optionally apply a filter to the value
      * @param array $args an array to search
      * @param string $key the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $filter a valid filter type from filter_var. Default applies no filter. If the filter fails it will return the default value
      * @param mixed $filterOptions options, the options for the filter (see filter_var)
      * @return mixed the value of the or the default 
      */
    protected static function argVal($args, $key, $default=null, $filter=null, $filterOptions=null) {
        if (isset($args[$key])) {
            $value = $args[$key];
            if ($filter) {
                if ($filter == FILTER_SANITIZE_KUROGO_DEFAULT) {
                    $filter = FILTER_CALLBACK;
                    $filterOptions = array('options'=>array('Module','sanitizeArgValue'));
                }
                
                if (($value = filter_var($value, $filter, $filterOptions))===FALSE) {
                    $value = $default;
                }
            }
            return $value;
        } else {
            return $default;
        }
    }
  
    /**
      * Returns a key from the request arguments
      * @param string $key the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $filter a valid filter type from filter_var. Default applies no filter 
      * @param mixed $filterOptions options, the options for the filter (see filter_var)
      * @return mixed the value of the or the default 
      */
    protected function getArg($key, $default='', $filter=FILTER_SANITIZE_KUROGO_DEFAULT, $filterOptions=null) {
      if (is_array($key)) {
        $result = null;
        foreach ($key as $val) {
          $result = self::argVal($this->args, $val, null, $filter, $filterOptions);
          if ($result !== null) {
            return $result;
          }
        }
        if ($result === null) {
          return $default;
        }
      }else {
        return self::argVal($this->args, $key, $default, $filter, $filterOptions);
      }
    }

    protected function getModuleIcon() {
        return $this->getOptionalModuleVar('icon', $this->configModule, 'module');
    }

    protected function getThemeVar($key) {
        $vars = Kurogo::getThemeVars();
        if (!isset($vars[$key])) {
            throw new KurogoConfigurationException("Config variable '$key' not set");
        }
        
        return $vars[$key];
    }

    protected function getOptionalThemeVar($var, $default='') {
        $vars = Kurogo::getThemeVars();
        return isset($vars[$var]) ? $vars[$var] : $default;
    }

    protected function getPostArg($key, $default='', $filter=FILTER_SANITIZE_KUROGO_DEFAULT, $filterOptions=null) {
      if (is_array($key)) {
        $result = null;
        foreach ($key as $val) {
          $result = self::argVal($_POST, $val, null, $filter, $filterOptions);
          if ($result !== null) {
            return $result;
          }
        }
        if ($result === null) {
          return $default;
        }
      }else {
        return self::argVal($_POST, $key, $default, $filter, $filterOptions);
      }
    }

    /**
      * Returns a key from the module configuration. If the value does not exist an exception will be thrown
      * @param string $var the key to retrieve
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return mixed
      */
    protected function getModuleVar($var, $section=null, $area='module') {
        if ($var == 'id' && $config == 'module') {
            KurogoDebug::Debug(func_get_args(), true);
        }

        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getVar($var, $section, $area, $this);
    }

    /**
      * Returns a key from the module configuration. If it does not exist it will return a default value
      * @param string $var the key to retrieve
      * @param mixed $default the default value if the key does not exist
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return mixed
      */
    protected function getOptionalModuleVar($var, $default='', $section=null, $area='module') {
        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getOptionalVar($var, $default, $section, $area, $this);
    }

    /**
      * Returns a section as an array from a module configuration. If it does not exist an exception will be thrown
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return array
      */
    protected function getModuleSection($section, $area='module') {
        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getSection($section, $area, $this);
    }

    /**
      * Returns a section as an array from a module configuration. If it does not exist an empty array will be returned, if the config does not exist false will be returned
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return array
      */
    protected function getOptionalModuleSection($section, $area='module', $expand=Config::EXPAND_VALUE) {
        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getOptionalSection($section, $area, $this);
    }

    /**
      * Returns the contents of a config file as a multi-dimensional array. If the file does not exist an exception will be thrown
      * @param string $config the module file to check.
      * @param int $expand whether to expand the values, default is to expand, use Config class constants
      * @return array
      */
    protected function getModuleSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getSections($area, $this, $applyContexts);
    }

    /**
      * Returns the contents of a config file as a multi-dimensional array. If the file does not exist return false
      * @param string $config the module file to check.
      * @param int $expand whether to expand the values, default is to expand, use Config class constants
      * @return array
      */
    protected function getOptionalModuleSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        if (!$this->configStore) {
            $this->initConfigStore();
        }
        return $this->configStore->getOptionalSections($area, $this, $applyContexts);
    }

    /**
      * Indicates that administrative access is necessary. 
      */
    protected function requiresAdmin() {
        if (!$this->evaluateACLS(AccessControlList::RULE_TYPE_ADMIN)) {
            $this->unauthorizedAccess();
        }
    }

    /**
      * Evaluates the access control lists 
      * @param string $type the type of acl to evaluate (see AccessControlList::RULE_TYPE_*)
      * @return bool true if the access is granted, false if it is not
      */
    protected function evaluateACLS($type=AccessControlList::RULE_TYPE_ACCESS) {
    	$allow = true;
        if ($acls = $this->getAccessControlLists($type)) {
        	$allow = false;
			$users = $this->getUsers(true);
			foreach ($acls as $index=>$acl) {
				foreach ($users as $user) {
					$result = $acl->evaluateForUser($user);
					switch ($result)
					{
						case AccessControlList::RULE_ACTION_ALLOW:
							Kurogo::log(LOG_INFO, "User $user allowed for ACL $index: $acl for $this->configModule",'module');
							$allow = true;
							break;
						case AccessControlList::RULE_ACTION_DENY:
							Kurogo::log(LOG_INFO, "User $user denied for ACL $index: $acl for $this->configModule",'module');
							return false;
							break;
					}
				}
			}
        }

        if (!$allow) {
            Kurogo::log(LOG_INFO, "User $user did not match any ACLs for $this->configModule",'module');
        }        
        return $allow;
    }

    /**
      * Returns the access control lists as a series of arrays. Used by admin module
      * @return array
      */
    public function getModuleAccessControlListArrays() {
        $acls = array();
        foreach (self::getModuleAccessControlLists() as $acl) {
            $acls[] = $acl->toArray();
        }
        return $acls;
    }

    /**
      * Returns the access control lists
      * @return array
      */
    protected function getModuleAccessControlLists() {
        $acls = array();

        if ($aclData = $this->getOptionalModuleSections('acls')) {
            foreach ($aclData as $aclArray) {
                if ($acl = AccessControlList::createFromArray($aclArray)) {
                    $acls[] = $acl;
                }
            }
        }
        
        return $acls;
    }

    /**
      * Retrieves the access control lists 
      * @param string $type the type of acl to evaluate (see AccessControlList::RULE_TYPE_*)
      * @return array of access control list objects
      */
    protected function getAccessControlLists($type) {
                
        $allACLS = array_merge(Kurogo::getSiteAccessControlLists(), $this->getModuleAccessControlLists());
        $acls = array();
        
        foreach ($allACLS as $acl) {
            if ($acl->getType()==$type) {
                $acls[] = $acl;
            }
        }
        
        return $acls;
    }

    /**
      * Retrieves a list of sections for the module admin. Subclasses could override this if the list was dynamic
      * @return array
      */
    protected function getModuleAdminSections() {
        $configData = $this->getModuleAdminConfig();
        $sections = array();
        foreach ($configData as $section=>$sectionData) {
            if (isset($sectionData['showIfSiteVar'])) {
                if (Kurogo::getOptionalSiteVar($sectionData['showIfSiteVar'][0], '') != $sectionData['showIfSiteVar'][1]) {
                    continue;
                }
            }

            if (isset($sectionData['showIfModuleVar'])) {
                if ($this->getOptionalModuleVar($sectionData['showIfModuleVar'][0], '') != $sectionData['showIfModuleVar'][1]) {
                    continue;
                }
            }

            if (isset($sectionData['titleKey'])) {
                $sectionData['title'] = $this->getLocalizedString($sectionData['titleKey']);
                unset($sectionData['titleKey']);
            }
            
            $sections[$section] = array(
                'title'=>$sectionData['title'],
                'type'=>$sectionData['type']
            );
        }
                
        return $sections;
    }

    /* used by mergeConfigData */
    private function mergeArrays($base, $new) {
        foreach ($new as $field=>$data) {
            
            if ($data) {
                //add it all if it's not there
                $base[$field] = $data;
            } elseif (isset($base[$field])) {
                //remove the section if it's false
                unset($base[$field]);
            }
        }
        
        return $base;
                
    }
    
    protected function mergeConfigData($baseData, $newData) {

        foreach ($newData as $field=>$data) {
            
            /* sections */
            if (!isset($baseData[$field])) {
                //add it all if it's not there
                $baseData[$field] = $data;
            } elseif (!$data) {
                //remove the section if it's false
                unset($baseData[$field]);
            } else {
            
                switch ($baseData[$field]['sectiontype'])
                {
                    case 'fields':
                    case 'section':
                        $baseData[$field]['fields'] = self::mergeArrays($baseData[$field]['fields'], $data['fields']);
                        break;
                        
                    case 'sections':
                        foreach ($data['sections'] as $section=>$sectionData) {
                            if (!isset($baseData[$field]['sections'][$section])) {
                                $baseData[$field]['sections'][$section] = $sectionData;
                            } elseif (!$sectionData) {
                                unset($baseData[$field]['sections'][$section]);
                            } else {
                                $baseData[$field]['sections'][$section]['fields'] = self::mergeArrays($baseData[$field]['sections'][$section]['fields'], $sectionData['fields']);
                            }
                        }
                        break;
                }

            }
        }
    
        return $baseData;
    }

    /**
      * Returns the admin console definitions.
      * @return array
      */
    protected function getModuleAdminConfig() {
        static $configData;
        if (!$configData) {
            $configData = array();
            $files = array(
                'common'=>sprintf("%s/common/config/admin-module.json", APP_DIR),
                'module'=>sprintf("%s/%s/config/admin-module.json", MODULES_DIR, $this->id),
                'sharedcommon'=>sprintf("%s/common/config/admin-module.json", SHARED_APP_DIR),
                'sharedmodule'=>sprintf("%s/%s/config/admin-module.json", SHARED_MODULES_DIR, $this->id),
                'sitecommon'=>sprintf("%s/common/config/admin-module.json", SITE_APP_DIR),
                'sitemodule'=>sprintf("%s/%s/config/admin-module.json", SITE_MODULES_DIR, $this->id)
            );

            foreach ($files as $type=>$file) {
                if (is_file($file)) {
                    if (!$data = json_decode(file_get_contents($file),true)) {
                        throw new KurogoDataException($this->getLocalizedString('ERROR_PARSING_FILE', $file));
                    }
                    
                    foreach ($data as $section=>&$sectionData) {
                        $sectionData['type'] = $type;
                    }
                    
                    $configData = self::mergeConfigData($configData, $data);
                }
            }
        }
        
        return $configData;
    }

    private function getStringsForLanguage($lang) {
        $stringFiles = array(
            APP_DIR . "/common/strings/".$lang . '.ini',
            SHARED_APP_DIR . "/common/strings/".$lang . '.ini',
            SITE_APP_DIR . "/common/strings/".$lang . '.ini',
            MODULES_DIR . '/' . $this->id ."/strings/".$lang . '.ini',
            SHARED_MODULES_DIR . '/' . $this->id ."/strings/".$lang . '.ini',
            SITE_MODULES_DIR . '/' . $this->id ."/strings/".$lang . '.ini'
        );
        
        if ($this->id != $this->configModule) {
            $stringFiles[] = SITE_MODULES_DIR . '/' . $this->configModule ."/strings/".$lang . '.ini';
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

    public function getOptionalLocalizedString($key, $default = null){
        $args = func_get_args();
        // Remove default
        if (isset($args[1])) {
          unset($args[1]);
          $args = array_values($args);
        }

        try {
          $value = call_user_func_array(array($this, 'getLocalizedString'), $args);
        } catch (KurogoKeyNotFoundException $e) {
          if($default !== NULL){
            $value = $default;
          }else{
            $value = $key;
          }
        } catch (KurogoInvalidKeyException $e){
          if($default !== NULL){
            $value = $default;
          }else{
            $value = $key;
          }
        }
        return $value;
    }
    
    public function getLocalizedString($key) {
        if (!preg_match("/^[a-z0-9_]+$/i", $key)) {
            throw new KurogoInvalidKeyException("Invalid string key $key");
        }

        Kurogo::log(LOG_DEBUG, "Retrieving localized string for $key", 'module');
        // use any number of args past the first as options
        $args = func_get_args();
        array_shift($args);
        if (count($args)==0 || is_null($args[0])) {
            $args = null;
        } 
        
        $languages = Kurogo::sharedInstance()->getLanguages();
        foreach ($languages as $language) {
            $val = $this->getStringForLanguage($key, $language, $args);
            if ($val !== null) {
                Kurogo::log(LOG_INFO, "Found localized string \"$val\" for $key in $language", 'module');
                return Kurogo::getOptionalSiteVar('LOCALIZATION_DEBUG') ? $key : $val;
            }
        }
        
        throw new KurogoKeyNotFoundException("Unable to find string $key for Module $this->id");
    }
    
    /**
      * Return the module's descriptive name
      * @return string
      */
    public function getModuleName() {
        if (!$this->moduleName) {
            $this->moduleName    = $this->getModuleVar('title','module');
        }
        return $this->moduleName;
    }
    
    protected function getHomeModulePortletConfig() {
        static $homePortletConfig;
        if (!$homePortletConfig) {
            $homePortletConfig = Kurogo::getModuleConfig('portlets', $this->getHomeModuleID());
        }
        return $homePortletConfig;
    }
    
    protected function getHomeModuleID() {
        if (!$this->homeModuleID) {
            $this->homeModuleID = Kurogo::getOptionalSiteVar('HOME_MODULE', 'home', 'modules');
        }
        
        return $this->homeModuleID;
    }

    protected function getLoginModuleID() {
        if (!$this->loginModuleID) {
            $this->loginModuleID = Kurogo::getOptionalSiteVar('LOGIN_MODULE', 'login', 'authentication');
        }
        
        return $this->loginModuleID;
    }

    /**
      * Action to take when the module is disabled
      */
    abstract protected function moduleDisabled();

    /**
      * Action to take when the module must be viewed under https
      */
    abstract protected function secureModule();

    /**
      * Action to take when the module must not be viewed under https
      */
    abstract protected function unsecureModule();

    /**
      * Action to take when access to the module is restricted
      */
    abstract protected function unauthorizedAccess();

    public function removeModule() {
        $source_dir = SITE_CONFIG_DIR . DIRECTORY_SEPARATOR . $this->getConfigModule();
        $base_target_dir = $target_dir = SITE_DISABLED_DIR . DIRECTORY_SEPARATOR . $this->getConfigModule() . '-' . date('Y-m-d');
        $start = 1;
        
        if (!is_dir(SITE_DISABLED_DIR)) {
            mkdir(SITE_DISABLED_DIR, 0700, true);
        }
        
        while (is_dir($target_dir)) {
            $target_dir = $base_target_dir . '-' . $start++;
        }
        
        rename($source_dir, $target_dir);
        Kurogo::clearCache();
        clearstatcache();
    }
    

}
