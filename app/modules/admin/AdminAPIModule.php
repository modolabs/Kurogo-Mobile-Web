<?php

class AdminAPIModule extends APIModule
{
    protected $id = 'admin';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }
    
    public function initializeForCommand() {  
        $this->requiresAdmin();
        
        switch ($this->command) {
            case 'sectionData':
                $type = $this->getArg('type');
                $section = $this->getArg('section');
                
                
                
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
   
}