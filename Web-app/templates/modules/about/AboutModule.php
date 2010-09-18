<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/WhatsNew.php');

class AboutModule extends Module {
  protected $id = 'about';
  
  private function getPhraseForDevice() {
    switch($GLOBALS['deviceClassifier']->getPlatform()) {
      case 'iphone':
        return 'iPhone';
        
      case 'android':
        return 'Android phones';
        
      default:
        switch ($GLOBALS['deviceClassifier']->getPagetype()) {
          case 'mobile':
            return 'touchscreen phones';
          
          case 'basic':
          default:
            return 'non-touchscreen phones';
        }
    }
  }
  
  protected function initializeForPage($page, $args) {
    switch ($page) {
      case 'index':
        $siteVars = $GLOBALS['siteConfig']->getThemeVar('site');
        
        $this->assignByRef('navlistItems', array(
          array(
            'html' => 'About this website',
            'url' => 'about_site.php',
          ),
          array(
            'html' => 'About '.$siteVars['INSTITUTION_NAME'],
            'url' => 'about.php',
          ),
          array(
            'html' => 'Send us feedback!',
            'url' => 'mailto:'.$siteVars['FEEDBACK_EMAIL'],
            'class' => 'email',
          ),
        ));
      case 'about_site':
        $this->assignByRef('devicePhrase', $this->getPhraseForDevice());
        break;
        
      case 'new':
        $whatsNew = new WhatsNew();
        
        $this->assignByRef('items', $whatsNew->get_items());
        break;
    }
  }
}
