<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
     
  protected function initializeForPage() {
    $homeModules = array(
      'primary' => array(),
      'secondary' => array(),
    );
    
    foreach ($this->getHomeScreenModules() as $id => $info) {
      if (!$info['disabled']) {
        if (!isset($info['url'])) {
          $info['url'] = "/$id/";
        }
        if (!isset($info['img'])) {
          $info['img'] = "/modules/{$this->id}/images/$id.png";
        }
        if ($info['primary']) {
          $homeModules['primary'][$id] = $info;
        } else {
          $homeModules['secondary'][$id] = $info;
        }
      }
    }

    $this->assign('homeModules', $homeModules);
    $this->assign('whatsNewCount', 0);
    $this->assign('topItem', null);
  }
}
