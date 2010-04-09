<?php
require_once ('mobi-config/mobi_web_constants.php');

if(is_dir(AUX_PATH)) {
  create_dirs(AUX_PATH, array('logs', 'tmp', 'pushd', 'maptiles'));
  create_dirs(AUX_PATH.'/pushd', array('apns_feedback', 'apns_push', 'emergency', 'my_stellar', 'shuttle'));
  create_dirs(AUX_PATH.'/maptiles', array('raw', 'crushed'));

  // create the symlink that exposes maptiles to outside http requests
  if(is_link('mobi-web/api/map/tile')) {
    unlink('mobi-web/api/map/tile');
  }
  symlink(AUX_PATH . '/maptiles/crushed', 'mobi-web/api/map/tile');
   
} else {
  echo AUX_PATH . " does not yet exist, this directory needs to be created with the same permissions as webserver";
}

function create_dir($path) {
  if(file_exists($path)) {
    if(!is_dir($path)) {
      throw new Exception("$path already exists AND is not a directory, it needs to be a directory");
    }
  } else {
    mkdir($path);
  }
}

function create_dirs($base, $dirs) {
  foreach($dirs as $dir) {
    create_dir("$base/$dir");
  }
}

 
?>