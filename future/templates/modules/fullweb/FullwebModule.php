<?php

require_once realpath(LIB_DIR.'/Module.php');

class FullwebModule extends Module {
  protected $id = 'fullweb';
  
  protected function initializeForPage() {
     $url = $this->getModuleVar('url');
     header("Location: $url");
     die();
  }
}
