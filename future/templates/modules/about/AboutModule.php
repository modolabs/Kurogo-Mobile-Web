<?php

require_once realpath(LIB_DIR.'/Module.php');

class AboutModule extends Module {
  protected $id = 'about';
  
  private function getPhraseForDevice() {
    switch($this->platform) {
      case 'iphone':
        return 'iPhone';
        
      case 'android':
        return 'Android phones';
        
      default:
        switch ($this->pagetype) {
          case 'compliant':
            return 'touchscreen phones';
          
          case 'basic':
          default:
            return 'non-touchscreen phones';
        }
    }
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index':
        $this->loadWebAppConfigFile('about-index', 'aboutPages');
        break;
        
      case 'about_site':
        $this->assign('devicePhrase', $this->getPhraseForDevice());
        break;
      
      case 'about':
        break;
      
      case 'new':
        $this->assign('items', array()); // Disabled for now
        break;
    }
  }
}
