<?php

require_once dirname(__FILE__).'/../lib/initialize.php';
  
//error_log(print_r($_GET, true));
//error_log('Handling '.$_SERVER['REQUEST_URI']);

//
// Get q variable
//

$path = isset($_GET['q']) ? $_GET['q'] : '';


//
// Configure web application
//

InitializeWebapp($path, dirname(__FILE__)); // modifies q for us to strip prefix and device


//
// Handle page request
//

if (preg_match(';^.*(modules|common)(/.*images)/(.*)$;', $path, $matches)) {
  //
  // Images
  //
  
  //error_log("Detected image request");
  
  $file = $matches[3];

  $platform = $GLOBALS['deviceClassifier']->getPlatform();
  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
  
  $testDirs = array(
    $GLOBALS['siteConfig']->getVar('THEME_DIR').'/'.$matches[1].$matches[2],
    TEMPLATES_DIR.'/'.$matches[1].$matches[2],
  );
  $testFiles = array(
    "$pagetype-$platform/$file",
    "$pagetype/$file",
    "$file",
  );
  
  //error_log(print_r($testPaths, true));
  
  foreach ($testDirs as $dir) {
    foreach ($testFiles as $file) {
      $path = "$dir/$file";
      if (realpath($path)) {
        header('Content-type: '.mime_content_type($path));
        echo file_get_contents($path);
        exit;
      }        
    }
  }
  header('Status: 404 Not Found');
  die;
  
} else {
  //
  // HTML Pages
  //
  
  //error_log("Detected page request");
  
  require_once realpath(LIB_DIR.'/Module.php');
  
  $id = 'home';
  $page = 'index';
  
  $args = $_GET;
  unset($args['q']);
  if (get_magic_quotes_gpc()) {
    function deepStripSlashes($v) {
      return is_array($v) ? array_map('deepStripSlashes', $v) : stripslashes($v);
    }
    deepStripslashes($args);
  }
  
  if (!strlen($path) || $path == '/') {
    error_log('flarg');
    if ($GLOBALS['deviceClassifier']->isComputer() || $GLOBALS['deviceClassifier']->isSpider()) {
      header("Location: ./info/");
      error_log('blarg');
    } else {
      header("Location: ./home/");
      error_log('alarg');
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
  
  $module = Module::factory($id);
  $module->displayPage($page, $args);
}
