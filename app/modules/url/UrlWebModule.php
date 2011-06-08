<?php
/**
  * @package Module
  * @subpackage Fullweb
  */
abstract class UrlWebModule extends WebModule {
  protected $id = 'url';
  
  protected function initializeForPage() {
     if ($url = $this->getModuleVar('url')) {
         header("Location: $url");
         die();
     } else {
        throw new Exception("URL not specified");
     }
  }
}