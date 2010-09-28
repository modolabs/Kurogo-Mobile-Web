<?php

if(!function_exists('mime_content_type')) {
  function mime_content_type($filename) {
    $mime_types = array(
    
      'txt'  => 'text/plain',
      'htm'  => 'text/html',
      'html' => 'text/html',
      'php'  => 'text/html',
      'css'  => 'text/css',
      'js'   => 'application/javascript',
      'json' => 'application/json',
      'xml'  => 'application/xml',
      'swf'  => 'application/x-shockwave-flash',
      'flv'  => 'video/x-flv',
      
      // images
      'png'  => 'image/png',
      'jpe'  => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg'  => 'image/jpeg',
      'gif'  => 'image/gif',
      'bmp'  => 'image/bmp',
      'ico'  => 'image/vnd.microsoft.icon',
      'tiff' => 'image/tiff',
      'tif'  => 'image/tiff',
      'svg'  => 'image/svg+xml',
      'svgz' => 'image/svg+xml',
      
      // archives
      'zip' => 'application/zip',
      'rar' => 'application/x-rar-compressed',
      'exe' => 'application/x-msdownload',
      'msi' => 'application/x-msdownload',
      'cab' => 'application/vnd.ms-cab-compressed',
      
      // audio/video
      'mp3' => 'audio/mpeg',
      'qt'  => 'video/quicktime',
      'mov' => 'video/quicktime',
      
      // adobe
      'pdf' => 'application/pdf',
      'psd' => 'image/vnd.adobe.photoshop',
      'ai'  => 'application/postscript',
      'eps' => 'application/postscript',
      'ps'  => 'application/postscript',
      
      // ms office
      'doc' => 'application/msword',
      'rtf' => 'application/rtf',
      'xls' => 'application/vnd.ms-excel',
      'ppt' => 'application/vnd.ms-powerpoint',
      
      // open office
      'odt' => 'application/vnd.oasis.opendocument.text',
      'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    
    $ext = strtolower(array_pop(explode('.', $filename)));
    
    if (array_key_exists($ext, $mime_types)) {
      return $mime_types[$ext];
      
    } elseif (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME);
      $mimetype = finfo_file($finfo, $filename);
      finfo_close($finfo);
      return $mimetype;
      
    } else {
      return 'application/octet-stream';
    }
  }
}

// Simulate PHP 5.3 behavior on 5.2
function realpath_exists($path) {
  $test = realpath($path);
  if (version_compare(PHP_VERSION, '5.3.0') >= 0 || 
      ($test && file_exists($test))) {
    return $test;
  } else {
    return false;
  }
}

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

  // and strip the base off the path
  $baseLen = strlen(URL_BASE);
  if ($baseLen && strpos($path, URL_BASE) === 0) {
    $path = substr($path, $baseLen);
  }

  // We are installed under URL_BASE
  define('COOKIE_PATH', URL_BASE);


  //
  // Constants which cannot be set by config file
  //
  
  define('LIB_DIR',                  ROOT_DIR.'/lib');
  define('TEMPLATES_DIR',            ROOT_DIR.'/templates');
  define('CONFIG_DEFS_DIR',          ROOT_DIR.'/config');
  define('CONFIG_SITE_DIR',          ROOT_DIR.'/opt/config');
  define('MODULES_DIR',              TEMPLATES_DIR.'/modules');
  define('TEMPLATE_CONFIG_DEFS_DIR', TEMPLATES_DIR.'/config');


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
