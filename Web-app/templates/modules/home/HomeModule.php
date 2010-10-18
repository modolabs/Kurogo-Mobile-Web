<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $this->loadThemeConfigFile('home-index', 'home');
      
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
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getHomeScreenModules() as $id => $info) {
          if ($info['search']) {
            $results = array();
            $module = Module::factory($id, $this->page, $this->args);
            $total = $module->federatedSearch($searchTerms, 2, $results);
            $federatedResults[] = array(
              'title'   => $info['title'],
              'results' => $results,
              'total'   => $total,
              'url'     => $module->urlForSearch($searchTerms),
            );
            unset($module);
          }
        }
        //error_log(print_r($federatedResults, true));
        $this->assign('federatedResults', $federatedResults);
        $this->assign('searchTerms',      $searchTerms);
        break;
    }
  }
}
