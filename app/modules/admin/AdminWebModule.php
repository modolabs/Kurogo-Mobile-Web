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
  * @subpackage Admin
  */

/**
  * @package Module
  * @subpackage Admin
  */
class AdminWebModule extends WebModule {
    protected $id = 'admin';
    protected $canBeRemoved = false;
    protected $canAllowRobots = false;

  protected function includeCommonCSS() {
    return $this->pagetype != 'tablet';
  }
  
    private function getNavSections() {
        $navSections = array(
            array(
                'id'=>'site',
                'title'=>$this->getLocalizedString('ADMIN_SITE_TITLE'),
                'description'=>'',
                'url'=>$this->buildURL('site',array()),
            ),
            array(
                'id'=>'modules',
                'title'=>$this->getLocalizedString('ADMIN_MODULES_TITLE'),
                'description'=>'',
                'url'=>$this->buildURL('modules',array()),
            ),
            array(
                'id'=>'credits',
                'title'=>$this->getLocalizedString('ADMIN_CREDITS_TITLE'),
                'description'=>'',
                'url'=>$this->buildURL('credits',array()),
            ),
        );
        
        return $navSections;
    }

    private function getSiteAdminConfig($type) {
        static $configData;
        if (!isset($configData[$type])) {
            $files = array(
                APP_DIR . "/common/config/admin-{$type}.json",
                SHARED_APP_DIR . "/common/config/admin-{$type}.json",
                SITE_APP_DIR . "/common/config/admin-{$type}.json"
            );
            $data = array();
            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($json = json_decode(file_get_contents($file), true)) {
                        $data = self::mergeConfigData($data, $json);
                    } else {
                        throw new KurogoDataException($this->getLocalizedString('ERROR_PARSING_FILE', $file));
                    }
                }
            }
            $configData[$type] = $data;
        }
        
        return $configData[$type];
    }
    
    private function getSubNavSections($section) {
        $subNavSections = array();
        switch ($section) {
            case 'site':
                $configData = $this->getSiteAdminConfig($section);
                foreach ($configData as $id=>$data) {
                    $subNavSections[$id] = array(
                        'id'=>$id,
                        'title'=>isset($data['titleKey']) ?$this->getLocalizedString($data['titleKey']) : $data['title'],
                        'url'=>$this->buildURL($section, array('section'=>$id))
                    );
                }
 
                break;
                
            case 'modules':
                $subNavSections['overview'] = array(
                    'id'=>'overview',
                    'title'=>$this->getLocalizedString('ADMIN_MODULES_OVERVIEW_TITLE'),
                    'url'=>$this->buildURL($section, array('section'=>'overview'))
                );
                $subNavSections['homescreen'] = array(
                    'id'=>'homescreen',
                    'title'=>$this->getLocalizedString("ADMIN_MODULES_HOMESCREEN_TITLE"),
                    'url'=>$this->buildURL($section, array('section'=>'homescreen'))
                );
                $modules = array();
                $homeModuleID = $this->getHomeModuleID();
                if ($iconSet = $this->getOptionalThemeVar('navigation_icon_set')) {
                    $iconSetSize = $this->getOptionalThemeVar('navigation_icon_size');
                    $imgPrefix = "/common/images/iconsets/{$iconSet}/{$iconSetSize}/";
                } else {
                    $imgPrefix = "/modules/{$homeModuleID}/images/";
                }

                foreach ($this->getAllModules() as $module) {
                    $icon = $module->getOptionalModuleVar('icon', $module->getConfigModule(), 'module');
                    $subNavSections[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'img'=> sprintf("%s%s%s", $imgPrefix, $icon, $this->imageExt),
                        'url'=>$this->buildURL('modules', array('module'=>$module->getConfigModule()))
                    );
                    $modules[$module->getConfigModule()] = array(
                        'icon'=>$icon,
                        'type'=>$module->getID(),
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'home'=>$module->isOnHomeScreen(),
                        'disabled'=>$module->getOptionalModuleVar('disabled', false, 'module'),
                        'protected'=>$module->getOptionalModuleVar('protected', false, 'module'),
                        'secure'=>$module->getOptionalModuleVar('secure', false, 'module'),
                        'search'=>$module->getOptionalModuleVar('search', false, 'module'),
                        'canDisable'=>$module->canBeDisabled(), 
                        'canRemove'=>$module->canBeRemoved(), 
                        'url'=>$this->buildURL('modules', array('module'=>$module->getConfigModule()))
                    );
                    
                }
                $this->assign('modules', $modules);
                break;
        }

        return $subNavSections;
    }

    private function getModules() {

        $modulesNavData = $this->getAllModuleNavigationData();
        $modules = array(
            'primary'=>array(),
            'secondary'=>array(),
            'unused'=>array()
        );
        
        foreach ($modulesNavData as $type=>$typeModules) {
            foreach ($typeModules as $moduleID=>$moduleNavData) {
                $modules[$type][$moduleID] = $moduleNavData['title'];
            }
        }
        
        $usedModules = array_merge($modules['primary'], $modules['secondary']);
        $allModules = $this->getAllModules();
        $unusedModules = array_diff(array_keys($allModules), array_keys($usedModules));

        if ($iconSet = $this->getOptionalThemeVar('navigation_icon_set')) {
            $iconSetSize = $this->getOptionalThemeVar('navigation_icon_size');
            $imgPrefix = "/common/images/iconsets/{$iconSet}/{$iconSetSize}/";
        } else {
            $imgPrefix = "/modules/{$homeModuleID}/images/";
        }        
        
        foreach ($unusedModules as $moduleID) {
            $module = $allModules[$moduleID];
            if ($module->canBeAddedToHomeScreen()) {
                $modules['unused'][$moduleID] = $module->getModuleName();
            }
        }
        
        foreach ($modules as $type=>&$m) {
            foreach ($m as $id=>$title) {
                if ($module = Kurogo::arrayVal($allModules, $id)) {
                    $icon = $module->getOptionalModuleVar('icon', $module->getConfigModule(), 'module');
                    $modules[$type][$id] = array(
                        'title'       => $title,
                        'img'=> sprintf("%s%s%s", $imgPrefix, $icon, $this->imageExt),
                    );
                } else {
                    unset($modules[$type][$id]);
                }
            }
        }
                
        return $modules;
    }

    protected function initialize() {
        $this->requiresAdmin();
    }
  
    protected function initializeForPage() {
        //make sure that only desktop/tablet devices can use the module
        $deviceClassifier = Kurogo::deviceClassifier();
        if ($this->page != 'index' && !($deviceClassifier->isComputer() || $deviceClassifier->isTablet())) {
            $this->redirectTo('index');
        }

        $navSections = $this->getNavSections();
        $section = '';
        $this->assign('navSections', $navSections);
        $this->addJQuery();
        $this->addJQueryUI();

        switch ($this->page)
        {
            case 'modules':
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                $moduleID = $this->getArg('module');
                
                if ($moduleID) {
                    $this->setTemplatePage('module');
                    try {
                        if ($module = WebModule::factory($moduleID)) {
                            $this->assign('moduleName', $module->getModuleName());
                            $this->assign('moduleID', $module->getConfigModule());
                            $this->assign('moduleIcon', $module->getOptionalModuleVar('icon', $module->getConfigModule(), 'module'));
                            $section = $moduleID;
                            $moduleSection = $this->getArg('section','general');
                            $this->assign('moduleSection',$moduleSection);
                        }
                    } catch (KurogoException $e) {
                        $this->redirectTo($this->page, array());
                    }
                
                } elseif ($section == $defaultSubNavSection) {
                    $moduleClasses = WebModule::getAllModuleClasses();
                    $this->assign('moduleClasses', $moduleClasses);
                    $this->setTemplatePage($section);
                } elseif ($section == 'homescreen') {
                    $this->setTemplatePage($section);
                    
                    $modules = $this->getModules();
                    $this->assign('modules', $modules);                    
                    
                } else {
                    $this->redirectTo($this->page, array());
                }
                                
                break;
            case 'site':            
        
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                
                if (!isset($subNavSections[$section])) {
                    $this->redirectTo($this->page, array());
                }
                break;
            case 'credits':
                
                $section = $this->getArg('section', 'credits');
                $subNavSections =  array(
                    'credits'=>array(
                        'id'=>'credits',
                        'title'=>$this->getLocalizedString("ADMIN_CREDITS_CREDITS_TITLE"),
                        'url'=>$this->buildURL($this->page, array('section'=>'credits'))
                    ),
                    'license'=>array(
                        'id'=>'license',
                        'title'=>$this->getLocalizedString("ADMIN_CREDITS_LICENSE_TITLE"),
                        'url'=>$this->buildURL($this->page, array('section'=>'license'))
                    )
                );
                $this->assign('subNavSections', $subNavSections);
                
                if (isset($subNavSections[$section])) {
                    switch ($section)
                    {
                        case 'license':
                            $licenseFile = ROOT_DIR . "/LICENSE";
                            if (is_file($licenseFile)) {
                                $this->assign('license', file_get_contents($licenseFile));
                            } else {
                                die($licenseFile);
                                throw new KurogoException("Unable to load LICENSE file, you may have a compromised Kurogo Installation");
                            }
                    }
                    $this->setTemplatePage($section);
                } else {
                    $this->redirectTo('section', array());
                }
                break;
                
            case 'index':
                //redirect desktop devices to the "default page"
                $deviceClassifier = Kurogo::deviceClassifier();
                if ($deviceClassifier->isComputer() || $deviceClassifier->isTablet()) {
                    $defaultSection = current($navSections);
                    $this->redirectTo($defaultSection['id'], array());
                }
                break;
            default:
                $this->redirectTo('index', array());
                break;
  
        }  

        $this->assign('section', $section);
  }

}
