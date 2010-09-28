<?php

class SiteConfig {
  private $configVars = array();
  private $themeVars = array();
  

  public function loadThemeFile($name, $section = true) {
    if (!in_array($name, array_keys($this->themeVars))) {
      $file = realpath_exists($this->getVar('THEME_CONFIG_DIR')."/$name.ini");
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
    
    error_log(__FUNCTION__."(): configVar['$key'] not set");
    
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
  
  /*private function setDefaults($defs) {
    foreach($defs as $key => $value) {
      if (!isset($this->configVars[$key])) { 
        $this->configVars[$key] = $value; 
      }
    }
  }*/
  
  private static function replaceVariables(&$config) {
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

  function __construct($configName = 'config') {
    // Load main configuration file
    $fileVars = parse_ini_file(realpath(CONFIG_DEFS_DIR."/$configName.ini"), false);
    
    $siteFile = realpath_exists(CONFIG_SITE_DIR."/config.ini");
    if ($siteFile) {
      $fileVars = array_merge($fileVars, parse_ini_file($siteFile, false));
    }
    
    // Handle key-relative paths by replacing keys with paths
    $GLOBALS['testVars'] = $fileVars;
    
    self::replaceVariables($fileVars);
    
    unset($GLOBALS['testVars']);
          
    $this->configVars = $fileVars;
    
    //error_log(print_r($fileVars, true));
  }
}
