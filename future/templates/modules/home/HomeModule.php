<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';

  protected function getModuleDefaultData()
  {
    return array_merge(parent::getModuleDefaultData(), array(
        'springboard'=>1
        )
    );
  }
  
  protected function getSectionTitleForKey($key)
  {
    switch ($key)
    {
        case 'primary_modules': return 'Primary Modules';
        case 'secondary_modules': return 'Secondary Modules';
        default: return parent::getSectionTitleForKey($key);
    }
  }
  
  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section)
    {
    
        case 'primary_modules':
        case 'secondary_modules':
            $adminModule->setTemplatePage('module_order', $this->id);
            $adminModule->addExternalJavascript(URL_BASE . "modules/{$this->id}/javascript/admin.js");
            $adminModule->addExternalCSS(URL_BASE . "modules/{$this->id}/css/admin.css");

            $allModules = $this->getAllModules();
            $sectionModules = $this->getHomeScreenModules($section);

            foreach ($allModules as $moduleID=>$module) {
                $allModules[$moduleID] = $module->getModuleName();
            }
            
            $adminModule->assign('allModules', $allModules);
            $adminModule->assign('sectionModules', $sectionModules);
            break;
    }
  }

  protected function getHomeScreenModules($type=null) {
      
    $config = ConfigFile::factory('home','module');
    $moduleData = $config->getSectionVars(true);
    $allVisible = true;

    if (isset($_COOKIE["visiblemodules"])) {
      $allVisible = false;
      if ($_COOKIE["visiblemodules"] == "NONE") {
        $visibleModuleIDs = array();
      } else {
        $visibleModuleIDs = array_flip(explode(",", $_COOKIE["visiblemodules"]));
      }
    }
    
    $modules = array();
    
    foreach ($moduleData['primary_modules'] as $moduleID=>$title) {
        $modules[$moduleID] = array(
            'title'=>$title,
            'primary'=>1,
            'disableable'=>1,
            'visible'=>$allVisible || isset($visibleModuleIDs[$moduleID])
        );
    }

    foreach ($moduleData['secondary_modules'] as $moduleID=>$title) {
        $modules[$moduleID] = array(
            'title'=>$title,
            'primary'=>0,
            'disableable'=>0,
            'visible'=>1
        );
    }
    
    switch ($type) {
        case 'primary_modules':
        case 'secondary_modules':
            return $moduleData[$type];
    }

    if (isset($_COOKIE["moduleorder"])) {
      $sortedModuleIDs = explode(",", $_COOKIE["moduleorder"]);
      $unsortedModuleIDs = array_diff(array_keys($modules), $sortedModuleIDs);
            
      $sortedModules = array();
      foreach (array_merge($sortedModuleIDs, $unsortedModuleIDs) as $moduleID) {
        if (isset($modules[$moduleID])) {
          $sortedModules[$moduleID] = $modules[$moduleID];
        }
      }
      $modules = $sortedModules;
    }
  
  
    //error_log('$modules(): '.print_r(array_keys($modules), true));
    return $modules;
  }
  
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $whatsNewCount = 0;
        $modules = array();
        $secondaryModules = array();
        
        foreach ($this->getHomeScreenModules() as $id => $info) {
            if ($info['visible']) {
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
        
        $this->assign('springboard', $this->getModuleVar('springboard'));
        $this->assign('modules', $modules);
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getHomeScreenModules() as $id => $info) {
          $module = Module::factory($id);
          if ($module->getModuleVar('search')) {
            $results = array();
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
