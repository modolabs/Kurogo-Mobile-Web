<?php

class SiteConfig extends ConfigGroup {

  function __construct() {
    // Load main configuration file
    $config = ConfigFile::factory(MASTER_CONFIG_DIR."/config.ini", 'file', false, true);
    $this->addConfig($config);
    
    if (!$site = $this->getVar('ACTIVE_SITE')) {
        die("FATAL ERROR: ACTIVE_SITE not set");
    }
    
    if (!$siteDir  = realpath_exists($this->getVar('SITE_DIR'))) {
        die("FATAL ERROR: Site Directory ". $this->getVar('SITE_DIR') . " not found for site " . $site);
    }
    
    $siteMode = $this->getVar('SITE_MODE');
    
    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $siteDir);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('LOG_DIR',              SITE_DIR.'/logs');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    $config = ConfigFile::factory(SITE_CONFIG_DIR."/config.ini", 'file', false, true);
    $this->addConfig($config);

    $config = ConfigFile::factory(SITE_CONFIG_DIR."/config-$siteMode.ini");
    $this->addConfig($config);

    // Set up theme define
    define('THEME_DIR', SITE_DIR.'/themes/'.$this->getVar('ACTIVE_THEME'));
    //error_log(print_r($this->configVars, true));
  }

}
