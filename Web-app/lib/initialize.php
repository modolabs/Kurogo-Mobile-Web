<?php


// Helper functions
function _initializeSiteConfig($rootDir) {
  //
  // Constants which cannot be set by config file
  //
  
  define('ROOT_DIR', $rootDir);

  define('WEBROOT_DIR',       ROOT_DIR.'/web'); 
  define('LIB_DIR',           ROOT_DIR.'/lib');
  define('MASTER_CONFIG_DIR', ROOT_DIR.'/config');
  define('TEMPLATES_DIR',     ROOT_DIR.'/templates');
  define('MODULES_DIR',       TEMPLATES_DIR.'/modules');
  
  
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
  define('COOKIE_PATH', URL_BASE); // We are installed under URL_BASE


  //
  // Load configuration files
  //    
  
  require_once realpath(LIB_DIR.'/SiteConfig.php');
  
  $GLOBALS['siteConfig'] = new SiteConfig();
}


function _initializeExceptionHandlers() {
  require_once realpath(LIB_DIR.'/exceptions.php');
  
  if($GLOBALS['siteConfig']->getVar('USE_PRODUCTION_ERROR_HANDLER')) {
    set_exception_handler("exceptionHandlerForProduction");
  } else {
    set_exception_handler("exceptionHandlerForDevelopment");
  }
}



function _initializeDeviceClassifier($layout=null) {
  require_once realpath(LIB_DIR.'/DeviceClassifier.php');
  
  $GLOBALS['deviceClassifier'] = new DeviceClassifier($layout);
}


//
// Used by web apis and some unit tests
// Does not include setup for UI components
//
function InitializeForWebAPI($rootDir) {
  // Pull in functions to deal with php version differences
  require_once($rootDir.'/lib/compat.php');


  // Set up paths and site defines
  _initializeSiteConfig($rootDir);
  
  
  // Install exception handlers
  _initializeExceptionHandlers();  
}


//
// Used by the Mobile Web platform
// Includes all UI initialization
//
function InitializeForWebSite($rootDir, &$path, $scriptPath) {

  // Set up paths, site defines and exception handlers
  InitializeForWebAPI($rootDir);
    
  
  // Strip the URL_BASE off the path
  $baseLen = strlen(URL_BASE);
  if ($baseLen && strpos($path, URL_BASE) === 0) {
    $path = substr($path, $baseLen);
  }


  // Check for device classification in url and strip it if present
  $layout = null;
  $urlPrefix = URL_BASE;
  if ($GLOBALS['siteConfig']->getVar('DEVICE_DEBUG') && 
      preg_match(';^device/([^/]+)(/.*)$;', $path, $matches)) {
    $layout = $matches[1];  // layout forced by url
    $path = $matches[2];
    $urlPrefix .= 'device/'.$layout.'/';
  }
  define('URL_PREFIX', $urlPrefix);
  
  //error_log(__FUNCTION__."(): prefix: $urlPrefix");
  //error_log(__FUNCTION__."(): path: $path");
  

  // Initialize global device classifier
  _initializeDeviceClassifier($layout);
}
