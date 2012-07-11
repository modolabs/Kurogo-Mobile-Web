<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
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
    
    protected function showLogin() {
        return $this->getOptionalModuleVar('SHOW_LOGIN', true);
    }
    
    private function getTabletModulePanes($tabletConfig) {
        $modulePanes = array();
        
        foreach ($tabletConfig as $blockName => $moduleID) {
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

                $pageData = $module->getPageData();
                if (isset($pageData['pane'], 
                          $pageData['pane']['pageTitle']) && strlen($pageData['pane']['pageTitle'])) {
                    
                    $title = $pageData['pane']['pageTitle'];
                } elseif (isset($pageData[$this->page], 
                          $pageData[$this->page]['pageTitle']) && strlen($pageData[$this->page]['pageTitle'])) {
                    
                    $title = $pageData[$this->page]['pageTitle'];
                }
                
            } catch (Exception $e) {
                Kurogo::log(LOG_WARNING, $e->getMessage(), "home", $e->getTrace());
            }
            
            $modulePanes[$blockName] = array(
                'id'        => $moduleID,
                'url'       => self::buildURLForModule($moduleID, 'index'),
                'title'     => $title,
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
                if ($this->pagetype == 'tablet') {
                    $modulePanes = $this->getTabletModulePanes($this->getModuleSection('tablet_panes'));
                    
                    $this->assign('modulePanes', $modulePanes);
                    $this->addOnLoad('loadModulePages('.json_encode($modulePanes).');');
                    $this->addOnOrientationChange('moduleHandleWindowResize();');
              
                } else {
                    $this->assign('modules', $this->getAllModuleNavigationData(self::EXCLUDE_DISABLED_MODULES));
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
                
                if ($this->getOptionalModuleVar('SHOW_FEDERATED_SEARCH', true)) {
                    $this->assign('showFederatedSearch', true);
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
        }
    }
}
