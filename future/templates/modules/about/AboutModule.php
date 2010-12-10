<?php

require_once realpath(LIB_DIR.'/Module.php');

class AboutModule extends Module {
  protected $id = 'about';

  protected function getModuleItemForKey($key, $value)
  {
    $item = array(
        'label'=>ucfirst($key),
        'name'=>"moduleData[$key]",
        'typename'=>"moduleData][$key",
        'value'=>$value,
        'type'=>'text'
    );

    switch ($key)
    {
        case 'ABOUT_HTML':
        case 'SITE_ABOUT_HTML':
            $item['type'] = 'paragraph';
            break;
        default:
            return parent::getModuleItemForKey($key, $value);
            break;
    }
    
    return $item;
  }

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
        $this->assign('SITE_ABOUT_HTML', $this->getModuleVar('SITE_ABOUT_HTML'));
        $this->assign('devicePhrase', $this->getPhraseForDevice()); // TODO: this should be more generic, not part of this module
        break;
      
      case 'about':
        $this->assign('ABOUT_HTML', $this->getModuleVar('ABOUT_HTML'));
        break;
      
      case 'new':
        $this->assign('items', array()); // Disabled for now
        break;
    }
  }
}
