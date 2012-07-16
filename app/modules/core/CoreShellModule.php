<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CoreShellModule extends ShellModule
{
    protected $id = 'core';

    // special factory method for core
    public static function factory($id='core', $command='', $args=array()) {
        $module = new CoreAPIModule();
        $module->init($command, $args);
        return $module;
    }
     
    public function initializeForCommand() {  
    
        switch ($this->command) {
        	case 'version':
        		$this->out(KUROGO_VERSION);
        		return 0;
        		break;
 
            case 'clearCaches':
                $result = Kurogo::sharedInstance()->clearCaches();
                return 0;
            	break;
            
            case 'fetchAllData':
				$allModules = $this->getAllModules();

				foreach ($allModules as $moduleID => $module) {
					if ($module->isEnabled() && $module->getOptionalModuleVar('PREFETCH_DATA')) {
						$module->setDispatcher($this->Dispatcher());
						$module->init('fetchAllData');
						$module->executeCommand();
					}
				}
                
                return 0;
                break;
                
            default:
                $this->invalidCommand();
                break;
        }
    }
}
