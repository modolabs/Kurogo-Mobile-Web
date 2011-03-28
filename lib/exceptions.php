<?php

/**
  * @package Exceptions
  *
  */

/**
  *  Returned when there is a problem returning data from a server
  * @package Exceptions
  */
class DataServerException extends Exception {
}

/**
  * @package Exceptions
  */
class DeviceNotSupported extends Exception {
}

/**
  * @package Exceptions
  */
class ModuleNotFound extends Exception {
}

/**
  * @package Exceptions
  */
class DisabledModuleException extends Exception {
}

/**
  */
function getErrorURL($exception, $devError = false) {
  $args = array(
    'code' => 'internal',
    'url' => $_SERVER['REQUEST_URI'],
  );
  
  if ($exception instanceOf DataServerException) {
    $args['code'] = 'data';
  
  } else if ($exception instanceOf DeviceNotSupported) {
    $args['code'] = 'device_notsupported';
    
  } else if ($exception instanceOf ModuleNotFound) {
    $args['code'] = 'notfound';
    
  } else if ($exception instanceOf DisabledModuleException) {
    $args['code'] = 'forbidden';
  }
  
  if($devError){
    $args['error'] = $devError;
  }
  
  return URL_PREFIX.'error/?'.http_build_query($args);
}

/**
  * When in development, see your errors on the page instead of opening error log
  */
function developmentErrorLog($exception){
  $path =  CACHE_DIR . "/errors/";
  if (!file_exists($path)) {
    if (!mkdir($path, 0755, true)){
      error_log("DEV Error: could not create $path");
      return false;
    }
  } 
  
  $time = date("Y-m-d_H-i-s");
  $file = $path . $time . '.log';
  
  if (!$handle = fopen($file, 'w')) {
    error_log("DEV Error: could open file $file");
    return false;
  }
  
  // create Error message
  // with help from
  // http://www.php.net/manual/en/function.set-exception-handler.php#98201
  
  // these are our templates
  $traceline = "#%s %s(%s): %s(%s)";
  $msg = "<br><strong>Fatal error</strong>:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in <strong>%s</strong> on line <strong>%s</strong>";

  // alter your trace as you please, here
  $trace = $exception->getTrace();
  foreach ($trace as $key => $stackPoint) {
    if (isset($trace[$key]['args'])) {
      // I'm converting arguments to their type
      // (prevents passwords from ever getting logged as anything other than 'string')
      $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
    }
  }

  // build your tracelines
  $result = array();
  foreach ($trace as $key => $stackPoint) {
    $stackPoint['file'] = isset($stackPoint['file']) ? $stackPoint['file'] : 'Unknown';
    $stackPoint['line'] = isset($stackPoint['line']) ? $stackPoint['line'] : 'Unknown';
    $stackPoint['function'] = isset($stackPoint['function']) ? $stackPoint['function'] : '';
    $stackPoint['args'] = isset($stackPoint['args']) ? $stackPoint['args'] : array();

    $result[] = sprintf(
      $traceline,
      $key,
      $stackPoint['file'],
      $stackPoint['line'],
      $stackPoint['function'],
      implode(', ', $stackPoint['args'])
    );
  }
  // trace always ends with {main}
  $result[] = '#' . ++$key . ' {main}';

  // write tracelines into main template
  $msg = sprintf(
      $msg,
      get_class($exception),
      $exception->getMessage(),
      $exception->getFile(),
      $exception->getLine(),
      implode("\n", $result),
      $exception->getFile(),
      $exception->getLine()
  );
  
  $msg = '<strong>' . $exception->getMessage() . '</strong><br>' . $msg;
  
  if (fwrite($handle, $msg) === FALSE) {
    error_log("DEV Error: could not write to file $file");
    return false;
  }
  
  @fclose($handle);
  return $time;
  
}

/**
  * Exception Handler set in initialize.php
  */
function exceptionHandlerForError($exception) {
    $error = print_r($exception, TRUE);
    die("There was a serious error: $error");
}

function exceptionHandlerForDevelopment($exception) {
  $errtime = developmentErrorLog($exception);
  error_log(print_r($exception, TRUE));
  header('Location: '.getErrorURL($exception, $errtime));
}

/**
  * Exception Handler set in initialize.php
  */
function exceptionHandlerForProduction($exception) {
  if(!$GLOBALS['deviceClassifier']->isSpider()) {
    mail(Kurogo::getSiteVar('DEVELOPER_EMAIL'), 
      "Mobile web page experiencing problems",
      "The following page is throwing exceptions:\n\n" .
      "URL: http".(IS_SECURE ? 's' : '')."://".SERVER_HOST."{$_SERVER['REQUEST_URI']}\n" .
      "User-Agent: \"{$_SERVER['HTTP_USER_AGENT']}\"\n" .
      "Referrer URL: \"{$_SERVER['HTTP_REFERER']}\"\n" .
      "Exception:\n\n" . 
      var_export($exception, true)
    );
  }

  header('Location: '.getErrorURL($exception));
  die(0);
}

function exceptionHandlerForAPI($exception) {
    $error = KurogoError::errorFromException($exception);
    $response = new APIResponse();
    $response->setVersion(0);
    $response->setError($error);
    $response->display();
}
