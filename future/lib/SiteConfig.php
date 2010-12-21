<?php

class SiteConfig extends ConfigGroup {

  function __construct() {
    // Load main configuration file
    $config = ConfigFile::factory(MASTER_CONFIG_DIR."/config.ini", 'file', ConfigFile::OPTION_DIE_ON_FAILURE);
    $this->addConfig($config);

    //make sure active site is set    
    if (!$site = $this->getVar('ACTIVE_SITE')) {
        die("FATAL ERROR: ACTIVE_SITE not set");
    }
    
    //make sure site_dir is set and is a valid path
    if (!($siteDir = $this->getVar('SITE_DIR')) || !($siteDir = realpath_exists($siteDir))) {
        die("FATAL ERROR: Site Directory ". $this->getVar('SITE_DIR') . " not found for site " . $site);
    }
    
    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $siteDir);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('LOG_DIR',              SITE_DIR.'/logs');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    //load in the site config file (required);
    $config = ConfigFile::factory(SITE_CONFIG_DIR."/config.ini", 'file', ConfigFile::OPTION_DIE_ON_FAILURE);
    $this->addConfig($config);

    // Set up theme define
    if (!$theme = $this->getVar('ACTIVE_THEME')) {
        die("FATAL ERROR: ACTIVE_THEME not set");
    }
    
    define('THEME_DIR', SITE_DIR.'/themes/'.$theme);
  }

}
