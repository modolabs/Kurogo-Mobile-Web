<?php

class CoreShellModule extends ShellModule
{
    protected $id = 'core';

    // special factory method for core
    public static function factory($id='core', $command='', $args=array()) {
        $module = new CoreAPIModule();
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
                //$homeModules = $this->getModuleNavigationIDs();
                foreach ($allmodules as $moduleID=>$module) {
                    if ($module->isEnabled()) {
                        $modules[] = array(
                            'id'        =>$module->getID(),
                            'tag'       =>$module->getConfigModule(),
                            'title'     =>$module->getModuleVar('title','module'),
                            'access'    =>$module->getAccess(AccessControlList::RULE_TYPE_ACCESS),
                        );
                    }
                }
 
                $modules = isset($modules) && is_array($modules) ? var_export($modules, true) : '';
                $this->out($modules);
                
                return 0;
                break;
                
            case 'classify':
                return 10;
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
}
