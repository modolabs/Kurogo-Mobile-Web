<?php

require_once realpath(LIB_DIR.'/Module.php');

class AdminModule extends Module {
  protected $id = 'admin';
  
  protected function initialize() {

  }

  protected function getModuleDefaultData()
  {
    return array(
        'title'=>'No Title',
        'homescreen'=>0,
        'primary'=>0,
        'disabled'=>0,
        'disableable'=>0,
        'movable'=>0,
        'new'=>0,
        'search'=>0,
        'protected'=>0,
        'secure'=>0
    );
  }
                    
  private function getModuleItemForKey($key, $value)
  {
    $item = array(
        'label'=>ucfirst($key),
        'name'=>"moduleData[$key]",
        'value'=>$value
    );

    switch ($key)
    {
        case 'title':
            $item['type'] = 'text';
            break;
        case 'homescreen':
        case 'primary':
        case 'disabled':
        case 'disableable':
        case 'movable':
        case 'new':
        case 'search':
        case 'protected':
        case 'secure':
            $item['type'] = 'boolean';
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
                $modules = $this->getAllModuleData();
                $moduleData = $this->getModuleDefaultData();

                if ($this->getArg('submit')) {
                    $moduleData = array_merge($moduleData, $this->getArg('moduleData'));
                    $moduleConfigFile = ConfigFile::factory('modules', 'web');
                    $moduleData = array($moduleID => $moduleData);
                    
                    $moduleConfigFile->addSectionVars($moduleData);
                    $moduleConfigFile->saveFile();
                    $this->redirectTo('modules', false, false);
                } elseif (isset($modules[$moduleID])) {
                    $moduleData = array_merge($moduleData, $modules[$moduleID]);
                }
                
                $this->setPageTitle(sprintf("Administering %s module", $moduleData['title']));
                
                $formListItems = array();
                foreach ($moduleData as $key=>$value) {
                    $formListItems[] = $this->getModuleItemForKey($key, $value);
                }

                $formListItems[] = array(
                    'type'=>'submit',
                    'name'=>'submit',
                    'value'=>'Save'
                );
                
                $module = array(
                    'id'=>$moduleID
                );

                $this->assign('formListItems', $formListItems);                
                $this->assign('module'       , $module);                
                break;

            case 'modules':
                $allModules = $this->getAllModuleData();
                $moduleList = array();

                foreach ($allModules as $moduleID=>$moduleData) {
                    try {
                        $moduleList[] = array(
                            'title'=>$moduleData['title'],
                            'url'=>$this->buildBreadcrumbURL('module', array(
                                'moduleID'=>$moduleID
                                )
                            )
                        );
                        $this->assign('moduleList', $moduleList);
                        
                    } catch(Exception $e) {
                    }
                }
                
            
                break;
            case 'index':
                $adminList = array();
                $adminList[] = array(
                    'title'=>'Modules',
                    'subtitle'=>'Administer Module Activation and Order',
                    'url'=>$this->buildBreadcrumbURL('modules', array())
                );
                $this->assign('adminList', $adminList);
                break;
  
        }  
        
  }

}
