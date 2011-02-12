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

  protected function getModuleDefaultData() {
    return array_merge(parent::getModuleDefaultData(), array(
      'display_type'      => 'springboard',
      'primary_modules'   => array(),
      'secondary_modules' => array()
    ));
  }
  
  protected function getSectionTitleForKey($key) {
    switch ($key) {
        case 'primary_modules': return 'Primary Modules';
        case 'secondary_modules': return 'Secondary Modules';
        default: return parent::getSectionTitleForKey($key);
    }
  }
  
  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section) {
        case 'primary_modules':
        case 'secondary_modules':
            $adminModule->setTemplatePage('module_order', $this->id);
            $adminModule->addInternalJavascript("/modules/{$this->id}/javascript/admin.js");
            $adminModule->addInternalCSS("/modules/{$this->id}/css/admin.css");

            $allModules = $this->getAllModules();
            $navigationModules = $this->getNavigationModules();

            foreach ($allModules as $moduleID=>$module) {
                $allModules[$moduleID] = $module->getModuleName();
            }
            
            $adminModule->assign('allModules', $allModules);
            $adminModule->assign('sectionModules', $navigationModules[$section]);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
    }
  }
  
  private function getTabletModulePanes($tabletConfig) {
    $modulePanes = array();
    
    foreach ($tabletConfig as $blockName => $moduleID) {
      $module = self::factory($moduleID, 'pane', $this->args);
      
      $paneContent = $module->fetchPage(); // sets pageTitle var
      
      $this->importCSSAndJavascript($module->exportCSSAndJavascript());
      
      $modulePanes[$blockName] = array(
        'id'      => $moduleID,
        'url'     => self::buildURLForModule($moduleID, 'index'),
        'title'   => $module->getTemplateVars('pageTitle'),
        'content' => $paneContent,
      );  
    }
   
    return $modulePanes;
  }
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'pane':
        break;
        
      case 'index':
        $this->addOnLoad('rotateScreen();');
        $this->addOnOrientationChange('rotateScreen();');

        if ($this->pagetype == 'tablet') {
          $config = $this->getModuleConfig();
          
          $this->assign('modulePanes', $this->getTabletModulePanes($config->getSection('tablet_panes')));
          $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          $this->addOnLoad('moduleHandleWindowResize();');
        } else {
          $this->assign('modules', $this->getModuleNavList());
        }
        $this->assign('displayType', $this->getModuleVar('display_type'));
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getNavigationModules(false) as $id => $info) {
          $module = self::factory($id);
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
