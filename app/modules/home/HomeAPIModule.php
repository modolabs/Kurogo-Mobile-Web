<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class HomeAPIModule extends APIModule
{
    protected $id = 'home';
    protected $vmin = 1;
    protected $vmax = 2;

    public function initializeForCommand() {
        switch ($this->command)
        {
            case 'notice':
                $response = null;
                $responseVersion = 1;
                if ($this->getOptionalModuleVar('BANNER_ALERT', false, 'notice')) {
                    $noticeData = $this->getOptionalModuleSection('notice');
                    if ($noticeData) {
                        $response = array(
                            'notice'=>'',
                            'moduleID'=>null,
                            'link'=>$this->getOptionalModuleVar('BANNER_ALERT_MODULE_LINK', false, 'notice')
                        );
                        // notice can either take a module or data model class or retriever class. The section is passed on. It must implement the HomeAlertInterface interface
        
                        if (isset($noticeData['BANNER_ALERT_MODULE'])) {
                            $moduleID = $noticeData['BANNER_ALERT_MODULE'];
                            $controller = WebModule::factory($moduleID);
                            $response['moduleID'] = $moduleID;
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
        
                        $response['notice'] = $controller->getHomeScreenAlert();
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;
            case 'modules':
            
                if ($setcontext = $this->getArg('setcontext')) {
                    Kurogo::sharedInstance()->setUserContext($setcontext);
                }
            
                $responseVersion = 2;
                $response = array(
                    'primary'=>array(),
                    'secondary'=>array(),
                    'customize'=>$this->getOptionalModuleVar('ALLOW_CUSTOMIZE', true),
                    'displayType'=>$this->getOptionalModuleVar('display_type','springboard'),
                );
                
                $allmodules = $this->getAllModules();
                $navModules = Kurogo::getSiteSections('navigation', Config::APPLY_CONTEXTS_NAVIGATION);
                
                foreach ($navModules as $moduleID=>$moduleData) {
                    if ($module = Kurogo::arrayVal($allmodules, $moduleID)) {
                    
                        if (isset($moduleData['minAPIClientVersion'])) {
                            if (version_compare($this->clientVersion, $moduleData['minAPIClientVersion'], 'lt')) {
                                continue;
                            }
                        }

                        if (isset($moduleData['maxAPIClientVersion'])) {
                            if (version_compare($this->clientVersion, $moduleData['maxAPIClientVersion'], 'gt')) {
                                continue;
                            }
                        }

                        if (isset($moduleData['pagetype'])) {
                            if (!is_array($moduleData['pagetype'])) {
                                $moduleData['pagetype'] = array($moduleData['pagetype']);
                            }
                            if (!in_array($this->clientPagetype, $moduleData['pagetype'])) {
                                continue;
                            }
                        }

                        if (isset($moduleData['browser'])) {
                            if (!is_array($moduleData['browser'])) {
                                $moduleData['browser'] = array($moduleData['browser']);
                            }
                            if (!in_array($this->clientBrowser, $moduleData['browser'])) {
                                continue;
                            }
                        }
                
                        if (isset($moduleData['platform'])) {
                            if (!is_array($moduleData['platform'])) {
                                $moduleData['platform'] = array($moduleData['platform']);
                            }
                            if (!in_array($this->clientPlatform, $moduleData['platform'])) {
                                continue;
                            }
                        }
                    
                        $title = Kurogo::arrayVal($moduleData,'title', $module->getModuleVar('title'));
                        $type = Kurogo::arrayVal($moduleData, 'type', 'primary');
                        $visible = Kurogo::arrayVal($moduleData, 'visible', 1);
                        $response[$type][] = array(
                            'tag'=>$moduleID,
                            'title'=>$title,
                            'visible'=>(bool)$visible,
                        );
                    }
                }
                
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;
            default:
                $this->invalidCommand();
        }
    }
}


