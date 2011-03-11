<?php

/**
 * @package Module
 */

/**
 * @package Module
 */ 
abstract class Module
{
    const ACL_ADMIN='acladmin';
    const ACL_USER ='acl';
    protected $id='none';
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
      * Loads the data in the feeds configuration file
      * @return array
      */
    protected function loadFeedData() {
        $data = array();
        
        if ($feedConfigFile = $this->getConfig($this->id, 'feeds')) {
            $data = $feedConfigFile->getSectionVars();
        }
        return $data;
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
                    return new $className();
                }
            }
        }
       
        throw new Exception("Module $id not found");
    }
   
    /**
      * Common initialization. Checks access.
      */
    protected function init() {
        $moduleData = $this->getModuleData();

        if ($moduleData['disabled']) {
            $this->moduleDisabled();
        }
        
        if ($moduleData['secure'] && (!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] !='on'))) { 
            $this->secureModule();
        }
        
        if ($this->getSiteVar('AUTHENTICATION_ENABLED')) {
            includePackage('Authentication');
            if ($moduleData['protected']) {
                if (!$this->isLoggedIn()) {
                    $this->unauthorizedAccess();
                }
            }
            
            if (!$this->evaluateACLS(self::ACL_USER)) {
                $this->unauthorizedAccess();
            }
        }
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
            $args = $this->getSiteSection('authentication');
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
    protected function getConfig($id, $type, $opts=0) {
        $config = ConfigFile::factory($id, $type, $opts);
        $GLOBALS['siteConfig']->addConfig($config);
        return $config;
    }

    /**
      * Returns the main module configuration file
      * @return ConfigFile object
      */
    protected function getModuleConfig() {
        static $moduleConfig;
        if (!$moduleConfig) {
            $moduleConfig = $this->getConfig($this->id, 'module', ConfigFile::OPTION_CREATE_WITH_DEFAULT);
        }
        
        return $moduleConfig;
    }
 
    /**
      * Returns the main module configuration
      * @return array. Dictionary of module keys and values
      */
    public function getModuleData() {
        if (!$this->moduleData) {
            $moduleData = $this->getModuleDefaultData();
            $config = $this->getModuleConfig();
            $moduleData = array_merge($moduleData, $config->getSectionVars(true));
            $this->moduleData = $moduleData;
        }
    
        return $this->moduleData;
    }
  
    /**
      * Returns default module configuration data
      * @return array
      */
    protected function getModuleDefaultData() {
        return array(
            'title'=>ucfirst($this->id),
            'disabled'=>0,
            'protected'=>0,
            'search'=>0,
            'secure'=>0
        );
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
      * Returns a key from the site configuration
      * @param string $var the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $opts
      * @return mixed the value of the or the default 
      */
    protected function getSiteVar($var, $default=null, $opts=Config::LOG_ERRORS) {
        $value = $GLOBALS['siteConfig']->getVar($var, $opts | Config::EXPAND_VALUE);
        return is_null($value) ? $default :$value;
    }

    /**
      * Returns a section from the site configuration
      * @param string $section the key to retrieve
      * @param int $opts
      * @return array the section
      */
    protected function getSiteSection($section, $opts=Config::LOG_ERRORS) {
        return $GLOBALS['siteConfig']->getSection($section, $opts);
    }

    /**
      * Returns a key from the module configuration
      * @param string $var the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $opts
      * @return mixed the value of the or the default 
      */
    protected function getModuleVar($var, $default=null, $opts=Config::LOG_ERRORS) {
        $config = $this->getModuleConfig();
        $value = $config->getVar($var, Config::EXPAND_VALUE| $opts);
        return is_null($value) ? $default :$value;
    }

    /**
      * Returns a section from the site configuration
      * @param string $var the key to retrieve
      * @param mixed $default an optional default value if the key is not present
      * @param int $opts
      * @return mixed the value of the or the default 
      */
    protected function getModuleSection($section, $default=array(), $opts=Config::LOG_ERRORS) {
        $config = $this->getModuleConfig();
        if (!$section = $config->getSection($section, $opts)) {
            $section = $default;
        }
        return $section;
    }

    /**
      * Returns a section as an array where the each element contains the various keys of the section
      * @param string $section the section to retrieve
      * @return array
      */
    protected function getModuleArray($section) {
        $config = $this->getModuleConfig();
        $return = array();

        if ($data = $config->getSection($section)) {
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
        if (!$this->evaluateACLS(self::ACL_ADMIN)) {
            $this->unauthorizedAccess();
        }
    }

    /**
      * Evaluates the access control lists 
      * @param bool $admin if true evaluate the admin acls
      * @return bool true if the access is granted, false if it is not
      */
    protected function evaluateACLS($type) {
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

    /**
      * Retrieves the access control lists 
      * @param bool $admin if true evaluate the admin acls
      * @return array of access control lists
      */
    protected function getAccessControlLists($type) {
        $acls = array();
        
        $aclStrings = array_merge(
            $this->getSiteVar($type, array(), Config::SUPRESS_ERRORS),
            $this->getModuleVar($type, array(), Config::SUPRESS_ERRORS)
        );
        
        foreach ($aclStrings as $aclString) {
            if ($acl = AccessControlList::createFromString($aclString)) {
                $acls[] = $acl;
            } else {
                throw new Exception("Invalid $var $aclString in $this->id");
            }
        }
        
        return $acls;
    }

    abstract protected function moduleDisabled();
    abstract protected function secureModule();
    abstract protected function unauthorizedAccess();

}
