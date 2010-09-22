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
    $siteVars = $GLOBALS['siteConfig']->getThemeVar('site');
    $navlistItems = array(
      'about_site' => array(
        'title' => 'About this website',
        'url' => 'about_site.php',
      ),
      'about' => array(
        'title' => 'About '.$siteVars['INSTITUTION_NAME'],
        'url' => 'about.php',
      ),
      'feedback' => array(
        'title' => 'Send us feedback!',
        'url' => 'mailto:'.$siteVars['FEEDBACK_EMAIL'],
        'class' => 'email',
      ),
    );

    switch ($page) {
      case 'index':
        if ($GLOBALS['deviceClassifier']->getPagetype() == 'basic') {
          $this->assign('lastNavItem', array_pop($navlistItems));
        }
        $this->assign('navlistItems', $navlistItems);
        break;
        
      case 'about_site':
        $this->setPageTitle($navlistItems[$page]['html']);
        $this->assign('devicePhrase', $this->getPhraseForDevice());
        break;
      
      case 'about':
        $this->setPageTitle($navlistItems[$page]['html']);
        break;
      
      case 'new':
        $whatsNew = new WhatsNew();
        
        $this->assign('items', $whatsNew->get_items());
        break;
    }
  }
}
