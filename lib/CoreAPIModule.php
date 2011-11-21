<?php

class CoreAPIModule extends APIModule
{
    protected $id = 'core';
    protected $vmin = 1;
    protected $vmax = 1;

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
                $homeModules = $this->getModuleNavigationIDs();
                foreach ($allmodules as $moduleID=>$module) {
                    if (!$module->getModuleVar('disabled', 'module')) {
                        $home = false;
                        if ( ($key = array_search($moduleID, $homeModules['primary'])) !== FALSE) {
                            $home = array('type'=>'primary', 'order'=>$key);
                        } elseif (($key = array_search($moduleID, $homeModules['secondary'])) !== FALSE) {
                            $home = array('type'=>'secondary', 'order'=>$key);
                        }
                        
                    
                        $modules[] = array(
                            'id'        =>$module->getID(),
                            'tag'       =>$module->getConfigModule(),
                            'title'     =>$module->getModuleVar('title','module'),
                            'access'    =>$module->getAccess(AccessControlList::RULE_TYPE_ACCESS),
                            'vmin'      =>$module->getVmin(),
                            'vmax'      =>$module->getVmax(),
                            'home'      =>$home
                        );
                    }
                }
                $response = array(
                    'version'=>KUROGO_VERSION,
                    'modules'=>$modules
                );
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}
