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
        return false;
    } elseif (!$type = $this->getArg('_type')) {
        DEbug::die_here();
        return $var;
    }
    
    if (!is_array($var)) {
        $type = isset($type[$key]) ? $type[$key] : null;
        DEbug::die_here();
        return $this->prepareSubmitValue($var, $type);
    } elseif (!isset($type[$key])) {
        DEbug::die_here();
        return $var;
    }
    
    $types = $type[$key];
    foreach ($types as $key=>$type) {
        $value = isset($var[$key]) ? $var[$key] : null;
        $var[$key] = $this->prepareSubmitValue($value, $type);
    }

    return $var;    
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
            case 'strings':
                if ($this->getArg('submit')) {
                    //$module->saveConfig($moduleData, $section);
                    $strings = $this->prepareSubmitData('strings');
                    $configFile = $this->getConfig('strings', 'site');
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
                        'label'=>$key,
                        'name'=>"strings[$key]",
                        'typename'=>"strings][$key",
                        'value'=>$value,
                        'type'=>'text'
                        );
                    } else {
                        $formListItems[] = array(
                        'label'=>$key,
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

                if ($this->getArg('submit')) {
                    //$module->saveConfig($moduleData, $section);
                    $this->redirectTo('index', false, false);
                } 
            
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
                    'subtitle'=>''
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
