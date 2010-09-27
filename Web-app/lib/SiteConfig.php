<?php

class SiteConfig {
  private $configVars = array();
  private $themeVars = array();
  
  public function loadThemeFile($name, $section = true) {
    if (!in_array($name, array_keys($this->themeVars))) {
      $file = realpath($this->getVar('THEME_CONFIG_DIR')."/$name.ini");
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
  
  private function setDefaults($defs) {
    foreach($defs as $key => $value) {
      if (!isset($this->configVars[$key])) { 
        $this->configVars[$key] = $value; 
      }
    }
  }

  function __construct($configName = 'config') {
    // Load main configuration file
    if (!in_array($configName, array_keys($this->themeVars))) {
      $fileVars = parse_ini_file(realpath(CONFIG_DEFS_DIR."/$configName.ini"), false);
      
      $siteFile = realpath(CONFIG_SITE_DIR."/config.ini");
      if ($siteFile) {
        $fileVars = array_merge($fileVars, parse_ini_file($siteFile, false));
      }
            
      $this->configVars = $fileVars;
    }
    
    // Set default directories if variables are not set in the config file
    $this->setDefaults(array( 
      'THEMES_DIR'               => ROOT_DIR.'/opt/themes',
      'DATA_DIR'                 => ROOT_DIR.'/opt/data',
      'CACHE_DIR'                => ROOT_DIR.'/opt/cache',

      'TMP_DIR'                  => '/tmp/',
    
      'MODULES_DIR'              => TEMPLATES_DIR.'/modules',
      'TEMPLATE_CONFIG_DEFS_DIR' => TEMPLATES_DIR.'/config',
    ));
    
    $this->setDefaults(array( 
      'THEME_DIR' => $this->configVars['THEMES_DIR'].'/'.$this->configVars['ACTIVE_THEME'],
    ));
    
    // Set default subdirectories if variables are not set in the config file
    // Use separate pass so we can make subdirectory defaults relative to the directories above
    $this->setDefaults(array(
      'WHATS_NEW_PATH'            => $this->configVars['DATA_DIR'].'/whatsnew.xml',

      'GAZETTE_CACHE_PATH'        => $this->configVars['CACHE_DIR'].'/GAZETTE',
      'GAZETTE_SEARCH_CACHE_PATH' => $this->configVars['CACHE_DIR'].'/GAZETTE_SEARCH',
      'GAZETTE_IMAGE_CACHE_PATH'  => $this->configVars['CACHE_DIR'].'/GAZETTE_IMAGES',
      
      'TEMPLATE_CACHE_DIR'        => $this->configVars['CACHE_DIR'].'/smarty/html',
      'TEMPLATE_COMPILE_DIR'      => $this->configVars['CACHE_DIR'].'/smarty/templates',
      'MINIFY_CACHE_DIR'          => $this->configVars['CACHE_DIR'].'/minify',

      'THEME_CONFIG_DIR'          => $this->configVars['THEME_DIR'].'/config',
    ));

  }
}
