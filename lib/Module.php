<?php

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
    protected $args = array();
    protected $configs = array();
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
      * Loads the data in the feeds configuration file
      * @return array
      */
    protected function loadFeedData() {
        $feeds = $this->getModuleSections('feeds');
        foreach ($feeds as $index=>&$feedData) {
            $feedData['INDEX'] = $index;
        }
        reset($feeds);
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
  
    /**
      * Factory method. Used to instantiate a subclass
      * @param string $id, the module id to load
      * @param string $type, the type of module to load (web/api)
      */
    public static function factory($id, $type=null) {
  
        Kurogo::log(LOG_INFO, "Initializing $type module $id", 'module');
		$configModule = $id;
		//attempt to load config/$id/module.ini  
        if ($config = ModuleConfigFile::factory($id, 'module', ModuleConfigFile::OPTION_DO_NOT_CREATE)) {
        	//use the ID parameter if it's present, otherwise use the included id
        	$id = $config->getOptionalVar('id', $id);
        } elseif (!Kurogo::getOptionalSiteVar('CREATE_DEFAULT_CONFIG', false, 'modules')) {
            Kurogo::log(LOG_ERR, "Module config file not found for module $id", 'module');
			throw new KurogoModuleNotFound(Kurogo::getLocalizedString('ERROR_MODULE_NOT_FOUND', $id));
        }
        

        // when run without a type it will find either
        $classNames = array(
            'web'=>ucfirst($id).'WebModule',
            'api'=>ucfirst($id).'APIModule'
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
        $modulePaths = array(
          SITE_MODULES_DIR."/$id/Site%s.php"=>"Site%s",
          SITE_MODULES_DIR."/$id/%s.php"=>"%s",
          MODULES_DIR."/$id/%s.php"=>"%s",
        );
        
        //cycle module paths 
        foreach($modulePaths as $path=>$className){ 
            
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
                        $module->setConfigModule($configModule);
                        if ($config) {
                        	$module->setConfig('module', $config);
                        }
                        return $module;
                    }
                    Kurogo::log(LOG_NOTICE, "$class found at $moduleFile is abstract and cannot be used for $id", 'module');
                    return false;
                }
            }
        }
       
        Kurogo::log(LOG_ERR, "No valid class found for module $id", 'module');
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
            $this->getModuleVar('disabled','module');
    }    
   
    /**
      * Common initialization. Checks access.
      */
    protected function init() {

        if ($this->isDisabled()) {
            Kurogo::log(LOG_NOTICE, "Access to $this->configModule is disabled", 'module');
            $this->moduleDisabled();
        }

        if ((Kurogo::getOptionalSiteVar('SECURE_REQUIRED') || $this->getModuleVar('secure','module')) && 
            (!isset($_SERVER['HTTPS']) || (strtolower($_SERVER['HTTPS']) !='on'))) { 
            Kurogo::log(LOG_NOTICE, "$this->configModule requires HTTPS", 'module');
            $this->secureModule();
        }
        
        if (Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            Kurogo::includePackage('Authentication');
            if (!$this->getAccess()) {
                $this->unauthorizedAccess();
            }
        }
        $this->logView = Kurogo::getOptionalSiteVar('STATS_ENABLED', true) ? true : false;
    }
    
    /**
      * Evaluates whether the current user has access to this Module
      * @return boolean
      */
    protected function getAccess() {

        if ($this->getModuleVar('protected','module')) {
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
  
    /**
      * Returns a config file
      * @param string $type the config file type (module, feeds, pages, etc)
      * @param int $opts bitfield of ConfigFile options
      * @return ConfigFile object
      */
    protected function getConfig($type, $opts=0) {
    	if (isset($this->configs[$type])) {
    		return $this->configs[$type];
    	}
    	
        if ($config = ModuleConfigFile::factory($this->configModule, $type, $opts)) {
            Kurogo::siteConfig()->addConfig($config);
            $this->setConfig($type, $config);
        }
        return $config;
    }

    /**
      * Sets a config file in the cache
      * @param string $type - the config type (should match the type when creating the config)
      * @param ConfigFile $config - a ConfigFile object
      */    
    protected function setConfig($type, ConfigFile $config) {
    	$this->configs[$type] = $config;
    }
    
    protected static function sanitizeArgValue($value) {
        $search = array('@<\s*script[^>]*?>.*?<\s*/\s*script\s*>@si'
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
        return self::argVal($this->args, $key, $default, $filter, $filterOptions);
    }

    /**
      * Returns a key from the module configuration. If the value does not exist an exception will be thrown
      * @param string $var the key to retrieve
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return mixed
      */
    protected function getModuleVar($var, $section=null, $config='module') {
        switch ($var) {
            case 'id':
                return $this->configModule;
        }
        
        $config = $this->getConfig($config);
        return $config->getVar($var, $section);
    }

    /**
      * Returns a key from the module configuration. If it does not exist it will return a default value
      * @param string $var the key to retrieve
      * @param mixed $default the default value if the key does not exist
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return mixed
      */
    protected function getOptionalModuleVar($var, $default='', $section=null, $config='module') {
        $config = $this->getConfig($config);
        return $config->getOptionalVar($var, $default, $section);
    }

    /**
      * Returns a section as an array from a module configuration. If it does not exist an exception will be thrown
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return array
      */
    protected function getModuleSection($section, $config='module') {
        $config = $this->getConfig($config);
        return $config->getSection($section);
    }

    /**
      * Returns a section as an array from a module configuration. If it does not exist an empty array will be returned
      * @param string $section the section of the config file to check. 
      * @param string $config the module file to check. Default is module (would check module.ini)
      * @return array
      */
    protected function getOptionalModuleSection($section, $config='module') {
        $config = $this->getConfig($config);
        return $config->getOptionalSection($section);
    }

    /**
      * Returns the contents of a config file as a multi-dimensional array. If the file does not exist an exception will be thrown
      * @param string $config the module file to check.
      * @param int $expand whether to expand the values, default is to expand, use Config class constants
      * @return array
      */
    protected function getModuleSections($config, $expand=Config::EXPAND_VALUE, $opts=0) {
        $config = $this->getConfig($config, $opts);
        return $config->getSectionVars($expand);
    }

    /**
      * Returns the contents of a config file as a multi-dimensional array. If the file does not exist return false
      * @param string $config the module file to check.
      * @param int $expand whether to expand the values, default is to expand, use Config class constants
      * @return array
      */
    protected function getOptionalModuleSections($config, $expand=Config::EXPAND_VALUE) {
        if ($config = $this->getConfig($config, ConfigFile::OPTION_DO_NOT_CREATE)) {
            return $config->getSectionVars($expand);
        } else {
            return false;
        }
    }

    /**
      * Returns a section as an array where the each element contains the various keys of the section
      * @param string $section the section to retrieve
      * @return array
      */
    protected function getModuleArray($section) {
        $return = array();

        if ($data = $this->getModuleSection($section)) {
            $fields = array_keys($data);
        
            for ($i=0; $i<count($data[$fields[0]]); $i++) {
                $item = array();
                foreach ($fields as $field) {
                    $item[$field] = $data[$field][$i];
                }
                $return[] = $item;
            }
        } 
        
        return $return;
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
        $acls = $this->getAccessControlLists($type);
        Kurogo::log(LOG_DEBUG, count($acls) . " $type ACLs found for $this->configModule", 'module');
        $allow = count($acls) > 0 ? false : true; // if there are no ACLs then access is allowed
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

        if ($config = $this->getConfig('acls', ConfigFile::OPTION_DO_NOT_CREATE)) {
            foreach ($config->getSectionVars() as $aclArray) {
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
            SITE_APP_DIR . "/common/strings/".$lang . '.ini',
            MODULES_DIR . '/' . $this->id ."/strings/".$lang . '.ini',
            SITE_MODULES_DIR . '/' . $this->id ."/strings/".$lang . '.ini'
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
    
    public function getLocalizedString($key, $opts=null) {
        if (!preg_match("/^[a-z0-9_]+$/i", $key)) {
            throw new KurogoConfigurationException("Invalid string key $key");
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
        
        throw new KurogoConfigurationException("Unable to find string $key for Module $this->id");
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
    

    /**
      * Action to take when the module is disabled
      */
    abstract protected function moduleDisabled();

    /**
      * Action to take when the module must be viewed under https
      */
    abstract protected function secureModule();

    /**
      * Action to take when access to the module is restricted
      */
    abstract protected function unauthorizedAccess();

}
