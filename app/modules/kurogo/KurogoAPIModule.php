<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoAPIModule extends APIModule
{
    protected $id = 'kurogo';
    protected $vmin = 1;
    protected $vmax = 3;

    // special factory method for core
    public static function factory($id='core', $command='', $args=array()) {
        $module = new KurogoAPIModule();
        $module->init($command, $args);
        return $module;
    }
 
    //always allow access
    protected function getAccessControlLists($type) {
        return array(AccessControlList::allAccess());
    }
    
    public function initializeForCommand() {  
    
        switch ($this->command) {
            case 'hello':
            
                $allmodules = $this->getAllModules();
                
                if ($this->requestedVersion >=3) {
                    $version = 3;
                } else {
                    $version = 2;
                    $homeModuleData = $this->getAllModuleNavigationData();
                    $homeModules = array(
                            'primary'  => array_keys($homeModuleData['primary']),
                            'secondary'=> array_keys($homeModuleData['secondary']),
                    );
                }

                $platform = $this->clientPlatform;
                if ($this->clientPagetype=='tablet') {
                    $platform .= '-tablet';
                }
                
                foreach ($allmodules as $moduleID=>$module) {
                    if ($module->isEnabled()) {
                        //home is deprecated in lieu of using the "modules" command of the home module
                        $home = false;
                        
                        if ($version < 3) {
                            if ( ($key = array_search($moduleID, $homeModules['primary'])) !== FALSE) {
                                if (Kurogo::arrayVal($homeModuleData['primary'][$moduleID], 'visible', true)) {
                                    $title = Kurogo::arrayVal($homeModuleData['primary'][$moduleID],'title', $module->getModuleVar('title'));
                                    $home = array('type'=>'primary', 'order'=>$key, 'title'=>$title);
                                }
                            } elseif (($key = array_search($moduleID, $homeModules['secondary'])) !== FALSE) {
                                if (Kurogo::arrayVal($homeModuleData['secondary'][$moduleID], 'visible', true)) {
                                    $title = Kurogo::arrayVal($homeModuleData['secondary'][$moduleID],'title', $module->getModuleVar('title'));
                                    $home = array('type'=>'secondary', 'order'=>$key, 'title'=>$title);
                                }
                            }
                        }
                        
                    
                        $moduleResponse = array(
                            'id'        =>$module->getID(),
                            'tag'       =>$module->getConfigModule(),
                            'icon'      =>$module->getOptionalModuleVar('icon', $module->getConfigModule(), 'module'),
                            'title'     =>$module->getModuleVar('title','module'),
                            'access'    =>$module->getAccess(AccessControlList::RULE_TYPE_ACCESS),
                            'payload'   =>$module->getPayload(),
                            'bridge'    =>$module->getWebBridgeConfig($platform),
                            'vmin'      =>$module->getVmin(),
                            'vmax'      =>$module->getVmax(),
                        );
                        
                        if ($version < 3) {
                            $moduleResponse['home'] = $home;
                        }
                        $modules[] = $moduleResponse;
                    }
                }
                
                $contexts = array();
                foreach (Kurogo::sharedInstance()->getContexts() as $context) {
                    if ($context->isManual()) {
                        $contexts[] = array(
                                'id'=>$context->getID(),
                                'title'=>$context->getTitle(),
                                'description'=>$context->getDescription(),
                            );
                    }
                }
                
                $response = array(
                    'timezone'=>Kurogo::getSiteVar('LOCAL_TIMEZONE'),
                    'site'=>Kurogo::getSiteString('SITE_NAME'),
                    'organization'=>Kurogo::getSiteString('ORGANIZATION_NAME'),
                    'version'=>KUROGO_VERSION,
                    'modules'=>$modules,
                    'default'=>Kurogo::defaultModule(),
                    'home'=>$this->getHomeModuleID(),
                    'contexts'=>$contexts,
                    'contextsDisplay'=> array(
                        'home'      => Kurogo::getSiteVar('USER_CONTEXT_LIST_STYLE_NATIVE', 'contexts'),
                        'customize' => Kurogo::getSiteVar('USER_CONTEXT_LIST_STYLE_CUSTOMIZE', 'contexts'),
                    ),
                );
                
				if ($appData = Kurogo::getAppData($this->clientPlatform)) {
					if ($version = Kurogo::arrayVal($appData, 'version')) {
						if (version_compare($this->clientVersion, $version)<0) {
							$response['appdata'] = array(
								'url'=>Kurogo::arrayVal($appData, 'url'),
								'version'=>Kurogo::arrayVal($appData, 'version')
							);
						}
					}
				}
                
                $this->setResponse($response);
                $this->setResponseVersion($version);
                break;

            case 'setcontext':
                $context = $this->getArg('context');
                $response = Kurogo::sharedInstance()->setUserContext($context);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;                
            case 'classify':
                $userAgent = $this->getArg('useragent');
                if (!$userAgent) {
                    throw new KurogoException("useragent parameter not specified");
                }
                
                $response = Kurogo::deviceClassifier()->classifyUserAgent($userAgent);
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
}
