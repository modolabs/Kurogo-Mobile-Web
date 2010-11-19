<?php

require_once realpath(LIB_DIR.'/Module.php');

class AdminModule extends Module {
  protected $id = 'admin';
  
  protected function initialize() {

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
        case 'id':
            $item['type'] = 'label';
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

                if ($this->getArg('submit')) {
                    $moduleData = array_merge($moduleData, $this->getArg('moduleData'));
                    $moduleConfigFile = ConfigFile::factory('modules', 'web', true);
                    $moduleConfigFile->addSectionVars(array($moduleID => $moduleData));
                    $moduleConfigFile->saveFile();
                    $this->redirectTo('modules', false, false);
                } 
                
                $this->setPageTitle(sprintf("Administering %s module", $moduleData['title']));
                
                $formListItems = array(
                    $this->getModuleItemForKey('id', $moduleID)
                );
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
                    'subtitle'=>'Administer Module Activation and Order',
                    'url'=>$this->buildBreadcrumbURL('modules', array())
                );
                $this->assign('adminList', $adminList);
                break;
  
        }  
        
  }

}
