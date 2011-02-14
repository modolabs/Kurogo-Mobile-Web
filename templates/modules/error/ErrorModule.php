<?php
/**
  * @package Module
  * @subpackage Error
  */

/**
  * @package Module
  * @subpackage Error
  */
class ErrorModule extends Module {
  protected $id = 'error';

  private $errors = array(
    'data' => array(
      'status'  => '504 Gateway Timeout',
      'message' => 'We are sorry the server is currently experiencing errors. Please try again later.',
    ),
    'internal' => array(
      'status'  => '500 Internal Server Error',
      'message' => 'Internal server error',
    ),
    'notfound' => array(
      'status'  => '404 Not Found',
      'message' => 'Page not found',
    ),
    'forbidden' => array(
      'status'  => '403 Forbidden',
      'message' => 'Not authorized to view this page',
    ),
    'device_notsupported' => array(
      'status'  => null,
      'message' => 'This functionality is not supported on this device',
    ),
    'disabled'  => array(
      'message' =>  'This module has been disabled'
    ),
    'protected' => array(
      'message' =>  'This module requires you to login',
      'linkText' => 'Click here to login'
    ),
    'protectedACL' => array(
      'message' =>  'You are not permitted to use this module',
      'linkText' => 'Click here to login'
    ),
    'default' => array(
      'status'  => '500 Internal Server Error',
      'message' => 'Unknown error',
    )
  );

  protected function factoryInit($page, $args) {
      $this->pagetype = $GLOBALS['deviceClassifier']->getPagetype();
      $this->page = 'index';
      $this->setTemplatePage($this->page, $this->id);
      $this->args = $args;
      return;
  }

  protected function initializeForPage() {
    $code = $this->getArg('code', 'default');
    $url  = $this->getArg('url', '');
    
    $error = isset($this->errors[$code]) ? 
      $this->errors[$code] : $this->errors['default'];;
    
    if (isset($error['status'])) {
      header('Status: '.$error['status']);
    }

    if (isset($error['linkText'])) {
        $this->assign('linkText', $error['linkText']);
    }
    
    $this->assign('navImageID', 'about');
    if($this->devError() === false){
      $this->assign('message', $error['message']);
    } else {
      $this->assign('message', nl2br($this->devError()));
    }
    $this->assign('url', $url);
  }
  
  protected function devError() {
    
    // production
    if($this->getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
      return false;
    }
      
    // check for development errors
    if(isset($_GET['error'])){
      $file = $path =  CACHE_DIR . "/errors/" . $_GET['error'] . ".log";
      if(file_exists($file) && $handle = fopen($file, "r")) {
        $msg = fread($handle, filesize($file));
        fclose($handle);
        return $msg;
      }
    }
    
    return false;
  }
  
}
