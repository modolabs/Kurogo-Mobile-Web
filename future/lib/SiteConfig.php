<?php

class SiteConfig {
  private $configVars = array();
  private $sectionVars = array();
  private $webAppVars = array();
  private $apiVars = array();
  
  static private $testVars = null;

  public function loadWebAppFile($name, $section = true, $ignoreError = false) {
    if (!in_array($name, array_keys($this->webAppVars))) {
      $file = realpath_exists(SITE_CONFIG_DIR."/web/$name.ini");
      if ($file) {
        $this->webAppVars[$name] = parse_ini_file($file, $section);
        $this->replaceWebAppVariables($this->webAppVars[$name]);
        return true;

      } else {
        if (!$ignoreError) {
          error_log(__FUNCTION__."(): no web application configuration file for '$name'");
        }
        return false;
      }
    }
    return true;
  }

  public function loadAPIFile($name, $section = true, $ignoreError = false) {
    if (!in_array($name, array_keys($this->apiVars))) {
      $file = realpath_exists(SITE_CONFIG_DIR."/api/$name.ini");
      if ($file) {
        $this->apiVars[$name] = parse_ini_file($file, $section);
        $this->replaceAPIVariables($this->apiVars[$name]);
        return true;

      } else {
        if (!$ignoreError) {
          error_log(__FUNCTION__."(): no api configuration file for '$name'");
        }
        return false;
      }
    }
    return true;
  }

  // -------------------------------------------------------------------------

  public function getWebAppVar($key, $subKey = null, $ignoreError = false) {
    if (isset($this->webAppVars[$key])) {
      if (!isset($subKey)) {
        return $this->webAppVars[$key];
      } else if (isset($this->webAppVars[$key][$subKey])) {
        return $this->webAppVars[$key][$subKey];
      }
    }
    
    if (!$ignoreError) {
      error_log(__FUNCTION__."(): webAppVars['$key']".
        (isset($subKey) ? "['$subKey']" : "")." not set");
    }
    
    return null;
  }
  
  public function getAPIVar($key, $subKey = null, $ignoreError = false) {
    if (isset($this->apiVars[$key])) {
      if (!isset($subKey)) {
        return $this->apiVars[$key];
      } else if (isset($this->apiVars[$key][$subKey])) {
        return $this->apiVars[$key][$subKey];
      }
    }

    if (!$ignoreError) {
      error_log(__FUNCTION__."(): apiVars['$key']".
        (isset($subKey) ? "['$subKey']" : "")." not set");
    }

    return null;
  }
  
  public function getSection($key)
  {
    if (isset($this->sectionVars[$key])) {
      return $this->sectionVars[$key];
    }
    
    error_log(__FUNCTION__."(): configuration section '$key' not set");
    
    return null;
  }

  public function getVar($key) {
    if (isset($this->configVars[$key])) {
      return $this->configVars[$key];
    }
    
    error_log(__FUNCTION__."(): configuration variable '$key' not set");
    
    return null;
  }
  
  // -------------------------------------------------------------------------
  
  private static function _replaceVariablesCallback($matches) {
    if (isset(self::$testVars[$matches[1]])) {
      return self::$testVars[$matches[1]];
    } else {
      return $matches[0];
    }
  }
  
  private function replaceVariables(&$config, $testVars) {
    foreach($config as $key => &$value) {
      if (is_string($value)) {
        self::$testVars = $testVars;
        for ($i = 0; $i < 10; $i++) {
          $old = $value;
          $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', 
              array(get_class($this), '_replaceVariablesCallback'), $value);
          if ($value == $old) { break; }
        }
        self::$testVars = null;
        
      } else if (is_array($value)) {
        $this->replaceVariables($value, $testVars);
      }
    }
  }
  
  private function replaceWebAppVariables(&$config) {
    $testVars = $config;
    if (isset($this->webAppVars['site'])) {
      $testVars = array_merge($this->webAppVars['site'], $testVars);
    }
  
    // Handle key-relative paths by replacing keys with paths
    $this->replaceVariables($config, $testVars);
  }

  private function replaceAPIVariables(&$config) {
    // Handle key-relative paths by replacing keys with paths
    $this->replaceVariables($config, array_merge($this->configVars, $config));
  }

  private function replaceConfigVariables(&$config) {
    // Handle key-relative paths by replacing keys with paths
    $this->replaceVariables($config, $this->configVars);
  }
  
  /* merges together config variables by section */
  private function addSectionVars($sectionVars) {
    foreach ($sectionVars as $var=>$value) {
        if (isset($this->sectionVars[$var])) {
            $this->sectionVars[$var] = array_merge($this->sectionVars[$var], $value);
        } else {
            $this->sectionVars[$var] = $value;
        }
    }
  }
  
  // -------------------------------------------------------------------------
  
  private static function getPathOrDie($path) {
    $file = realpath_exists($path);
    if (!$file) {
      die("Missing config file at '$path'");
    }
    return $file;
  }
  
  private static function getVarOrDie($file, $vars, $key) {
    if (!isset($vars[$key])) {
      die("Missing '$key' definition in '$file'");
    }
    return $vars[$key];
  }

  // -------------------------------------------------------------------------

  function __construct() {
    // Load main configuration file
    $file = MASTER_CONFIG_DIR."/config.ini";
    $this->configVars = parse_ini_file(self::getPathOrDie($file), false); 
    $this->addSectionVars(parse_ini_file(self::getPathOrDie($file), true));
    $this->replaceConfigVariables($this->configVars);

    $siteDir  = self::getVarOrDie($file, $this->configVars, 'SITE_DIR');
    $siteMode = self::getVarOrDie($file, $this->configVars, 'SITE_MODE');

    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $this->configVars['SITE_DIR']);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('LOG_DIR',              SITE_DIR.'/logs');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    // Load site configuration file
    $this->configVars = array_merge($this->configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config.ini"), false));   
    $this->addSectionVars(parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config.ini"), true));
    $this->replaceConfigVariables($this->configVars);

    // Load site mode configuration file
    $this->configVars = array_merge($this->configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config-$siteMode.ini"), false));   
    $this->addSectionVars(parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config-$siteMode.ini"), true));
    $this->replaceConfigVariables($this->configVars);
    
    // Set up theme define
    define('THEME_DIR', SITE_DIR.'/themes/'.$this->configVars['ACTIVE_THEME']);

    //error_log(print_r($this->configVars, true));
  }
}
