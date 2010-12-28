<?php

require_once realpath(LIB_DIR.'/Module.php');

class AdminModule extends Module {
  protected $id = 'admin';
  
  protected function initialize() {

  }
  
  /* submit values are affected by the _type specifiers */
  protected function prepareSubmitData($key)
  {
    if (!$var = $this->getArg($key)) {
        //couldn't find the variable
        error_log("Could not find variable $key");
        return false;
    } elseif (!$type = $this->getArg('_type')) {
        error_log("Type data not found");
        return $var;
    }
    
    if (!is_array($var)) {
        $type = isset($type[$key]) ? $type[$key] : null;
        return $this->prepareSubmitValue($var, $type);
    } elseif (!isset($type[$key])) {
        error_log("Type data not found for $key");
        return $var;
    }
    
    $types = $type[$key];
    foreach ($types as $key=>$type) {
        if (is_array($type)) {
            foreach ($type as $_key=>$_type) {
                $value = isset($var[$key][$_key]) ? $var[$key][$_key] : null;
                $var[$key][$_key] = $this->prepareSubmitValue($value, $_type);
            }
        } else {
            $value = isset($var[$key]) ? $var[$key] : null;
            $var[$key] = $this->prepareSubmitValue($value, $type);
        }
    }
    
    return $var;    
  }
  
  protected function prepareAdminForSection($section, $adminModule) {
    $sectionVars = $this->getSiteSection($section);
    $formListItems = array();

    foreach ($sectionVars as $key=>$value) {
        $formListItems[] = $this->getSiteItemForKey($section, $key, $value);
    }

    return $formListItems;    
  }

  protected function prepareSubmitValue($value, $type)
  {
    switch ($type)
    {
        case 'paragraph':
            //convert CRLF to LF, then CR to LF (i.e. normalize line endings)
            $value = is_null($value) ? '' : explode("\n\n", str_replace(array("\r\n","\r"), array("\n","\n"), $value));
            break;
        case 'boolean':
            $value = is_null($value) ? 0 : $value;
            break;
        case 'text':
            $value = is_null($value) ? '' : $value;
            break;
        default:
            $value = is_null($value) ? null : $value;
            break;
    }
    
    return $value;
 }
 
  protected function getSectionTitleForKey($key)
  {
     switch (strtolower($key))
     {
        case 'ga':
            return 'Google Analytics';
        default:
           return implode(" ", array_map("ucfirst", explode("_", strtolower($key))));
     }
     return $key;
  }

                             
  protected function getSiteItemForKey($section, $key, $value)
  {
    $item = array(
        'label'=>implode(" ", array_map("ucfirst", explode("_", strtolower($key)))),
        'name'=>"siteData[$section][$key]",
        'typename'=>"siteData][$section][$key",
        'value'=>$value,
        'type'=>'text'
    );

    switch ($key)
    {
        default:
            if (preg_match("/_(DEBUG|ENABLED)$/", $key)) {
                $item['type'] = 'boolean';
            }
            break;
    }
    
    return $item;
  }
  
  protected function initializeForPage() {
  
        switch ($this->page)
        {
             case 'module':
                $moduleID = $this->getArg('moduleID');
                if (empty($moduleID)) {
                    $this->redirectTo('modules');
                }

                $module = Module::factory($moduleID);
                $moduleData = $module->getModuleData();
                $moduleSections = array();
                
                if ($section = $this->getArg('section')) {
                    
                    if ($section=='feeds' && $module->hasFeeds()) {
                        if (strlen($this->getArg('removeFeed'))>0) {
                            $index = $this->getArg('removeFeed');
                            $module->removeFeed($index);
                        }

                        if ($this->getArg('addFeed')) {
                            $feedData = $this->getArg('addFeedData');
                            if (!$module->addFeed($feedData, $error)) {
                                $this->assign('errorMessage', $error);
                            }
                        }

                        $moduleData['feeds'] = $module->loadFeedData();
                        $this->assign('feedURL', $this->buildBreadcrumbURL('module', array(
                                'moduleID'=>$moduleID,
                                'section'=>$section),false
                            ));
                        $this->assign('feedFields', $module->getFeedFields());
                    }
                
                    if (!isset($moduleData[$section])) {
                        $section = null;
                    }
                }

                if ($this->getArg('submit')) {
                    $merge = $this->getArg('merge', true);
                    if ($merge) {
                        $moduleData = $this->prepareSubmitData('moduleData');
                        $moduleData = array_merge($module->getModuleDefaultData(), $moduleData);
                    } else {
                        $moduleData = $this->prepareSubmitData('moduleData');
                    }

                    if ($section) {
                        $moduleData = array($section=>$moduleData[$section]);
                    }

                    $module->saveConfig($moduleData, $section);
                    if ($section) {
                        $this->redirectTo('module', array('moduleID'=>$moduleID), false);
                    } else {
                        $this->redirectTo('modules', false, false);
                    }
                } 
                
                $this->setPageTitle(sprintf("Administering %s module", $moduleData['title']));
                $this->setBreadcrumbTitle($moduleData['title']);
                $this->setBreadcrumbLongTitle($moduleData['title']);
                
                
                $formListItems = array();

                if ($section) {
                    $moduleData = $moduleData[$section];
                } else {
                   $formListItems[] = $this->getModuleItemForKey('id', $moduleID);
                }
                
                foreach ($moduleData as $key=>$value) {
                    if (is_scalar($value)) {
                        $formListItems[] = $module->getModuleItemForKey($key, $value);
                    } else {
                        $moduleSections[$key] = $module->getSectionTitleForKey($key);
                    }
                }
                
                if (!$section) {
                    foreach ($moduleSections as $key=>$title) {
                        $formListItems[] = array(
                            'type'=>'url',
                            'name'=>$title,
                            'value'=>$this->buildBreadcrumbURL('module', array(
                                'moduleID'=>$moduleID,
                                'section'=>$key)
                            )
                        );
                    }

                    if ($module->hasFeeds()) {
                        $formListItems[] = array(
                            'type'=>'url',
                            'name'=>'Data Configuration',
                            'value'=>$this->buildBreadcrumbURL('module', array(
                                'moduleID'=>$moduleID,
                                'section'=>'feeds')
                            )
                        );
                    }

                    $formListItems[] = array(
                        'type'=>'url',
                        'name'=>'Page Data',
                        'value'=>$this->buildBreadcrumbURL('pageData', array(
                            'moduleID'=>$moduleID
                            )
                        )
                    );
                } 
                
                $_module = array(
                    'id'=>$moduleID
                );

                $this->assign('formListItems', $formListItems);
                $this->assign('module'       , $_module);
                $this->assign('section'      , $section);

                if ($section) {
                    $module->prepareAdminForSection($section, $this);
                }
                break;

            case 'modules':
                $allModules = $this->getAllModules();
                $moduleList = array();

                foreach ($allModules as $moduleID=>$moduleData) {
                    try {
                        $moduleList[] = array(
                            'img'=>"/modules/home/images/{$moduleID}.png",
                            'title'=>$moduleData->getModuleName(),
                            'url'=>$this->buildBreadcrumbURL('module', array(
                                'moduleID'=>$moduleID
                                )
                            )
                        );
                        $this->assign('moduleList', $moduleList);
                        
                    } catch(Exception $e) {}
                }
            
                break;
            case 'pageData':
                $moduleID = $this->getArg('moduleID');
                if (empty($moduleID)) {
                    $this->redirectTo('modules');
                }

                $module = Module::factory($moduleID);
                $pageData = $module->getPageData();

                if ($this->getArg('submit')) {
                    $pageData = $this->prepareSubmitData('pageData');
                    $module->saveConfig(array('page'=>$pageData), 'page');
                } 
                
                $this->setPageTitle(sprintf("Administering Page Data for %s", $module->getModuleName()));
                $pages = array();

                foreach ($pageData as $page=>$_pageData) {
                    $item = array();
                    $item[] = array(
                        'label'=>'Page',
                        'type'=>'label',
                        'value'=>$page
                    );
                    $item[] = array(
                        'label'=>'Title',
                        'type'=>'text',
                        'name'=>"pageData[$page][pageTitle]",
                        'typename'=>"pageData][$page][pageTitle",
                        'value'=>isset($_pageData['pageTitle']) ? $_pageData['pageTitle'] : ''
                    );
                    $item[] = array(
                        'label'=>'Breadcrumb Title',
                        'type'=>'text',
                        'name'=>"pageData[$page][breadcrumbTitle]",
                        'typename'=>"pageData][$page][breadcrumbTitle",
                        'value'=>isset($_pageData['breadcrumbTitle']) ? $_pageData['breadcrumbTitle'] : ''
                    );
                    $item[] = array(
                        'label'=>'Breadcrumb Long Title',
                        'type'=>'text',
                        'name'=>"pageData[$page][breadcrumbLongTitle]",
                        'typename'=>"pageData][$page][breadcrumbLongTitle",
                        'value'=>isset($_pageData['breadcrumbLongTitle']) ? $_pageData['breadcrumbLongTitle'] : ''
                    );
                    $pages[$page] = $item;
                }
                
                $_module = array(
                    'id'=>$moduleID
                );

                $this->assign('pages'        , $pages);
                $this->assign('module'       , $_module);

                break;
            case 'strings':
                if ($this->getArg('submit')) {
                    $strings = $this->prepareSubmitData('strings');
                    $configFile = $this->getConfig('strings', 'site', ConfigFile::OPTION_CREATE_WITH_DEFAULT);
                    $configFile->addSectionVars($strings, false);
                    $configFile->saveFile();
                    $this->redirectTo('index', false, false);
                } 

                $config = $this->getConfig('strings', 'site');
                $strings = $config->getSectionVars(true);
                $formListItems = array();
                foreach ($strings as $key=>$value) {
                    if (is_scalar($value)) {
                        $formListItems[] = array(
                        'label'=>implode(" ", array_map("ucfirst", explode("_", strtolower($key)))),
                        'name'=>"strings[$key]",
                        'typename'=>"strings][$key",
                        'value'=>$value,
                        'type'=>'text'
                        );
                    } else {
                        $formListItems[] = array(
                        'label'=>implode(" ", array_map("ucfirst", explode("_", strtolower($key)))),
                        'name'=>"strings[$key]",
                        'typename'=>"strings][$key",
                        'value'=>implode("\n\n", $value),
                        'type'=>'paragraph'
                        );
                    }
                }
                $this->assign('strings', $strings);
                $this->assign('formListItems', $formListItems);
                break;

            case 'site':
                $configFile = ConfigFile::factory('config', 'site');
                $siteVars = $configFile->getSectionVars();

                if ($section = $this->getArg('section')) {
                
                    if (!isset($siteVars[$section])) {
                        $section = null;
                    }
                }
                
                $formListItems = array();

                if ($section) {

                    if ($this->getArg('submit')) {
                        $sectionVars = $this->prepareSubmitData('siteData');
                        $configFile->addSectionVars($sectionVars, true);
                        $configFile->saveFile();
                        $this->redirectTo('site', false, false);
                    }

                    $formListItems = $this->prepareAdminForSection($section, $this);
                                        
                } else {
                    foreach ($siteVars as $sectionName=>$sectionVars){
                        $formListItems[] = array(
                            'type'=>'url',
                            'name'=>$this->getSectionTitleForKey($sectionName),
                            'value'=>$this->buildBreadcrumbURL('site', array(
                                'section'=>$sectionName
                                )
                            )
                        );
                    }
                }
                                            
                $this->assign('section'      , $section);
                $this->assign('formListItems', $formListItems);
                
                break;

            case 'index':
                $adminList = array();
                $adminList[] = array(
                    'title'=>'Modules',
                    'url'=>$this->buildBreadcrumbURL('modules', array()),
                    'subtitle'=>'Manage module configuration and data'
                );
                $adminList[] = array(
                    'title'=>'Site Configuration',
                    'url'=>$this->buildBreadcrumbURL('site', array()),
                    'subtitle'=>'Manage site-wide configuration'
                );
                $adminList[] = array(
                    'title'=>'String Configuration',
                    'url'=>$this->buildBreadcrumbURL('strings', array()),
                    'subtitle'=>'Update textual strings used throughout the site'
                );
                $this->assign('adminList', $adminList);
                break;
  
        }  
        
  }

}
