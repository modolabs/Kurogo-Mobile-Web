<?php
/**
  * @package Module
  * @subpackage Fullweb
  */
class UrlWebModule extends WebModule {
  protected $id = 'url';
  
  protected function initializeForPage() {
     if ($url = $this->getModuleVar('url')) {
         $this->logView();
         Kurogo::redirectToURL($url);
     } else {
        throw new KurogoConfigurationException("URL not specified");
     }
  }
}
