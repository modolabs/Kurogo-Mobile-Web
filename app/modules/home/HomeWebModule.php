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
      try {
          $module = self::factory($moduleID, 'pane', $this->args);
      
          $paneContent = $module->fetchPage(); // sets pageTitle var
      
          $this->importCSSAndJavascript($module->exportCSSAndJavascript());

          $title = $module->getTemplateVars('pageTitle');
      } catch (Exception $except) {
          Kurogo::log(LOG_WARNING, $except->getMessage(), "home", $except->getTrace());
          $paneContent =  '<p class="nonfocal">' . $this->getLocalizedString('ERROR_MODULE_PANE') . "</p>";
          $title = $module->getConfigModule();
      }
      
      $modulePanes[$blockName] = array(
        'id'      => $moduleID,
        'url'     => self::buildURLForModule($moduleID, 'index'),
        'title'   => $title,
        'content' => $paneContent,
      );  
    }
   
    return $modulePanes;
  }

  protected function runFederatedSearchForModule($module, $searchTerms) {
      $results = array();
      try {
          $total = $module->federatedSearch($searchTerms, 2, $results);
      } catch (Exception $e) {
          $total = 0;
          Kurogo::log(LOG_WARNING, 'Federated search for module '.$module->getID().' failed: '.
              $e->getMessage(), 'module');
      }
      return array(
          'items' => $results,
          'total' => $total,
          'url'   => $module->urlForFederatedSearch($searchTerms),
      );
  }
  
  public function getAvailableAlertModules() {
    $allModules = $this->getAllModules();
    $modules = array();
    foreach ($allModules as $module) {
        if ($module instanceOf HomeAlertInterface) {
            $modules[$module->getConfigModule()] = sprintf("%s (%s)", $module->getModuleName(), $module->getConfigModule());
        }
    }
    return $modules;
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
          $this->assign('modules', $this->getModuleNavlist());
          $this->assign('hideImages', $this->getOptionalModuleVar('HIDE_IMAGES', false));
          
          if ($this->getOptionalModuleVar('BANNER_ALERT', false, 'notice')) {
            $noticeData = $this->getOptionalModuleSection('notice');
            if ($noticeData) {
                $bannerNotice = null;
                // notice can either take a module or data model class or retriever class. The section is passed on. It must implement the HomeAlertInterface interface

                if (isset($noticeData['BANNER_ALERT_MODULE'])) {
                    $moduleID = $noticeData['BANNER_ALERT_MODULE'];
                    $controller = WebModule::factory($moduleID);
                    $string = "Module $moduleID";
                } elseif (isset($noticeData['BANNER_ALERT_MODEL_CLASS'])) {
                    $controller = DataModel::factory($noticeData['BANNER_ALERT_MODEL_CLASS'], $noticeData);
                    $string = $noticeData['BANNER_ALERT_MODEL_CLASS'];
                } elseif (isset($noticeData['BANNER_ALERT_RETRIEVER_CLASS'])) {
                    $controller = DataRetriever::factory($noticeData['BANNER_ALERT_RETRIEVER_CLASS'], $noticeData);
                    $string = $noticeData['BANNER_ALERT_RETRIEVER_CLASS'];
                } else {
                    throw new KurogoConfigurationException("Banner alert not properly configured");
                }

                if (!$controller instanceOf HomeAlertInterface) {
                    throw new KurogoConfigurationException("$string does not implement HomeAlertModule interface");
                } 

                $bannerNotice = $controller->getHomeScreenAlert();
                
                if ($bannerNotice) {
                  $this->assign('bannerNotice', $bannerNotice);

                  // is this necessary?                  
                  $bannerModule = $this->getOptionalModuleVar('BANNER_ALERT_MODULE_LINK', false, 'notice');
                  if ($bannerModule) {
                    $this->assign('bannerURL', $this->buildURLForModule($moduleID, 'index'));
                  }
                }
            }
          }
        }
        
        if ($this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
            $this->assign('showFederatedSearch', true);
            $this->assign('placeholder', $this->getLocalizedString("SEARCH_PLACEHOLDER", Kurogo::getSiteString('SITE_NAME')));
        }
        
        if ($this->getPlatform()=='iphone' && $this->getOptionalModuleVar('ADD_TO_HOME', false)) {
            $this->addInternalJavascript('/common/javascript/lib/add2homeConfig.js');
            $this->addInternalJavascript('/common/javascript/lib/add2home.js');
            $this->addInternalCSS('/common/css/add2home.css');
        }
        
        $this->assign('SHOW_DOWNLOAD_TEXT', DownloadWebModule::appDownloadText($this->platform));
        $this->assign('displayType', $this->getModuleVar('display_type'));
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        $useAjax = ($this->pagetype != 'basic') && ($this->pagetype != 'touch');
        
        $searchModules = array();
        
        foreach ($this->getAllModuleNavigationData(self::EXCLUDE_DISABLED_MODULES) as $type=>$modules) {
        
            foreach ($modules as $id => $info) {
            
                $module = self::factory($id);
                if ($module->getModuleVar('search')) {
                    $searchModule = array(
                        'id'        => $id,
                        'elementId' => 'federatedSearchModule_'.$id,
                        'title'     => $info['title'],
                    );

                    if ($useAjax) {
                        $searchModule['ajaxURL'] = FULL_URL_PREFIX.ltrim($this->buildURL('searchResult', array(
                            'id'     => $id,
                            'filter' => $searchTerms,
                        )), '/');
                        
                    } else {
                        $searchModule['results'] = $this->runFederatedSearchForModule($module, $searchTerms);
                    }
                    $searchModules[] = $searchModule;
                }
            }
        }
        
        if ($useAjax) {
            $this->addInlineJavascript('var federatedSearchModules = '.json_encode($searchModules).";\n");
            $this->addOnLoad('runFederatedSearch(federatedSearchModules);');
        }

        $this->assign('federatedSearchModules', $searchModules);
        $this->assign('searchTerms',            $searchTerms);
        $this->setLogData($searchTerms);
        break;
      
      case 'searchResult':
        $moduleID = $this->getArg('id');
        $searchTerms = $this->getArg('filter');
        
        $module = self::factory($moduleID);
        $this->assign('federatedSearchResults', $this->runFederatedSearchForModule($module, $searchTerms));
        break;
    }
  }
}
