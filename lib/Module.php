<?php

/**
 * @package Module
 */

/**
 * @package Module
 */ 
abstract class Module
{
    protected $id='none';
    protected $configModule;
    protected $args = array();
    protected $session;
    protected $moduleData;

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
      * Sets the id used for config
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
        return $this->getModuleSections('feeds');
    }
  
    /**
      * Sets the arugments from the incoming request
      * @param array the array of arguments
      */
    protected function setArgs($args) {
      $this->args = is_array($args) ? $args : array();
    }
  
    /**
      * Factory method. Used to instantiate a subclass
      * @param string $id, the module id to load
      * @param string $type, the type of module to load (web/api)
      */
    public static function factory($id, $type=null) {
  
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
                throw new Exception("Invalid module type $type");
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
                
                // see if it exists
                $moduleFile = realpath_exists($path);
                if ($moduleFile && include_once($moduleFile)) {
                    //found it
                    $info = new ReflectionClass($className);
                    if (!$info->isAbstract()) {
                        $module = new $className();
                        return $module;
                    }
                    return false;
                }
            }
        }
       
        throw new Exception("Module $id not found");
    }
    
    public function __construct() {
        if (!$this->configModule) {
            $this->configModule = $this->id;
        }
    }
   
    /**
      * Common initialization. Checks access.
      */
    protected function init() {

        if ($this->getModuleVar('disabled','module')) {
            $this->moduleDisabled();
        }

        if ((Kurogo::getOptionalSiteVar('SECURE_REQUIRED') || $this->getModuleVar('secure','module')) && 
            (!isset($_SERVER['HTTPS']) || (strtolower($_SERVER['HTTPS']) !='on'))) { 
            $this->secureModule();
        }
        
        if (Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            includePackage('Authentication');
            if (!$this->getAccess()) {
                $this->unauthorizedAccess();
            }
        }
    }
    
    protected function getAccess() {

        if ($this->getModuleVar('protected','module')) {
            if (!$this->isLoggedIn()) {
                return false;
            }
        }
                
        if (!$this->evaluateACLS(AccessControlList::RULE_TYPE_ACCESS)) {
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
      * @return array 
      */
    public function getUsers($returnAnonymous=false) {
        $session = $this->getSession();
        return $session->getUsers($returnAnonymous);
    }
      
    /**
      * Returns the current login session
      * @param string $type, the type of module to load (web/api)
      */
    protected function getSession() {
        if (!$this->session) {
            $args = Kurogo::getSiteSection('authentication');
            $args['DEBUG_MODE'] = Kurogo::getSiteVar('DATA_DEBUG');
            $this->session = new Session($args);
        }
    
        return $this->session;
    }
  
    /**
      * Returns a config file
      * @param string $id the module id
      * @param string $type the config file type (module, feeds, pages, etc)
      * @param int $opts bitfield of ConfigFile options
      * @return ConfigFile object
      */
    protected function getConfig($type, $opts=0) {
        if ($config = ModuleConfigFile::factory($this->configModule, $type, $opts)) {
            Kurogo::siteConfig()->addConfig($config);
        }
        return $config;
    }

    /**
      * Convenience method for retrieving a key from an array
      * @param array $args an array to search
      * @param string $key the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @return mixed the value of the or the default 
      */
    protected static function argVal($args, $key, $default=null) {
        if (isset($args[$key])) {
          return $args[$key];
        } else {
          return $default;
        }
    }
  
    /**
      * Returns a key from the request arguments
      * @param string $key the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @return mixed the value of the or the default 
      */
    protected function getArg($key, $default='') {
        return self::argVal($this->args, $key, $default);
    }

    /**
      * Returns a key from the module configuration
      * @param string $var the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $opts
      * @return mixed the value of the or the default 
      */
    protected function getModuleVar($var, $section=null, $config='module') {
        switch ($var) {
            case 'id':
                return $this->configModule;
        }
        
        $config = $this->getConfig($config);
        return $config->getVar($var, $section);
    }

    protected function getOptionalModuleVar($var, $default='', $section=null, $config='module') {
        $config = $this->getConfig($config);
        return $config->getOptionalVar($var, $default, $section);
    }

    protected function getModuleSection($section, $config='module') {
        $config = $this->getConfig($config);
        return $config->getSection($section);
    }

    protected function getOptionalModuleSection($section, $config='module') {
        $config = $this->getConfig($config);
        return $config->getOptionalSection($section);
    }

    protected function getModuleSections($config, $expand=Config::EXPAND_VALUE) {
        $config = $this->getConfig($config);
        return $config->getSectionVars($expand);
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
      * Indicates that administrative access is necessary. Admin access is granted through the adminacl key
      */
    protected function requiresAdmin() {
        if (!$this->evaluateACLS(AccessControlList::RULE_TYPE_ADMIN)) {
            $this->unauthorizedAccess();
        }
    }

    /**
      * Evaluates the access control lists 
      * @param bool $admin if true evaluate the admin acls
      * @return bool true if the access is granted, false if it is not
      */
    protected function evaluateACLS($type=AccessControlList::RULE_TYPE_ACCESS) {
        $acls = $this->getAccessControlLists($type);
        $allow = count($acls) > 0 ? false : true; // if there are no ACLs then access is allowed
        $users = $this->getUsers(true);
        foreach ($acls as $acl) {
            foreach ($users as $user) {
                $result = $acl->evaluateForUser($user);
                switch ($result)
                {
                    case AccessControlList::RULE_ACTION_ALLOW:
                        $allow = true;
                        break;
                    case AccessControlList::RULE_ACTION_DENY:
                        return false;
                        break;
                }
            }
        }
        
        return $allow;
    }

    public function getModuleAccessControlListArrays() {
        $acls = array();
        foreach (self::getModuleAccessControlLists() as $acl) {
            $acls[] = $acl->toArray();
        }
        return $acls;
    }

    public function getModuleAccessControlLists() {
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
      * @param bool $admin if true evaluate the admin acls
      * @return array of access control lists
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
            
            $sections[$section] = array(
                'title'=>$sectionData['title'],
                'type'=>$sectionData['type']
            );
        }
                
        return $sections;
    }
    
    protected function getModuleAdminConfig() {
        static $configData;
        if (!$configData) {
            $configData = array();
            $files = array(
                'common'=>sprintf("%s/common/config/admin-module.json", APP_DIR),
                'module'=>sprintf("%s/%s/config/admin-module.json", MODULES_DIR, $this->id)
            );

            foreach ($files as $type=>$file) {                
                if (is_file($file)) {
                    if (!$data = json_decode(file_get_contents($file),true)) {
                        throw new Exception("Error parsing $file");
                    }
                    
                    foreach ($data as $section=>&$sectionData) {
                        $sectionData['type'] = $type;
                    }
                    
                    $configData = array_merge_recursive($configData, $data);
                }
            }
        }
        
        return $configData;
    }

    abstract protected function moduleDisabled();
    abstract protected function secureModule();
    abstract protected function unauthorizedAccess();

}
