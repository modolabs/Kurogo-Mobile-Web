<?php

require_once realpath(LIB_DIR.'/Module.php');

class AdminModule extends Module {
  protected $id = 'admin';
  
  protected function initialize() {

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
                    if (isset($moduleData[$section])) {
                        $module->prepareAdminForSection($section, $this);
                    } else {
                        $section = null;
                    }
                }
                
                if ($this->getArg('submit')) {
                    $merge = $this->getArg('merge', true);
                    if ($merge) {
                        $moduleData = array_merge($module->getModuleDefaultData(), $this->getArg('moduleData'));
                    } else {
                        $moduleData = $this->getArg('moduleData');
                    }                    
                    
                    $module->saveConfig($moduleData, $section);
                    $this->redirectTo('modules', false, false);
                } 
                
                $this->setPageTitle(sprintf("Administering %s module", $moduleData['title']));
                $this->setBreadcrumbTitle($moduleData['title']);
                $this->setBreadcrumbLongTitle($moduleData['title']);
                
                $formListItems = array(
                    $this->getModuleItemForKey('id', $moduleID)
                );

                if ($section) {
                    $moduleData = $moduleData[$section];
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
                }
                
                $module = array(
                    'id'=>$moduleID
                );

                $this->assign('formListItems', $formListItems);
                $this->assign('module'       , $module);
                $this->assign('section'      , $section);
                break;

            case 'modules':
                $allModules = $this->getAllModules();
                $moduleList = array();

                foreach ($allModules as $moduleID=>$moduleData) {
                    try {
                        $moduleList[] = array(
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

            case 'index':
                $adminList = array();
                $adminList[] = array(
                    'title'=>'Modules',
                    'url'=>$this->buildBreadcrumbURL('modules', array())
                );
                $this->assign('adminList', $adminList);
                break;
  
        }  
        
  }

}
