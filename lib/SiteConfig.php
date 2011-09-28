<?php
/**
  * @package Config
  */

/**
  * @package Config
  */
class SiteConfig extends ConfigGroup {

  function __construct() {
    // Load main configuration file
    $config = ConfigFile::factory('kurogo', 'project', ConfigFile::OPTION_IGNORE_MODE | ConfigFile::OPTION_IGNORE_LOCAL);
    $this->addConfig($config);

    define('CONFIG_MODE', $config->getVar('CONFIG_MODE'));
    define('CONFIG_IGNORE_LOCAL', $config->getVar('CONFIG_IGNORE_LOCAL'));

    //make sure active site is set    
    if (!$site = $this->getVar('ACTIVE_SITE')) {
        die("FATAL ERROR: ACTIVE_SITE not set");
    }
    
        // make sure site_dir is set and is a valid path
        // Do not call realpath_exists here because until SITE_DIR define is set
        // it will not allow files and directories outside ROOT_DIR
        if (!($siteDir = $this->getVar('SITE_DIR')) || !(($siteDir = realpath($siteDir)) && file_exists($siteDir))) {
        die("FATAL ERROR: Site Directory ". $this->getVar('SITE_DIR') . " not found for site " . $site);
    }
    
    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $siteDir);
    define('SITE_KEY',             md5($siteDir));
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('SITE_APP_DIR',         SITE_DIR.'/app');
    define('SITE_MODULES_DIR',     SITE_DIR.'/app/modules');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('LOG_DIR',              SITE_DIR.'/logs');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    //load in the site config file (required);
    $config = ConfigFile::factory('site', 'site');
    $this->addConfig($config);

    // Set up theme define
    if (!$theme = $this->getVar('ACTIVE_THEME')) {
        die("FATAL ERROR: ACTIVE_THEME not set");
    }
    
    define('THEME_DIR', SITE_DIR.'/themes/'.$theme);
  }

}
