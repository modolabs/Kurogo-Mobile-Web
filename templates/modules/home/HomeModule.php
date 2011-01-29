<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class HomeModule extends Module {
  protected $id = 'home';

  protected function getModuleDefaultData()
  {
    return array_merge(parent::getModuleDefaultData(), array(
        'display_type'=>'springboard',
        'primary_modules'=>array(),
        'secondary_modules'=>array()
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
        default:
            return parent::prepareAdminForSection($section, $adminModule);
    }
  }

  public function getHomeScreenModules($type=null) {
      
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

    $primary_modules = $this->getModuleSection('primary_modules');
    foreach ($primary_modules as $moduleID=>$title) {
        $modules[$moduleID] = array(
            'title'=>$title,
            'primary'=>1,
            'disableable'=>1,
            'visible'=>$allVisible || isset($visibleModuleIDs[$moduleID])
        );
    }

    $secondary_modules = $this->getModuleSection('secondary_modules');
    foreach ($secondary_modules as $moduleID=>$title) {
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
            return $$type;
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
        $primaryModules = array();
        $secondaryModules = array();
        
        foreach ($this->getHomeScreenModules() as $id => $info) {
            if ($info['visible']) {
                $module = array(
                  'title' => $info['title'],    
                  'url'   => isset($info['url']) ? $info['url'] : URL_BASE . $id . '/',
                  'img'   => isset($info['img']) ? $info['img'] : sprintf('%smodules/%s/images/%s%s', URL_BASE, $this->id, $id, $this->imageExt)
                );
                if ($id == 'about' && $whatsNewCount > 0) {
                  $module['badge'] = $whatsNewCount;
                }
                if ($info['primary']) {
                  $primaryModules[] = $module;
                } else {
                  $module['class'] = 'utility';
                  $secondaryModules[] = $module;
                }
            }
        }
        
        if (count($primaryModules) && count($secondaryModules)) {
          $primaryModules[] = array('separator' => true);
        }
        $modules = array_merge($primaryModules, $secondaryModules);
        
        $this->assign('display_type', $this->getModuleVar('display_type'));
        $this->assign('modules', $modules);
        $this->assign('primaryModules', $primaryModules);
        $this->assign('secondaryModules', $secondaryModules);
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
              'url'     => $module->urlForFederatedSearch($searchTerms),
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
