<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class HomeWebModule extends WebModule {
  protected $id = 'home';
  protected $canBeAddedToHomeScreen = false;

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
              
      case 'index':
        if ($this->pagetype == 'tablet') {
          
          $this->assign('modulePanes', $this->getTabletModulePanes($this->getModuleSection('tablet_panes')));
          $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          $this->addOnOrientationChange('moduleHandleWindowResize();');
        } else {
          $this->assign('modules', $this->getModuleNavList());
        }
        
        $this->assign('SHOW_DOWNLOAD_TEXT', DownloadWebModule::appDownloadText($this->platform));
        $this->assign('displayType', $this->getModuleVar('display_type'));
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getNavigationModules(false) as $type=>$modules) {
        
            foreach ($modules as $id => $info) {
            
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
        }
        //error_log(print_r($federatedResults, true));
        $this->assign('federatedResults', $federatedResults);
        $this->assign('searchTerms',      $searchTerms);
        break;
    }
  }
}
