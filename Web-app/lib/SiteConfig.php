<?php

class SiteConfig {
  private $configVars = array();
  private $themeVars = array();
  

  public function loadThemeFile($name, $section = true) {
    if (!in_array($name, array_keys($this->themeVars))) {
      $file = realpath_exists(THEME_CONFIG_DIR."/$name.ini");
      if ($file) {
        $this->themeVars[$name] = parse_ini_file($file, $section);
      } else {
        error_log(__FUNCTION__."(): no configuration file for '$name'");
      }
    }
  }

  public function getVar($key) {
    if (isset($this->configVars[$key])) {
      return $this->configVars[$key];
    }
    
    error_log(__FUNCTION__."(): configuration variable'$key' not set");
    
    return null;
  }

  public function getThemeVar($key, $subKey = null) {
    if (isset($this->themeVars[$key])) {
      if (!isset($subKey)) {
        return $this->themeVars[$key];
      } else if (isset($this->themeVars[$key][$subKey])) {
        return $this->themeVars[$key][$subKey];
      }
    }
    
    error_log(__FUNCTION__."(): themeVar['$key']".
      (isset($subKey) ? "['$subKey']" : "")." not set");
    
    return null;
  }
  
  private static function _replaceVariables(&$config) {
    foreach($config as $key => &$value) {
      if (is_string($value)) {
        for ($i = 0; $i < 10; $i++) {
          $old = $value;
          $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', 
            create_function(
              '$matches',
              'if (isset($GLOBALS["testVars"][$matches[1]])) { '.
              '  return $GLOBALS["testVars"][$matches[1]];'.
              '} else {'.
              '  return $matches[0];'.
              '}'
            ), $value);
          if ($value == $old) { break; }
        }
        
      } else if (is_array($value)) {
        self::replaceVariables($value);
      }
    }
  }

  private static function replaceVariables(&$config) {
    // Handle key-relative paths by replacing keys with paths
    $GLOBALS['testVars'] = $config;
    self::_replaceVariables($config);
    unset($GLOBALS['testVars']);
  }
  
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

  function __construct() {
    // Load main configuration file
    $file = MASTER_CONFIG_DIR."/config.ini";
    $configVars = parse_ini_file(self::getPathOrDie($file), false); 
    self::replaceVariables($configVars);

    $siteDir  = self::getVarOrDie($file, $configVars, 'SITE_DIR');
    $siteMode = self::getVarOrDie($file, $configVars, 'SITE_MODE');

    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $configVars['SITE_DIR']);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('THEMES_DIR',           SITE_DIR.'/themes');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    // Load site configuration file
    $configVars = array_merge($configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config.ini"), false));   
    self::replaceVariables($configVars);
    
    define('THEME_DIR',        THEMES_DIR.'/'.$configVars['ACTIVE_THEME']);
    define('THEME_CONFIG_DIR', THEME_DIR.'/config');

    // Load site mode configuration file
    $configVars = array_merge($configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config-$siteMode.ini"), false));   
    self::replaceVariables($configVars);
          
    $this->configVars = $configVars;
    
    //error_log(print_r($configVars, true));
  }
}
