<?php

function InitializeWebapp(&$path, $scriptPath) {
  //
  // Get URL base
  //
  define('ROOT_DIR',      dirname(__FILE__).'/..');
  define('WEBROOT_DIR',   ROOT_DIR.'/web'); 
  define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
  
  $pathParts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI'])));
  
  $testPath = DOCUMENT_ROOT.DIRECTORY_SEPARATOR;
  $urlBase = '/';
  if (realpath($testPath) != realpath(WEBROOT_DIR)) {
    foreach ($pathParts as $dir) {
      $test = $testPath.$dir.DIRECTORY_SEPARATOR;
      
      if (realpath($test)) {
        $testPath = $test;
        $urlBase .= $dir.'/';
        if (realpath($test) == realpath(WEBROOT_DIR)) {
          break;
        }
      }
    }
  }
  define('URL_BASE', $urlBase);

  // and strip the base off the path
  $baseLen = strlen(URL_BASE);
  if ($baseLen && strpos($path, URL_BASE) === 0) {
    $path = substr($path, $baseLen);
  }

  //
  // Constants which cannot be set by config file
  //
  
  define('LIB_DIR',         ROOT_DIR.'/lib');
  define('TEMPLATES_DIR',   ROOT_DIR.'/templates');
  define('CONFIG_DEFS_DIR', ROOT_DIR.'/config');
  define('CONFIG_SITE_DIR', ROOT_DIR.'/opt/config');

  // We are installed under URL_BASE
  define('COOKIE_PATH', URL_BASE);


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
  
  require_once realpath(LIB_DIR.'/DeviceClassifier.php');
  
  $GLOBALS['deviceClassifier'] = new DeviceClassifier($layout);
}
