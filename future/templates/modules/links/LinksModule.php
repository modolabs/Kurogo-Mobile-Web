<?php
/**
  * @package Module
  * @subpackage Links
  */

/**
  */
require_once realpath(LIB_DIR.'/Module.php');

/**
  * @package Module
  * @subpackage Links
  */
class LinksModule extends Module {
  protected $id = 'links';

  protected function getModuleDefaultData()
  {
    return array_merge(parent::getModuleDefaultData(), array(
        'springboard'=>0,
        'strings'=>array(
            'description'=>''
        ),
        'links'=>array()
        )
    );
  }

  protected function getSectionTitleForKey($key)
  {
    switch ($key)
    {
        case 'links': return 'Links';
        default: return parent::getSectionTitleForKey($key);
    }
  }
  
  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section)
    {
    
        case 'links':
            $adminModule->setTemplatePage('admin_links', $this->id);
            $adminModule->addExternalJavascript(URL_BASE . "modules/{$this->id}/javascript/admin.js");
            $adminModule->addExternalCSS(URL_BASE . "modules/{$this->id}/css/admin.css");
            $links = $this->getModuleArray('links');
            $adminModule->assign('links', $links);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
            break;
    }
  }
  
  protected function initializeForPage() {
    $links = $this->getModuleArray('links');
    
    $springboard = isset($links['springboard']) && $links['springboard'];
    
    foreach ($links as &$link) {
      if (!is_array($link)) {
        unset($link);
      } else if (isset($link['icon']) && strlen($link['icon'])>0) {
        $link['img'] = "/modules/{$this->id}/images/{$link['icon']}";
      }
    }
    
    $this->assign('springboard', $this->getModuleVar('springboard'));
    $this->assign('description', $this->getModuleVar('description'));
    $this->assign('links',       $links);
  }
}
