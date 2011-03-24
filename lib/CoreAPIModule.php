<?php

class CoreAPIModule extends APIModule
{
    protected $id = 'core';
    protected $vmin = 1;
    protected $vmax = 1;

    // special factory method for core
    public static function factory($command='', $args=array()) {
        $module = new CoreAPIModule();
        $module->init($command, $args);
        return $module;
   }
    
    public function initializeForCommand() {  
    
        switch ($this->command) {
            case 'hello':
            
                $allmodules = $this->getAllModules();
                foreach ($allmodules as $moduleID=>$module) {
                    $modules[] = array(
                        'id'=>$moduleID,
                        'title'=>$module->getModuleVar('title','module'),
                        'vmin'=>$module->getVmin(),
                        'vmax'=>$module->getVmax()
                    );
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