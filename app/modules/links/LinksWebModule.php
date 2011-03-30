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

  protected function getModuleDefaultData() {
    return array_merge(parent::getModuleDefaultData(), array(
      'display_type' => 'springboard',
      'strings' => array(
          'description' => ''
      ),
      'links' => array()
      )
    );
  }

  protected function getSectionTitleForKey($key) {
    switch ($key) {
      case 'links': return 'Links';
      default: return parent::getSectionTitleForKey($key);
    }
  }
  
  protected function initializeForPage() {
    $links = $this->getModuleSections('links');
    
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
