<?php
/**
  * @package Module
  * @subpackage Error
  */

/**
  * @package Module
  * @subpackage Error
  */
class ErrorWebModule extends WebModule {
  protected $id = 'error';
  protected $configModule = 'error';
  protected $moduleName = 'Error';
  protected $canBeAddedToHomeScreen = false;

    protected function getError($code) {
        static $errors = array(
            'server' => array(
              'status'    => '504 Gateway Timeout'
            ),
            'data' => array(
              'status'    => '500 Internal Server Error'
            ),
            'user'=> array(
              'status'    => '500 Internal Server Error'
            ),
            'config'=> array(
              'status'    => '500 Internal Server Error'
            ),
            'internal' => array(
              'status'  => '500 Internal Server Error',
            ),
            'notfound' => array(
              'status'  => '404 Not Found',
            ),
            'forbidden' => array(
              'status'  => '403 Forbidden',
            ),
            'disabled'  => array(
            ),
            'protected' => array(
            ),
            'default' => array(
              'status'  => '500 Internal Server Error',
            )
          );
        
        $code =   isset($errors[$code]) ? $code : 'default';
        $error = $errors[$code];
        $error['message'] = $this->getLocalizedString(strtoupper('ERROR_' . $code));
        return $error;
    }

    protected function init($page='', $args=array()) {
      if(!Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
        set_exception_handler("exceptionHandlerForError");
      }
      $this->pagetype = Kurogo::deviceClassifier()->getPagetype();
      $this->platform = Kurogo::deviceClassifier()->getPlatform();
      $this->page = 'index';
      $this->setTemplatePage($this->page, $this->id);
      $this->args = $args;
      $this->logView = Kurogo::getOptionalSiteVar('STATS_ENABLED', true) ? true : false;
      try {
          $this->moduleName = $this->getOptionalModuleVar('title', 'Error', 'module');
      } catch (KurogoConfigurationException $e) {
      }
      return;
  }

  protected function getAccessControlLists($type) {
    return array(AccessControlList::allAccess());
  }

  protected function initializeForPage() {
    $code = $this->getArg('code', 'default');
    $url = $this->buildURLFromArray($this->args);
    
    $error = $this->getError($code);
    
    if (isset($error['status'])) {
      header('Status: '.$error['status']);
    }

    $linkText = isset($error['linkText']) ? $error['linkText'] : $this->getLocalizedString('DEFAULT_LINK_TEXT');
    $this->assign('linkText', $linkText);
    
    if($this->devError() === false){
      $this->assign('message', $error['message']);
    } else {
      $this->assign('message', nl2br($this->devError()));
    }
    $this->assign('url', $url);
  }
  
  protected function devError() {
    
    // production
    if(Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
      return false;
    }
      
    // check for development errors
    if(isset($_GET['error'])){
      $path = explode('/', $_GET['error']);
      $sanitizedFileName = end($path);
      $file = $path =  CACHE_DIR . "/errors/" . $sanitizedFileName . ".log";
      if(file_exists($file) && $handle = fopen($file, "r")) {
        $msg = fread($handle, filesize($file));
        fclose($handle);
        return $msg;
      }
    }
    
    return false;
  }
  
}
