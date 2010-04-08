<?php
require_once ('mobi-config/mobi_web_constants.php');

if(is_dir(AUX_PATH)) {
  create_dir(AUX_PATH . '/logs');
  create_dir(AUX_PATH . '/tmp');
  
  $pushd = AUX_PATH . '/pushd';
  create_dir($pushd);
  create_dir($pushd . '/apns_feedback');
  create_dir($pushd . '/apns_push');
  create_dir($pushd . '/emergency');
  create_dir($pushd . '/my_stellar');
  create_dir($pushd . '/shuttle');
  
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

?>