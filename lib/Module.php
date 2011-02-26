<?php

abstract class Module
{
    protected $id='none';
    protected $args = array();
    protected $session;
    protected $moduleData;

  public function getID() {
    return $this->id;
  }
  
  protected function loadFeedData() {
    $data = null;
    $feedConfigFile = realpath_exists(sprintf("%s/feeds/%s.ini", SITE_CONFIG_DIR, $this->id));
    if ($feedConfigFile) {
        $data = parse_ini_file($feedConfigFile, true);
    } 
    
    return $data;
  }
  
  protected function setArgs($args) {
    $this->args = is_array($args) ? $args : array();
  }
  
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
    
    // possible module paths
    $modulePaths = array(
      SITE_MODULES_DIR."/$id/Site%s.php"=>"Site%s",
      MODULES_DIR."/$id/%s.php"=>"%s"
    );
    
    //cycle module paths and class names to find a valid module
    foreach($modulePaths as $path=>$className){ 
        foreach ($classNames as $class) {
            $className = sprintf($className, $class);
            $path = sprintf($path, $class);
            $moduleFile = realpath_exists($path);
            if ($moduleFile && include_once($moduleFile)) {
                return new $className();
            }
        }
    }
   
    throw new ModuleNotFound("Module $id not found");
   }
   
   protected function init() {
        $moduleData = $this->getModuleData();

        if ($moduleData['disabled']) {
            $this->moduleDisabled();
        }
        
        if ($moduleData['secure'] && (!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] !='on'))) { 
            $this->secureModule();
        }
        
        if ($this->getSiteVar('AUTHENTICATION_ENABLED')) {
            $user = $this->getUser();
            $session = $this->getSession();
            if ($moduleData['protected']) {
                if (!$this->isLoggedIn()) {
                    $this->unauthorizedAccess();
                }
            }
            
            $acls = $this->getAccessControlLists();
            $allow = count($acls) > 0 ? false : true; // if there are no ACLs then access is allowed
            foreach ($acls as $acl) {
                $result = $acl->evaluateForUser($user);
                switch ($result)
                {
                    case AccessControlList::RULE_ACTION_ALLOW:
                        $allow = true;
                        break;
                    case AccessControlList::RULE_ACTION_DENY:
                        $this->unauthorizedAccess();
                        break;
                }
            }
            
            if (!$allow) {
                $this->unauthorizedAccess();
            }
        }
    }

  //
  // User functions
  //
  
  public function isLoggedIn() {
    $session = $this->getSession();
    return $session->isLoggedIn();
  }

  public function getUser() {
    $session = $this->getSession();
    return $session->getUser();
  }
  
  public function getSession() {
    if (!$this->session) {
        $this->session = new Session();
    }
    
    return $this->session;
  }
  
  public function createDefaultConfigFile() {
    $moduleConfig = $this->getConfig($this->id, 'module', ConfigFile::OPTION_CREATE_EMPTY);
    $moduleConfig->addSectionVars($this->getModuleDefaultData());
    return $moduleConfig->saveFile();
  }
 
  protected function getConfig($name, $type, $opts=0) {
    $config = ConfigFile::factory($name, $type, $opts);
    $GLOBALS['siteConfig']->addConfig($config);
    return $config;
  }

  public function getModuleConfig() {
    static $moduleConfig;
    if (!$moduleConfig) {
        $moduleConfig = $this->getConfig($this->id, 'module', ConfigFile::OPTION_CREATE_WITH_DEFAULT);
    }

    return $moduleConfig;
  }
 
   public function getModuleData() {
    if (!$this->moduleData) {
        $moduleData = $this->getModuleDefaultData();
        $config = $this->getModuleConfig();
        $moduleData = array_merge($moduleData, $config->getSectionVars(true));
        $this->moduleData = $moduleData;
    }
    
    return $this->moduleData;
  }
  
  protected function getModuleDefaultData() {
    return array(
        'title'=>ucfirst($this->id),
        'disabled'=>0,
        'protected'=>0,
        'search'=>0,
        'secure'=>0
    );
  }

  protected static function argVal($args, $key, $default=null) {
    if (isset($args[$key])) {
      return $args[$key];
    } else {
      return $default;
    }
  }
  
  protected function getArg($key, $default='') {
    return self::argVal($this->args, $key, $default);
  }

  //
  // Configuration
  //
  protected function getSiteVar($var, $opts=Config::LOG_ERRORS) {
      return $GLOBALS['siteConfig']->getVar($var, $opts | Config::EXPAND_VALUE);
  }

  protected function getSiteSection($var, $opts=Config::LOG_ERRORS) {
      return $GLOBALS['siteConfig']->getSection($var, $opts);
  }

  protected function getModuleVar($var, $default=null, $opts=Config::LOG_ERRORS)
  {
     $config = $this->getModuleConfig();
     $value = $config->getVar($var, Config::EXPAND_VALUE| $opts);
     return is_null($value) ? $default :$value;
  }

  protected function getModuleSection($section, $default=array(), $opts=Config::LOG_ERRORS)
  {
     $config = $this->getModuleConfig();
     if (!$section = $config->getSection($section, $opts)) {
        $section = $default;
     }
     return $section;
  }

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

  public function getAccessControlLists() {
    $acls = array();
    $aclStrings = $this->getModuleVar('acl', array(), Config::SUPRESS_ERRORS);
    foreach ($aclStrings as $aclString) {
        if ($acl = AccessControlList::createFromString($aclString)) {
            $acls[] = $acl;
        } else {
            throw new Exception("Invalid ACL $aclString in $this->id");
        }
    }
    
    return $acls;
  }
  
  
  abstract protected function moduleDisabled();
  abstract protected function secureModule();
  abstract protected function unauthorizedAccess();

}