<?php

require_once realpath(LIB_DIR.'/Module.php');

class LinksModule extends Module {
  protected $id = 'links';
  
  protected function initializeForPage() {
    $links = $this->loadThemeConfigFile('links-index', 'links');
    $springboard = isset($links['springboard']) && $links['springboard'];
    unset($links['springboard']);
    
    foreach ($links as &$link) {
      if (isset($link['icon'])) {
        $link['img'] = "/modules/{$this->id}/images/{$link['icon']}";
      }
    }
    
    $this->assign('springboard', $springboard);
    $this->assign('links',       $links);
  }
}
