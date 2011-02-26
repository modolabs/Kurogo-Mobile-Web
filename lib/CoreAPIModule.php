<?php

class CoreAPIModule extends APIModule
{
    protected $id = 'core';

    // special factory method for core
    public static function factory($command='', $args=array()) {
        $module = new CoreAPIModule();
        $module->init($command, $args);
        return $module;
   }
    
    public function initializeForCommand() {  
    
        switch ($this->command) {
            case 'hello':
                $response = array(
                    'version'=>KUROGO_VERSION
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