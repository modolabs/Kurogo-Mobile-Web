<?php
require_once ('mobi-config/mobi_web_constants.php');

if(is_dir(AUX_PATH)) {
  mkdir(AUX_PATH . '/logs');
  mkdir(AUX_PATH . '/tmp');
} else {
  echo AUX_PATH . " does not yet exist, this directory needs to be created with the same permissions as webserver";
}
?>