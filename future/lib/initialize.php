<?php

//
// Initialization setup
// 
// $path    - an optional path portion of the request uri which will be 
//            stripped of the base url and device classifier if present
//            If you want to force a specific device, you can specify 
//            /device/[device]/ as the path.
//

define('ROOT_DIR', dirname(__FILE__).'/..'); // change if this file is moved

function Initialize(&$path=null) {
  //
  // Constants which cannot be set by config file
  //
  
  define('WEBROOT_DIR',       ROOT_DIR.'/web'); 
  define('LIB_DIR',           ROOT_DIR.'/lib');
  define('MASTER_CONFIG_DIR', ROOT_DIR.'/config');
  define('TEMPLATES_DIR',     ROOT_DIR.'/templates');
  define('MODULES_DIR',       TEMPLATES_DIR.'/modules');
  
  
  //
  // Pull in functions to deal with php version differences
  //
  
  require_once(ROOT_DIR.'/lib/compat.php');

  //
  // Set up library autoloader
  //
  
  require_once realpath(LIB_DIR.'/autoloader.php');
  
  spl_autoload_register("siteLibAutoloader");


  //
  // Get URL base
  //
  
  define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
  
  $pathParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI'])));
  
  $testPath = DOCUMENT_ROOT.DIRECTORY_SEPARATOR;
  $urlBase = '/';
  if (realpath($testPath) != realpath(WEBROOT_DIR)) {
    foreach ($pathParts as $dir) {
      $test = $testPath.$dir.DIRECTORY_SEPARATOR;
      
      if (realpath_exists($test)) {
        $testPath = $test;
        $urlBase .= $dir.'/';
        if (realpath($test) == realpath(WEBROOT_DIR)) {
          break;
        }
      }
    }
  }
  define('URL_BASE', $urlBase);
  define('FULL_URL_BASE', sprintf("http://%s%s", $_SERVER['HTTP_HOST'], URL_BASE));

  define('COOKIE_PATH', URL_BASE); // We are installed under URL_BASE

  //
  // Load configuration files
  //    
  
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
    
  if (isset($path)) {
    // Strip the URL_BASE off the path
    $baseLen = strlen(URL_BASE);
    if ($baseLen && strpos($path, URL_BASE) === 0) {
      $path = substr($path, $baseLen);
    }
  }  




  //
  // Initialize global device classifier
  //
  
  $device = null;
  $urlPrefix = URL_BASE;
  
  // Check for device classification in url and strip it if present
  if ($GLOBALS['siteConfig']->getVar('DEVICE_DEBUG') && 
      preg_match(';^device/([^/]+)(/.*)$;', $path, $matches)) {
    $device = $matches[1];  // layout forced by url
    $path = $matches[2];
    $urlPrefix .= "device/$device/";
  }
  
  define('URL_PREFIX', $urlPrefix);

  //error_log(__FUNCTION__."(): prefix: $urlPrefix");
  //error_log(__FUNCTION__."(): path: $path");

  if (isset($device) || isset($_SERVER['HTTP_USER_AGENT']) && strlen($_SERVER['HTTP_USER_AGENT'])) {
    require_once realpath(LIB_DIR.'/DeviceClassifier.php');
    
    $GLOBALS['deviceClassifier'] = new DeviceClassifier($device);
  }
  
  
  //
  // Set timezone
  //
  
  date_default_timezone_set($GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE'));

}
