<?php

function InitializeWebapp(&$path) {
  //
  // Constants which cannot be set by config file
  //
  
  define('ROOT_DIR',        $_SERVER['DOCUMENT_ROOT'].'/..');
  define('LIB_DIR',         ROOT_DIR.'/lib');
  define('TEMPLATES_DIR',   ROOT_DIR.'/templates');
  define('CONFIG_DEFS_DIR', ROOT_DIR.'/config');
  define('CONFIG_SITE_DIR', ROOT_DIR.'/opt/config');

  // Constant for now
  define('COOKIE_PATH', '/');


  //
  // Lazy load configuration files
  //    
  
  require_once realpath(LIB_DIR.'/SiteConfig.php');
  
  $GLOBALS['siteConfig'] = new SiteConfig();
  
  
  //
  // Install exception handlers
  //
  
  require_once realpath(LIB_DIR.'/exceptions.php');
  
  if($GLOBALS['siteConfig']->getVar('USE_PRODUCTION_ERROR_HANDLER')) {
    set_exception_handler("exceptionHandlerForProduction");
  } else {
    set_exception_handler("exceptionHandlerForDevelopment");
  }
  
  
  //
  // Initialize global device classifier
  //
  
  require_once realpath(LIB_DIR.'/DeviceClassifier.php');
  
  $layout = null;
  if ($GLOBALS['siteConfig']->getVar('DEVICE_DEBUG') && 
      preg_match(';^(.*)device/([^/]+)/(.*)$;', $path, $matches)) {
    $layout = $matches[2];
    $path = $matches[1].$matches[3];
  }
  
  $GLOBALS['deviceClassifier'] = new DeviceClassifier($layout);
}
