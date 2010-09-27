<?php

require_once realpath(LIB_DIR.'/Module.php');

class InfoModule extends Module {
  protected $id = 'info';
     
  protected function initializeForPage() {
    // Just a static page
  }
}
