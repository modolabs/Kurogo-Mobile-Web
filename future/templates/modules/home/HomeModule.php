<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $this->loadWebAppConfigFile('home-index', 'home');
      
        $whatsNewCount = 0;
        $modules = array();
        $secondaryModules = array();
        
        foreach ($this->getHomeScreenModules() as $id => $info) {
          if (!$info['disabled']) {
            $module = array(
              'title' => $info['title'],
              'url'   => isset($info['url']) ? $info['url'] : "/$id/",
              'img'   => isset($info['img']) ? $info['img'] : "/modules/{$this->id}/images/$id.png",
            );
            if ($id == 'about' && $whatsNewCount > 0) {
              $module['badge'] = $whatsNewCount;
            }
            if ($info['primary']) {
              $modules[] = $module;
            } else {
              $module['class'] = 'utility';
              $secondaryModules[] = $module;
            }
          }
        }
        
        if (count($modules) && count($secondaryModules)) {
          $modules[] = array('separator' => true);
        }
        $modules = array_merge($modules, $secondaryModules);
        
        $this->assign('modules', $modules);
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
