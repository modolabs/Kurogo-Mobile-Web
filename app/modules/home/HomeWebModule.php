<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */

Kurogo::includePackage('Emergency');

class HomeWebModule extends WebModule {
  protected $id = 'home';
  protected $canBeAddedToHomeScreen = false;
  protected $hideFooterLinks = true;

  protected function showLogin() {
    return $this->getOptionalModuleVar('SHOW_LOGIN', true);
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
              
      case 'index':
        if ($this->pagetype == 'tablet') {
          $this->assign('modulePanes', $this->getTabletModulePanes($this->getModuleSection('tablet_panes')));
          $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          $this->addOnOrientationChange('moduleHandleWindowResize();');
          
        } else {
          $this->assign('modules', $this->getModuleNavList());
          $this->assign('hideImages', $this->getOptionalModuleVar('HIDE_IMAGES', false));
          
          if ($this->getOptionalModuleVar('SHOW_BANNER_ALERT', false)) {
            $config = $this->loadFeedData();
            
            if (isset($config['notice'])) {
              $bannerController = DataController::factory($config['notice']['CONTROLLER_CLASS'], $config['notice']);
              if ($bannerController) {
                $bannerNotice = $bannerController->getLatestEmergencyNotice();
                if ($bannerNotice) {
                  $this->assign('bannerNotice', $bannerNotice);
                  
                  $bannerModule = $this->getOptionalModuleVar('BANNER_ALERT_MODULE_LINK', false);
                  if ($bannerModule) {
                    $this->assign('bannerURL', $this->buildURLForModule($bannerModule, 'index'));
                  }
                }
              }
            }
          }
        }
        
        if ($this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
            $this->assign('showFederatedSearch', true);
            $this->assign('placeholder', $this->getLocalizedString("SEARCH_PLACEHOLDER", Kurogo::getSiteString('SITE_NAME')));
        }
        $this->assign('SHOW_DOWNLOAD_TEXT', DownloadWebModule::appDownloadText($this->platform));
        $this->assign('displayType', $this->getModuleVar('display_type'));
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getAllModuleNavigationData(self::EXCLUDE_DISABLED_MODULES) as $type=>$modules) {
        
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

        $this->assign('federatedResults', $federatedResults);
        $this->assign('searchTerms',      $searchTerms);
        $this->setLogData($searchTerms);
        break;
    }
  }
}
