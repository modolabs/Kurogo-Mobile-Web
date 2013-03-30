#!/usr/bin/php -q
<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoShellDispatcher {

    protected $stdin; //standard input stream
    protected $stdout; //standard output stream
    protected $stderr; //standard error stream
    protected $params = array();
    protected $args = array();
    protected $initArgs = array();
    protected $shell = '';
    protected $shellCommand = '';
    protected $siteName;
    protected $workingPath;
    protected $rootDir;
    
    public function getParams() {
        return $this->params;
    }
    
    public function getArgs() {
        return $this->args;
    }
    
    public function getRootDir() {
        return $this->rootDir;
    }
    
    public function getSiteName() {
        return $this->siteName ? $this->siteName : '';
    }
    
    public function getShellCommand() {
        return $this->shellCommand;
    }
    
    function shiftArgs() {
		if (empty($this->args)) {
			return false;
		}
		unset($this->args[0]);
		$this->args = array_values($this->args);
		return true;
	}
    /**
     * Constructor
     *
     * @param array $args the argv.
     */
 
    public function __construct($args = array()) {
        $this->initArgs = $args;
        $this->initConstants();
        $this->parseParams($args);
        $this->initEnvironment();
        $this->stop($this->dispatch());
    }
    
    protected function initConstants() {
        define('KUROGO_SHELL', true);
        
        if (function_exists('ini_set')) {
            ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
            ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 0);
        }
    }
    
    protected function initEnvironment() {
        $this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		
		if (!$this->bootstrap()) {
		    /*
		    $this->stderr(PHP_EOL . "Kurogo Console: ");
			$this->stderr(PHP_EOL . "Unable to load Kurogo class");
			$this->stop();
			*/
			$this->stop(ShellModule::SHELL_LOAD_KUROGO_ERROR);
		}

		$this->shiftArgs();
    }

	protected function bootstrap() {
        $rootDir = $this->getRootDir();
		$kurogoFile = $rootDir . '/lib/Kurogo.php';

		if ($kurogoFile && require_once($kurogoFile)) {
		    $Kurogo = Kurogo::sharedInstance();
		    $path = $this->getSiteName();
		    $Kurogo->initialize($path);
		    
		    //init some must constance in command line
		    if (defined('KUROGO_SHELL')) {
                define('COOKIE_PATH', URL_BASE);
            }
            
            return true;
		} 
		
        return false;
	}
	
    protected function parseParams($params) {
        $this->_parseParams($params);
        
        if (isset($this->params['working'])) {
            $this->workingPath = $this->params['working'];
            unset($this->params['working']);
        }
        
        if (isset($this->params['site'])) {
            if (!preg_match("/^[a-z0-9-_]+$/i", $this->params['site'])) {
                throw new Exception("Invalid site name " . $this->params['site']);
            }
            $this->siteName = $this->params['site'];
            unset($this->params['site']);
        }

        if (isset($this->params['root'])) {
            $this->rootDir = $this->params['root'];
            unset($this->params['root']);
        } else {
            $this->rootDir = realpath(substr(dirname(__FILE__), 0, -4));
        }
    }
    
    protected function _parseParams($params) {
        $count = count($params);
        for ($i=0; $i < $count; $i++) {
            if (isset($params[$i])) {
                if (substr($params[$i], 0, 1) === '-') {
                    $key = substr($params[$i], 1);
                    $this->params[$key] = true;
                    unset($params[$i]);
                    if (isset($params[++$i])) {
                        if (substr($params[$i], 0, 1) !== '-') {
                            $this->params[$key] = str_replace('"', '', $params[$i]);
                            unset($params[$i]);
                        } else {
                            $i--;
                        }
                    }
                } else {
                    $this->args[] = $params[$i];
                    unset($params[$i]);
                }
            }
        }
    }
    
    protected function dispatch() {
        if (isset($this->args[0])) {
            $shell = $this->args[0];
            $command = '';
            
            $this->shell = $shell;
            $this->shiftArgs();
            
            if (isset($this->args[0]) && $this->args[0]) {
                $command = $this->args[0];
            }
            $this->shellCommand = $command;
            $args = $this->getParams();

            $Kurogo = Kurogo::sharedInstance();
            $Kurogo->setRequest($shell, $command, $args);
    
    		try {
				if ($module = ShellModule::factory($shell, $command, $args, $this)) {
					if (!$command) {
						$this->stop(ShellModule::SHELL_COMMAND_EMPTY);
					}
					$Kurogo->setCurrentModule($module);
					return $module->executeCommand();
				}
			} catch (KurogoModuleNotFound $e) {
				$this->stop(ShellModule::SHELL_MODULE_NOT_FOUND);
			} 
        }
        
        $this->stop(ShellModule::SHELL_NO_MODULE);
        /*
        $this->stderr(PHP_EOL . "Kurogo Console: ");
	    $this->stderr(PHP_EOL . "Not found the shell module");
		$this->stop();
		*/
    }
    
    /**
     * Prompts the user for input, and returns it.
     */
    public function getInput($prompt, $options = null, $default = null) {
        if (is_array($options)) {
            $printOptions = '(' . implode('/', $options) . ')';
        } else {
            $printOptions = $options;
        }
        
        if (is_null($default)) {
			$this->stdout($prompt . " $printOptions " . PHP_EOL . '> ', false);
		} else {
			$this->stdout($prompt . " $printOptions " . PHP_EOL . "[$default] > ", false);
		}
		$result = fgets($this->stdin);

		if ($result === false) {
			exit;
		}
        $result = trim($result);

		if ($default != null && empty($result)) {
			return $default;
		}
		return $result;
    }
    
    public function stdout($string, $newLine = true) {
        if ($newLine) {
            fwrite($this->stdout, $string . PHP_EOL);
        } else {
            fwrite($this->stdout, $string);
        }
    }
    
    public function stderr($string) {
        fwrite($this->stderr, $string);
    }
    
    public function stop($status = 0) {
        exit($status);
    }
}

$dispatcher = new KurogoShellDispatcher($argv);
