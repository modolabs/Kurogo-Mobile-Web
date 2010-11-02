<?php

require_once realpath(LIB_DIR.'/Module.php');

class LoginModule extends Module {
  protected $id = 'login';
  
  protected function initialize() {

  }

  protected function initializeForPage() {
  
    $url = $this->getArg('url', ''); //return url
    
    $this->assign('url', $url);
  }

}
