<?php
/**
  * @package Module
  * @subpackage Links
  */

/**
  * @package Module
  * @subpackage Links
  */
class LinksWebModule extends WebModule {
  protected $id = 'links';
  
  public function getLinks() {
    return $this->getModuleSections('links');
  }

  protected function initializeForPage() {
    $links = $this->getLinks();
    
    foreach ($links as $index => &$link) {
        if (self::argVal($link, 'icon', false)) {
            $link['img'] = "/modules/{$this->configModule}/images/{$link['icon']}{$this->imageExt}";
        }
    }
        
    $this->assign('displayType', $this->getModuleVar('display_type'));
    $this->assign('description', $this->getModuleVar('description','strings'));
    $this->assign('links',       $links);
  }
}
