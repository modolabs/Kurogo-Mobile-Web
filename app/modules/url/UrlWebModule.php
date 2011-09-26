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
         header("Location: $url");
         die();
     } else {
        throw new KurogoConfigurationException("URL not specified");
     }
  }
}
