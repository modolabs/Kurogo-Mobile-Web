<?php

/**
  * @package Exceptions
  *
  */
  
class KurogoException extends Exception {
    protected $sendNotification = true;
    protected $code = 'internal';
    
    public function shouldSendNotification() {
        return $this->sendNotification;
    }
}

/**
  *  Returned when there is a problem returning data from a server
  * @package Exceptions
  */
class KurogoDataServerException extends KurogoException {
    protected $code = 'server';
}

class DataServerException extends KurogoException {
    protected $code = 'server';
}

class KurogoDataException extends KurogoException {
    protected $code = 'data';
}

class KurogoConfigurationException extends KurogoException {
    protected $code = 'config';
}

class KurogoUserException extends KurogoException {
    protected $code = 'user';
}

class KurogoUnauthorizedException extends KurogoException {
    protected $code = 'forbidden';
}

/**
  * @package Exceptions
  */
class KurogoModuleNotFound extends KurogoException {
    protected $code = 'notfound';
    protected $sendNotification = false;
}

/**
  */
function getErrorURL($exception, $devError = false) {
	if (!defined('URL_PREFIX')) {
		return false; //the error occurred VERY early in the init process
	}
	
  $args = array_merge(array(
    'code' => $exception instanceOf KurogoException ? $exception->getCode() : 'internal',
    ), Kurogo::getArrayForRequest()
  );
  
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
      Kurogo::log(LOG_WARNING, "DEV Error: could not create $path", "exception");
      return false;
    }
  } 
  
  $time = date("Y-m-d_H-i-s");
  $file = $path . $time . '.log';
  
  if (!$handle = fopen($file, 'w')) {
    Kurogo::log(LOG_WARNING, "DEV Error: could not open file $file", "exception");
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
    Kurogo::log(LOG_WARNING, "DEV Error: could not write to file $file", "exception");
    return false;
  }
  
  @fclose($handle);
  return $time;
  
}

/**
  * Exception Handler set in Kurogo::initialize()
  */
function exceptionHandlerForError($exception) {
    $bt = $exception->getTrace();
    array_unshift($bt, array('line'=>$exception->getLine(), 'file'=>$exception->getFile()));
    Kurogo::log(LOG_ALERT, "A ". get_class($exception) . " has occured: " . $exception->getMessage(), "exception", $bt);
    $error = print_r($exception, TRUE);
    header('Content-type: text/plain');
    die("There was a serious error: " . $exception->getMessage());
}

function exceptionHandlerForDevelopment($exception) {
    $bt = $exception->getTrace();
    array_unshift($bt, array('line'=>$exception->getLine(), 'file'=>$exception->getFile()));
    Kurogo::log(LOG_ALERT, "A ". get_class($exception) . " has occured: " . $exception->getMessage(), "exception", $bt);
    $errtime = developmentErrorLog($exception);
    $error = print_r($exception, TRUE);
	
	if ($url = getErrorURL($exception, $errtime)) {
    	header('Location: ' . $url);
    	die(0);
    } else {
    	header('Content-type: text/plain');
		die("A serious error has occurred: \n\n" . $error);
    }
}

/**
  * Exception Handler set in Kurogo::initialize()
  */
function exceptionHandlerForProduction(Exception $exception) {
    $bt = $exception->getTrace();
    array_unshift($bt, array('line'=>$exception->getLine(), 'file'=>$exception->getFile()));
    Kurogo::log(LOG_ALERT, sprintf("A %s has occured: %s", get_class($exception), $exception->getMessage()), "exception", $bt);
    if ($exception instanceOf KurogoException) {
        $sendNotification = $exception->shouldSendNotification();
    } else {
        $sendNotification = true;
    }

    if ($sendNotification) {
        $to = Kurogo::getSiteVar('DEVELOPER_EMAIL');
        if (!Kurogo::deviceClassifier()->isSpider() && $to) {
            mail($to, 
              "Mobile web page experiencing problems",
              "The following page is throwing exceptions:\n\n" .
              "URL: http".(IS_SECURE ? 's' : '')."://".SERVER_HOST."{$_SERVER['REQUEST_URI']}\n" .
              "User-Agent: \"{$_SERVER['HTTP_USER_AGENT']}\"\n" .
              "Referrer URL: \"{$_SERVER['HTTP_REFERER']}\"\n" .
              "Exception:\n\n" . 
              var_export($exception, true)
            );
        }
    }

    if ($url = getErrorURL($exception)) {
		header('Location: ' . $url);
		die(0);
	} else {
		die("A serious error has occurred");
	}
}

function exceptionHandlerForAPI($exception) {
    $bt = $exception->getTrace();
    array_unshift($bt, array('line'=>$exception->getLine(), 'file'=>$exception->getFile()));
    Kurogo::log(LOG_ALERT, "A ". get_class($exception) . " has occured: " . $exception->getMessage(), "exception", $bt);
    $error = KurogoError::errorFromException($exception);
    $response = new APIResponse();
    $response->setVersion(0);
    $response->setError($error);
    $response->display();
    exit();
}

set_exception_handler('exceptionHandlerForError');