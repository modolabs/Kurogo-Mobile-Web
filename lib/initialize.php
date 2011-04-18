<?php
/**
  * @package Core
  */
  
//
// Initialization setup
// 
// $path    - an optional path portion of the request uri which will be 
//            stripped of the base url and device classifier if present
//            If you want to force a specific device, you can specify 
//            /device/[device]/ as the path.
//

/**
  * change if this file is moved
  */
define('ROOT_DIR', dirname(__FILE__).'/..'); 



/**
  * 
  */
function Initialize(&$path=null) {
  //
  // Constants which cannot be set by config file
  //
  
  define('WEBROOT_DIR',       realpath(ROOT_DIR.'/www')); 
  define('LIB_DIR',           realpath(ROOT_DIR.'/lib'));
  define('MASTER_CONFIG_DIR', realpath(ROOT_DIR.'/config'));
  define('APP_DIR',           realpath(ROOT_DIR.'/app'));
  define('MODULES_DIR',       realpath(APP_DIR.'/modules'));
  define('MIN_FILE_PREFIX', 'file:');
  define('API_URL_PREFIX', 'rest');
  
  //
  // Pull in functions to deal with php version differences
  //
  
  require(LIB_DIR . '/Kurogo.php');
  require(LIB_DIR . '/compat.php');

  $Kurogo = Kurogo::sharedInstance();
  spl_autoload_register(array($Kurogo, "siteLibAutoloader"));
  
  //
  // Load configuration files
  //    
  
  $GLOBALS['siteConfig'] = new SiteConfig();
  ini_set('display_errors', Kurogo::getSiteVar('DISPLAY_ERRORS'));
  if (!ini_get('error_log')) {
     ini_set('error_log', LOG_DIR . '/php_error.log');
  }

  date_default_timezone_set(Kurogo::getSiteVar('LOCAL_TIMEZONE'));

  //
  // Set up host define for server name and port
  //
  
  $host = $_SERVER['SERVER_NAME'];
  if (isset($_SERVER['HTTP_HOST']) && strlen($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    
  } else if (isset($_SERVER['SERVER_PORT'])) {
    $host .= ":{$_SERVER['SERVER_PORT']}";
  }
  define('SERVER_HOST', $host);
  
  
  //
  // And a double quote define for ini files (php 5.1 can't escape them)
  //
  define('_QQ_', '"');
  

  //
  // Get URL base
  //
  
  $pathParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI'])));
  
  $testPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
  $urlBase = '/';
  $foundPath = false;
  if (realpath($testPath) != WEBROOT_DIR) {
    foreach ($pathParts as $dir) {
      $test = $testPath.$dir.DIRECTORY_SEPARATOR;
      
      if (realpath_exists($test)) {
        $testPath = $test;
        $urlBase .= $dir.'/';
        if (realpath($test) == WEBROOT_DIR) {
          $foundPath = true;
          break;
        }
      }
    }
  }
  define('URL_BASE', $foundPath ? $urlBase : '/');
  define('IS_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
  define('FULL_URL_BASE', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_BASE);
  define('COOKIE_PATH', URL_BASE); // We are installed under URL_BASE

  //
  // Install exception handlers
  //
  require(LIB_DIR.'/exceptions.php');
  
  if(Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
    set_exception_handler("exceptionHandlerForProduction");
  } else {
    set_exception_handler("exceptionHandlerForDevelopment");
  }
  
  // Strips out the leading part of the url for sites where 
  // the base is not located at the document root, ie.. /mobile or /m 
  // Also strips off the leading slash (needed by device debug below)
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
  $urlDeviceDebugPrefix = '/';
  
  // Check for device classification in url and strip it if present
  if (Kurogo::getSiteVar('DEVICE_DEBUG') && 
      preg_match(';^device/([^/]+)/(.*)$;', $path, $matches)) {
    $device = $matches[1];  // layout forced by url
    $path = $matches[2];
    $urlPrefix .= "device/$device/";
    $urlDeviceDebugPrefix .= "device/$device/";
  }
  
  define('URL_DEVICE_DEBUG_PREFIX', $urlDeviceDebugPrefix);
  define('URL_PREFIX', $urlPrefix);
  define('FULL_URL_PREFIX', 'http'.(IS_SECURE ? 's' : '').'://'.$_SERVER['HTTP_HOST'].URL_PREFIX);

  //error_log(__FUNCTION__."(): prefix: $urlPrefix");
  //error_log(__FUNCTION__."(): path: $path");
  $GLOBALS['deviceClassifier'] = new DeviceClassifier($device);
}
