<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class ShellModule extends Module {

    protected $dispatcher = null;
    protected $command = '';

    const SHELL_COMMAND_EMPTY=1;
    const SHELL_MODULE_DISABLED=2;
    const SHELL_UNAUTHORIZED=3;
    const SHELL_INVALID_COMMAND=4;
    const SHELL_MODULE_NOT_FOUND=5;
    const SHELL_LOAD_KUROGO_ERROR=6;
    const SHELL_NO_MODULE=7;

    //always allow access
    protected function getAccessControlLists($type) {
        return array(AccessControlList::allAccess());
    }

    protected function setDispatcher(&$dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    protected function Dispatcher() {
        
        return $this->dispatcher;
    }

    /**
     * Set the command
     * @param string the command
     */
    protected function setCommand($command) {
        $this->command = $command;
    }

    /**
     * The module is disabled.
     */
    protected function moduleDisabled() {
        //$error = new KurogoError(2, 'Module Disabled', 'This module has been disabled');
        //$this->throwError($error);
        $this->stop(self::SHELL_MODULE_DISABLED);
    }

    /**
     * The module must be run securely (https)
     */
    protected function secureModule() {
        return false;
    }

    /**
     * The module must be run securely (https)
     */
    protected function unsecureModule() {
        return false;
    }

    /**
     * The user cannot access this module
     */
    protected function unauthorizedAccess() {
        //$error = new KurogoError(4, 'Unauthorized', 'You are not permitted to use the '.$this->getModuleVar('title', 'module').' module');
        //$this->throwError($error);
        $this->stop(self::SHELL_UNAUTHORIZED);
    }

    /**
     * An unrecognized command was requested
     */
    protected function invalidCommand() {
        //$error = new KurogoError(5, 'Invalid Command', "The $this->id module does not understand $this->command");
        //$this->throwError($error);
        $this->stop(self::SHELL_INVALID_COMMAND);
    }

    /**
     * Throw a fatal error in the shell. Used for user created errors like invalid parameters. Stops
     * execution and displays the error
     */
    protected function throwError($error) {
        $string = array(
            'code:'.$error->getCode(),
            'title:'.$error->getTitle(),
            'message:'.$error->getMessage()
        );
        $this->error($string);
        $this->stop();
    }

    public static function getAllModules() {
        $configFiles = glob(SITE_CONFIG_DIR . "/*/module.ini");
        $modules = array();

        foreach ($configFiles as $file) {
            if (preg_match("#" . preg_quote(SITE_CONFIG_DIR,"#") . "/([^/]+)/module.ini$#", $file, $bits)) {
                $id = $bits[1];
                try {
                    if ($module = ShellModule::factory($id)) {
                       $modules[$id] = $module;
                    }
                } catch (KurogoException $e) {
                }
            }
        }
        ksort($modules);
        return $modules;
    }

    /**
     * Prompts the user for input, and returns it.
     */
    protected function in($prompt, $options = null, $default = null) {
        return $this->Dispatcher()->getInput($prompt, $options, $default);
    }

    protected function out($string, $newLine = true) {
        $string = is_array($string) ? implode(PHP_EOL, $string) : $string;
        $this->Dispatcher()->stdout($string, $newLine);
    }

    protected function error($string) {
        $string = is_array($string) ? implode(PHP_EOL, $string) : $string;
        $this->Dispatcher()->stderr($string);
    }

    protected function stop($status = 0) {
        $this->Dispatcher()->stop($status);
    }
    /**
     * Factory method
     * @param string $id the module id to load
     * @param string $command the command to execute
     * @param array $args an array of arguments
     * @param KurogoShellDispatcher $dispatcher an object of KurogoShellDispatcher
     * @return ShellModule
     */

    public static function factory($id, $command='', $args=array(), $dispatcher = null) {
        if (!$module = parent::factory($id, 'shell')) {
            return false;
        }
        if ($command) {
            $module->setDispatcher($dispatcher);
            $module->init($command, $args);
        }

        return $module;
    }

    /**
     * Initialize the request
     */
    protected function init($command='', $args=array()) {
        parent::init();
        $this->setArgs($args);
        $this->setCommand($command);
    }

    /**
     * Execute the command. Will call initializeForCommand() which should set the version, error and response
     * values appropriately
     */
    public function executeCommand() {
        if (empty($this->command)) {
            $this->stop(self::SHELL_COMMAND_EMPTY);
            //$error = new KurogoError(6, 'Command not specified', "");
            //$this->throwError($error);
        }
        return $this->initializeForCommand();
    }

    /**
     * All modules must implement this method to handle the logic of each shell command.
     */
    abstract protected function initializeForCommand();
    
    protected function preFetchData(DataModel $controller, &$response) {
    	$retriever = $controller->getRetriever();
    	return $retriever->getData($response);
    }
    
    /* subclasses can override this method to return all controllers list */
    protected function getAllControllers() {
        $controllers = array();
        
        if ($feeds = $this->loadFeedData()) {
            foreach ($feeds as $index => $feesData) {
                if ($feed = $this->getFeed($index)) {
                    $controllers[] = $feed;
                }
            }
        }
        
        return $controllers;
    }
    
    protected function preFetchAllData() {
    	$time = 0;
    	$controllers = $this->getAllControllers() ;
		$this->out("Fetching $this->configModule");
		foreach ($controllers as $key=>$controller) {
			$title = $controller->getTitle() ? $controller->getTitle() : $key;
			$out = "Fetching $title. ";
	        $start = microtime(true);
			$data = $this->preFetchData($controller, $response);
			if ($response->getFromCache()) {
				$out .= "In cache. ";
			} else {
				$out .= "Fetch took " . sprintf("%.2f", $response->getTimeElapsed()) . " seconds. ";
			}
	        $end = microtime(true);
	        $diff = $end-$start;
			$time += $diff;
			$out .= "Total: " . sprintf("%.2f", $end-$start) . " seconds.";
			$this->out($out);
		}
		$this->out(count($controllers) . " feeds took " . sprintf("%.2f", $time) . " seconds.");
    }

    //
    // Messaging support
    //

    public function messagingEnabled() {
        // if module doesn't specify messaging parameter, use site value
        $siteConfig = Kurogo::getOptionalSiteSection('notifications');
        $siteEnabled = Kurogo::arrayVal($siteConfig, 'ENABLED_BY_DEFAULT', false);
        return $this->getOptionalModuleVar('messaging', $siteEnabled, null, 'module');
    }

    /*
     * Return tags for all different types of messages sent by this module 
     * that are not user-dependent.  e.g. "urgent", "casual"
     */
    public function getStaticNotificationContexts() {
        return array();
    }

    /*
     * Return relevant updates for non-user-dependent message types
     * since the specified time.
     * Format: array(array('title' => 'Message Title', 'body' => 'Details'))
     */
    public function getUpdatesForStaticContext($context, $platform, $lastCheckTime) {
        return array();
    }

}



