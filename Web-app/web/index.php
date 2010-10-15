<?php

require_once realpath(dirname(__FILE__).'/../lib/initialize.php');

//
// Configure web application
// modifies $path for us to strip prefix and device
//

$path = isset($_GET['q']) ? $_GET['q'] : '';

Initialize($path); 

//
// Handle page request
//

if (preg_match(';^.*(modules|common)(/.*images)/(.*)$;', $path, $matches)) {
  //
  // Images
  //
  
  $file = $matches[3];

  $platform = $GLOBALS['deviceClassifier']->getPlatform();
  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
  
  $testDirs = array(
    THEME_DIR.'/'.$matches[1].$matches[2],
    TEMPLATES_DIR.'/'.$matches[1].$matches[2],
  );
  $testFiles = array(
    "$pagetype-$platform/$file",
    "$pagetype/$file",
    "$file",
  );
  
  foreach ($testDirs as $dir) {
    foreach ($testFiles as $file) {
      $path = realpath_exists("$dir/$file");
      if ($path) {
        header('Content-type: '.mime_content_type($path));
        echo file_get_contents($path);
        exit;
      }        
    }
  }

} else if (preg_match(';^.*media/(.*)$;', $path, $matches)) {
  //
  // Media
  //

  $path = realpath_exists(SITE_DIR."/media/$matches[1]");
  if ($path) {
    header('Content-type: '.mime_content_type($path));
    echo file_get_contents($path);
    exit;
  }
  
} else if (preg_match(';^.*api/$;', $path, $matches)) {
  //
  // Native Interface API
  //  

  require_once realpath(LIB_DIR.'/PageViews.php');
  
  if (isset($_REQUEST['module']) && $_REQUEST['module']) {
    $id = $_REQUEST['module'];
    $path = realpath_exists(LIB_DIR."/api/$id.php");
    
    if ($path) {
      PageViews::log_api($id, $GLOBALS['deviceClassifier']->getPlatform());
      
      require_once($path);
      exit;
    }
  }
  
} else {
  //
  // Web Interface
  //
  
  require_once realpath(LIB_DIR.'/Module.php');
  require_once realpath(LIB_DIR.'/PageViews.php');
    
  $id = 'home';
  $page = 'index';
  
  $args = $_GET;
  unset($args['q']);
  if (get_magic_quotes_gpc()) {
    
    function deepStripSlashes($v) {
      return is_array($v) ? array_map('deepStripSlashes', $v) : stripslashes($v);
    }
    $args = deepStripslashes($args);
  }
  
  if (!strlen($path) || $path == '/') {
    if ($GLOBALS['deviceClassifier']->isComputer() || $GLOBALS['deviceClassifier']->isSpider()) {
      header("Location: ./info/");
    } else {
      header("Location: ./home/");
    }
  } else {  
    $parts = explode('/', ltrim($path, '/'), 2);

    if (count($parts) > 1) {
      $id = $parts[0];
      if (strlen($parts[1])) {
        $page = basename($parts[1], '.php');
      }
    }
  }

  PageViews::log_api($id, $GLOBALS['deviceClassifier']->getPlatform());
  
  $module = Module::factory($id, $page, $args);
  $module->displayPage();
  exit;
}

//
// Unsupported Request
//

header('Status: 404 Not Found');
die;
