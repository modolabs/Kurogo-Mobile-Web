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
    protected $verbose = 1;

    // special factory method for core
    public static function factory($id='core', $command='', $args=array()) {
        $module = new CoreAPIModule();
        $module->init($command, $args);
        return $module;
    }

    protected function out($string, $newLine = true) {
        if($this->verbose){
            parent::out($string, $newLine);
        }
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
                
            case 'deployPostFlight':
                $this->verbose = $this->getArg('v');
                $this->out('Running KurogoShell Core deployPostFlight');

                $postFlightFilePath = SITE_SCRIPTS_DIR . DIRECTORY_SEPARATOR . 'deployPostFlight.sh';
                
                if(!file_exists($postFlightFilePath)){
                    $this->out("$postFlightFilePath does not exist, skipping execution");
                    return 0;
                } elseif (!is_executable($postFlightFilePath)) {
                	$this->out("$postFlightFilePath exists, but is not executable. This must be fixed");
                	return 126;
                }

                $outputLines = array();
                exec(sprintf("%s %s %s", 
                		escapeshellcmd($postFlightFilePath), 
                		escapeshellarg(ROOT_DIR), 
                		escapeshellarg(SITE_DIR)
                	), $outputLines, $returnValue);
                	
                foreach ($outputLines as $lineNumber => $line) {
                    $this->out($line);
                }
                return $returnValue;
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }
}
