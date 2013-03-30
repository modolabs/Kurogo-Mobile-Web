<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    protected $hideFooterLinks = true;

    private function getModuleCustomizeList() {    
        $navModules = $this->getAllModuleNavigationData(self::INCLUDE_HIDDEN_MODULES, 'customize');
        $modules = Kurogo::arrayVal($navModules, 'primary', array());
        return $modules;
    }

  private function handleCustomizeRequest($args) {
    if (isset($args['action'])) {
      $currentModules = $this->getModuleCustomizeList();
      $modules = array();
      foreach ($currentModules as $moduleID=>$moduleData) {
        $modules[$moduleID] = intval($moduleData['visible']);
      }
      
      switch ($args['action']) {
        case 'swap':
          if (isset($args['module1'], $args['module2']) && 
              in_array($args['module1'], $modules) && 
              in_array($args['module2'], $modules)) {

            $temp = $modules;
            $modules =array();
            foreach ($temp as $moduleID=>$visible) {
                if ($moduleID==$args['module1']) {
                    $moduleID = $args['module2'];
                    $visible = $temp[$args['module2']];
                } elseif ($moduleID == $args['module2']) {
                    $moduleID = $args['module1'];
                    $visible = $temp[$args['module1']];
                }
                $modules[$moduleID] = $visible;
            }

            $this->setUserNavData($modules);
          }
          break;
          
        case 'on':
        case 'off':
          if (isset($args['module'])) {
            $modules[$args['module']] = $args['action'] == 'on' ? 1 : 0;            
            $this->setUserNavData($modules);
          }
          break;
        
        default:
          Kurogo::log(LOG_WARNING,__FUNCTION__."(): Unknown action '{$_REQUEST['action']}'",'module');
          break;
      }
    }
  }    
    protected function showLogin() {
        return $this->getOptionalModuleVar('SHOW_LOGIN', true);
    }
    
    protected function getTabletModulePanes() {
        $portletsConfig = $this->getModuleSections('portlets');
        
        $modulePanes = array();
        
        foreach ($portletsConfig as $moduleID => $portletConfig) {
            $title = ucfirst($moduleID);
            
            try {
                $module = self::factory($moduleID, 'pane', array());
                
                // Allow module to add javascript and css urls
                $module->fetchPage();
                
                // don't copy inline css and javascript which will be added on  
                // pane content ajax load. See common/templates/pane.tpl
                $cssAndJavascript = $module->exportCSSAndJavascript();
                $cssAndJavascript['properties']['inlineCSSBlocks'] = array();
                $cssAndJavascript['properties']['inlineJavascriptBlocks'] = array();
                $cssAndJavascript['properties']['inlineJavascriptFooterBlocks'] = array();
                $cssAndJavascript['properties']['onOrientationChangeBlocks'] = array();
                $cssAndJavascript['properties']['onLoadBlocks'] = array();
                $this->importCSSAndJavascript($cssAndJavascript);
                
                if (isset($portletConfig['TITLE'])) {
                    $title = $portletConfig['TITLE'];
                } else {
                    // Old way of specifying the pane title
                    $pageData = $module->getPageData();
                    
                    if (isset($pageData['pane'], 
                              $pageData['pane']['pageTitle']) && strlen($pageData['pane']['pageTitle'])) {
                        
                        $title = $pageData['pane']['pageTitle'];
                    } elseif (isset($pageData[$this->page], 
                              $pageData[$this->page]['pageTitle']) && strlen($pageData[$this->page]['pageTitle'])) {
                        
                        $title = $pageData[$this->page]['pageTitle'];
                    }
                }
            } catch (Exception $e) {
                Kurogo::log(LOG_WARNING, $e->getMessage(), "home", $e->getTrace());
            }
            
            $modulePanes[] = array(
                'moduleId'  => $moduleID,
                'title'     => $title,
                'classes'   => self::argVal($portletConfig, 'CLASSES', ''),
                'url'       => self::buildURLForModule($moduleID, 'index'),
                'elementId' => "content_{$moduleID}",
                'ajaxURL'   => FULL_URL_PREFIX.ltrim($this->buildURL('pane', array('id' => $moduleID)), '/'),
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
                $this->setPageTitle($this->getOptionalModuleVar('pageTitle', Kurogo::getSiteString('SITE_NAME'), 'index', 'pages'));
                if ($this->pagetype == 'tablet') {
                    $modulePanes = $this->getTabletModulePanes();
                    
                    $this->assign('modulePanes', $modulePanes);
                    $this->addInlineJavascript('var homePortlets = {};');
                    $this->addOnLoad('loadModulePages('.json_encode($modulePanes).');');
                    $this->addOnOrientationChange('moduleHandleWindowResize();');
              
                } else {
                    $this->assign('modules', $this->getAllModuleNavigationData(self::EXCLUDE_HIDDEN_MODULES));
                    $this->assign('hideImages', $this->getOptionalModuleVar('HIDE_IMAGES', false));
                }
                    
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
                
                if ($this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
                    $this->assign('showFederatedSearch', true);
                    $this->assign('placeholder', $this->getLocalizedString("SEARCH_PLACEHOLDER", Kurogo::getSiteString('SITE_NAME')));
                }
                
                if ($this->getPlatform()=='iphone' && $this->getOptionalModuleVar('ADD_TO_HOME', false)) {
                    $this->addInternalJavascript('/common/javascript/lib/add2homeConfig.js');
                    $this->addInternalJavascript('/common/javascript/lib/add2home.js');
                    $this->addInternalCSS('/common/css/add2home.css');
                }
                
                
                $this->assignUserContexts($this->getOptionalModuleVar('ALLOW_CUSTOMIZE', true));                
                $this->assign('SHOW_DOWNLOAD_TEXT', Kurogo::getOptionalSiteVar('downloadText', '', $this->platform, 'apps')); 
                
                $homeModuleID = $this->getHomeModuleID();
                if ($iconSet = $this->getOptionalThemeVar('navigation_icon_set')) {
                    $iconSetSize = $this->getOptionalThemeVar('navigation_icon_size');
                    $downloadImgPrefix = "/common/images/iconsets/{$iconSet}/{$iconSetSize}/download";
                } else {
                    $downloadImgPrefix = "/modules/{$homeModuleID}/images/download";
                }
                $this->assign('downloadImgPrefix', $downloadImgPrefix);
                $this->assign('displayType', $this->getModuleVar('display_type'));
                break;
            
            case 'search':
                $searchTerms = $this->getArg('filter');
                $useAjax = ($this->pagetype != 'basic');
                
                $searchModules = array();
                
                if ($this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
                    $this->assign('showFederatedSearch', true);
                    foreach ($this->getAllModuleNavigationData(self::EXCLUDE_HIDDEN_MODULES) as $type=>$modules) {
                    
                        foreach ($modules as $id => $info) {
                            if ($id == 'customize') {
                                continue;
                            }
                            
                            $module = self::factory($id);
                            
                            if ($module->getOptionalModuleVar('search', false, 'module')) {
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
                        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                        $this->addInlineJavascript('var federatedSearchModules = '.json_encode($searchModules).";\n");
                        $this->addOnLoad('runFederatedSearch(federatedSearchModules);');
                    }
                }
                
                $this->assign('federatedSearchModules', $searchModules);
                $this->assign('searchTerms',            $searchTerms);
                $this->setLogData($searchTerms);
                break;
            case 'modules':
                $configModule = $this->getArg('configModule', $this->configModule);
                $this->assign('modules', $this->getAllModuleNavigationData(self::EXCLUDE_HIDDEN_MODULES));
                $this->assign('hideImages', $this->getOptionalModuleVar('HIDE_IMAGES', false));
                $this->assign('displayType', $this->getModuleVar('display_type'));

                if ($configModule == $this->configModule && $this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
                    $this->assign('showFederatedSearch', true);
                    $this->assign('placeholder', $this->getLocalizedString("SEARCH_PLACEHOLDER", Kurogo::getSiteString('SITE_NAME')));
                }
                $this->assignUserContexts($this->getOptionalModuleVar('ALLOW_CUSTOMIZE', true));
                
                break;

          
            case 'searchResult':
                $moduleID = $this->getArg('id');
                $searchTerms = $this->getArg('filter');
                $this->setLogData($searchTerms);
                
                $module = self::factory($moduleID);
                $this->assign('federatedSearchResults', $this->runFederatedSearchForModule($module, $searchTerms));
                break;
                
            case 'pane':
                // This wrapper exists so we can catch module errors and prevent redirection to the error page
                $moduleID = $this->getArg('id');
                
                try {
                    $module = self::factory($moduleID, 'pane', array(self::AJAX_PARAMETER => 1));
                    $content = $module->fetchPage();
                    
                } catch (Exception $e) {
                    Kurogo::log(LOG_WARNING, $e->getMessage(), "home", $e->getTrace());
                    $content = '<p class="nonfocal">'.$this->getLocalizedString('ERROR_MODULE_PANE').'</p>';
                }
                
                $this->assign('content', $content);
                break;
            case 'customize':
                $allowCustomize = $this->getOptionalModuleVar('ALLOW_CUSTOMIZE', true);
                $this->assign('allowCustomize', $allowCustomize);
                if (!$allowCustomize) {
                    break;
                }
                $this->handleCustomizeRequest($this->args);

                $modules = $this->getModuleCustomizeList();
                $moduleIDs = array_keys($modules);
    
                switch($this->pagetype) {
                  case 'compliant':
                  case 'tablet':
                     $this->addInlineJavascript(
                      'var MODULE_NAV_COOKIE = "'.self::MODULE_NAV_COOKIE.'";'.
                      'var MODULE_NAV_COOKIE_LIFESPAN = '.Kurogo::getSiteVar('MODULE_NAV_COOKIE_LIFESPAN').';'.
                      'var COOKIE_PATH = "'.COOKIE_PATH.'";'
                    );
                    $this->addInlineJavascriptFooter('init();');
                    break;
      
                  case 'basic':
                    foreach ($moduleIDs as $index => $id) {
                      $modules[$id]['toggleVisibleURL'] = $this->buildBreadcrumbURL('index', array(
                        'action' => $modules[$id]['visible'] ? 'off' : 'on',
                        'module' => $id,
                      ), false);
          
                      if ($index > 0) {
                        $modules[$id]['swapUpURL'] = $this->buildBreadcrumbURL('index', array(
                          'action'    => 'swap',
                          'module1'   => $id,
                          'module2'   => $moduleIDs[$index-1],
                        ), false);
                      }
                      if ($index < (count($moduleIDs)-1)) {
                        $modules[$id]['swapDownURL'] = $this->buildBreadcrumbURL('index', array(
                          'action'    => 'swap',
                          'module1'   => $id,
                          'module2'   => $moduleIDs[$index+1],
                        ), false);
                      }
                    }
                    break;
        
                  default:
                    break;
                }    

                // show user selectable context switching 
                if ($contexts = Kurogo::sharedInstance()->getContexts()) {
                
                    $userContextList = $this->getUserContextListData('customizemodules', false);
                
                    $this->assign('customizeUserContextListDescription', $this->getLocalizedString('USER_CONTEXT_LIST_DESCRIPTION'));

                    if ($this->platform == 'iphone') {
                        $this->assign('customizeUserContextListDescriptionFooter', $this->getLocalizedString('USER_CONTEXT_LIST_DESCRIPTION_FOOTER_DRAG'));
                    } else {
                        $this->assign('customizeUserContextListDescriptionFooter', $this->getLocalizedString('USER_CONTEXT_LIST_DESCRIPTION_FOOTER'));
                    }

                    $this->assign('customizeUserContextList', $userContextList);
                } else {
                    $key = 'CUSTOMIZE_INSTRUCTIONS';
                    if ($this->pagetype == 'compliant' || $this->pagetype=='tablet') {
                        $key = 'CUSTOMIZE_INSTRUCTIONS_' . strtoupper($this->pagetype);
                        if ($this->platform == 'iphone') {
                            $key .= '_DRAG';
                        }
                    }
                    $this->assign('customizeInstructions', $this->getLocalizedString($key));
                }            
                $this->assign('modules', $modules);
                break;

            case 'customizemodules':
                $modules = $this->getModuleCustomizeList();
                $this->assign('modules', $modules);
                break;
        }
    }
}
