<?php

require_once realpath(LIB_DIR.'/Module.php');

class LinksModule extends Module {
  protected $id = 'links';
  
  protected function initializeForPage() {
    $this->loadThemeConfigFile('links-index', 'links');
  }
}
