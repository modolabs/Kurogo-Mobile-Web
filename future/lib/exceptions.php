<?php

//
// Helper functions and classes
//

class DataServerException extends Exception {
}

class DeviceNotSupported extends Exception {
}

class PageNotFound extends Exception {
}

class DisabledModuleException extends Exception {
}

function getErrorURL($exception) {
  $args = array(
    'code' => 'internal',
    'url' => $_SERVER['REQUEST_URI'],
  );
  
  if (is_a($exception, "DataServerException")) {
    $args['code'] = 'data';
  
  } else if(is_a($exception, "DeviceNotSupported")) {
    $args['code'] = 'device_notsupported';
    
  } else if(is_a($exception, "PageNotFound")) {
    $args['code'] = 'notfound';
    
  } else if(is_a($exception, "DisabledModuleException")) {
    $args['code'] = 'forbidden';
  }
  
  return URL_PREFIX.'error/?'.http_build_query($args);
}

function exceptionHandlerForDevelopment($exception) {
  error_log(print_r($exception, TRUE));
  header('Location: '.getErrorURL($exception));
}

function exceptionHandlerForProduction($exception) {
  if(!$GLOBALS['deviceClassifier']->isSpider()) {
    $protocol = isset($_SERVER['HTTPS']) && strlen($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ?
      'https' : 'http';
  
    mail($GLOBALS['siteConfig']->getVar('DEVELOPER_EMAIL'), 
      "Mobile web page experiencing problems",
      "The following page is throwing exceptions:\n\n" .
      "URL: $protocol://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}\n" .
      "User-Agent: \"{$_SERVER['HTTP_USER_AGENT']}\"\n" .
      "Referrer URL: \"{$_SERVER['HTTP_REFERER']}\"\n" .
      "Exception:\n\n" . 
      var_export($exception, true)
    );
  }

  header('Location: '.getErrorURL($exception));
  die(0);
}
