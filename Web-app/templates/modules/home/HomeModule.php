<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
     
  protected function initializeForPage($page, $args) {
    $homeModules = array(
      'primary' => array(),
      'secondary' => array(),
    );
    
    foreach ($GLOBALS['siteConfig']->getThemeVar('modules') as $id => $info) {
      if ($info['visible']) {
        if ($info['primary']) {
          $homeModules['primary'][$id] = $info;
        } else {
          $homeModules['secondary'][$id] = $info;
        }
      }
    }
    
    $this->assignByRef('homeModules', $homeModules);
    $this->assignByRef('whatsNewCount', 0);
    $this->assignByRef('topItem', null);
  }
}
