<?php

class SiteConfig {
  private $configVars = array();
  private $themeVars = array();
  

  public function loadThemeFile($name, $section = true, $ignoreError = false) {
    if (!in_array($name, array_keys($this->themeVars))) {
      $file = realpath_exists(THEME_CONFIG_DIR."/$name.ini");
      if ($file) {
        $this->themeVars[$name] = parse_ini_file($file, $section);
        $this->replaceThemeVariables($this->themeVars[$name]);
        return true;

      } else if (!$ignoreError) {
        error_log(__FUNCTION__."(): no configuration file for '$name'");
      }
    }
    return true;
  }

  public function getVar($key) {
    if (isset($this->configVars[$key])) {
      return $this->configVars[$key];
    }
    
    error_log(__FUNCTION__."(): configuration variable'$key' not set");
    
    return null;
  }

  public function getThemeVar($key, $subKey = null, $ignoreError = false) {
    if (isset($this->themeVars[$key])) {
      if (!isset($subKey)) {
        return $this->themeVars[$key];
      } else if (isset($this->themeVars[$key][$subKey])) {
        return $this->themeVars[$key][$subKey];
      }
    }
    
    if (!$ignoreError) {
      error_log(__FUNCTION__."(): themeVar['$key']".
        (isset($subKey) ? "['$subKey']" : "")." not set");
    }
    
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
        self::_replaceVariables($value);
      }
    }
  }

  private function replaceConfigVariables(&$config) {
    // Handle key-relative paths by replacing keys with paths
    $GLOBALS['testVars'] = $this->configVars;
    self::_replaceVariables($config);
    unset($GLOBALS['testVars']);
  }

  private function replaceThemeVariables(&$config) {
    $testVars = $config;
    if (isset($this->themeVars['site'])) {
      $testVars = array_merge($this->themeVars['site'], $testVars);
    }
  
    // Handle key-relative paths by replacing keys with paths
    $GLOBALS['testVars'] = $testVars;
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
    $this->configVars = parse_ini_file(self::getPathOrDie($file), false); 
    $this->replaceConfigVariables($this->configVars);

    $siteDir  = self::getVarOrDie($file, $this->configVars, 'SITE_DIR');
    $siteMode = self::getVarOrDie($file, $this->configVars, 'SITE_MODE');

    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $this->configVars['SITE_DIR']);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('THEMES_DIR',           SITE_DIR.'/themes');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    // Load site configuration file
    $this->configVars = array_merge($this->configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config.ini"), false));   
    $this->replaceConfigVariables($this->configVars);
    
    define('THEME_DIR',        THEMES_DIR.'/'.$this->configVars['ACTIVE_THEME']);
    define('THEME_CONFIG_DIR', THEME_DIR.'/config');

    // Load site mode configuration file
    $this->configVars = array_merge($this->configVars, 
      parse_ini_file(self::getPathOrDie(SITE_CONFIG_DIR."/config-$siteMode.ini"), false));   
    $this->replaceConfigVariables($this->configVars);
    
    //error_log(print_r($this->configVars, true));
  }
}
