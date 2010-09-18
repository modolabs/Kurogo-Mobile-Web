<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/../lib/initialize.php';
  
//error_log(print_r($_GET, true));
//error_log('Handling '.$_SERVER['REQUEST_URI']);


//
// Get q variable
//

$q = isset($_GET['q']) ? $_GET['q'] : '';


//
// Configure web application
//

InitializeWebapp($q); // modifies q for us if user set device in url


//
// Handle page request
//

if (preg_match(';^.*(modules|common)(/.*images)/(.*)$;', $q, $matches)) {
  //
  // Images
  //
  
  //error_log("Detected image request");
  
  $path = TEMPLATES_DIR.'/'.$matches[1].$matches[2];
  $file = $matches[3];

  $platform = $GLOBALS['deviceClassifier']->getPlatform();
  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    
  $testPaths = array(
    "$path/$pagetype-$platform/$file",
    "$path/$pagetype/$file",
    "$path/$file",
  );
  
  //error_log(print_r($testPaths, true));
  
  foreach ($testPaths as $testPath) {
    if (realpath($testPath)) {
      header('Content-type: '.mime_content_type($testPath));
      echo file_get_contents($testPath);
      exit;
    }        
  }
  header('Status: 404 Not Found');
  die;
  
} else {
  //
  // HTML Pages
  //
  
  //error_log("Detected page request");
  
  if (!strlen($q)) {
    if ($GLOBALS['deviceClassifier']->isComputer() || $GLOBALS['deviceClassifier']->isSpider()) {
      //header("Location: ./info/");
    } else {
      //header("Location: ./home/");
    }
  }
  
  require_once realpath(LIB_DIR.'/Module.php');
  
  $id = 'home';
  $page = 'index';
  $args = $_GET;
  unset($args['q']);
  
  $parts = explode('/', $q, 2);
  if (count($parts) > 1) {
    $id = $parts[0];
    if (strlen($parts[1])) {
      $page = basename($parts[1], '.php');
    }
  }
  
  $module = Module::factory($id);
  $module->displayPage($page, $args);
}