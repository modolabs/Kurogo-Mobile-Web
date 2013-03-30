<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoShellModule extends ShellModule
{
    protected $id = 'kurogo';
    protected $verbose = 1;

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
                $time = 0;

                $this->out("Fetching data for site: " . SITE_NAME);
                $start = microtime(true);

                foreach ($allModules as $moduleID => $module) {
                    if ($module->isEnabled() && $module->getOptionalModuleVar('PREFETCH_DATA')) {
                        $module->setDispatcher($this->Dispatcher());
                        try {
                            $module->init('fetchAllData');
                            $module->executeCommand();
                        } catch (KurogoException $e) {
                            $this->out("Error: " . $e->getMessage());
                        }
                    }
                }
                $end = microtime(true);
                $diff = $end-$start;
                $time += $diff;
                $this->out("Total: " . sprintf("%.2f", $end-$start) . " seconds.");
                
                return 0;
                break;
                
            case 'deployPostFlight':
                $this->verbose = $this->getArg('v');
                $this->out('Running KurogoShell kurogo deployPostFlight');

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
