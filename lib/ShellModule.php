<?php

abstract class ShellModule extends Module {

    protected $responseVersion;
    protected $requestedVersion;
    protected $requestedVmin;
    protected $vmin;
    protected $vmax;
    protected $command = '';
    protected $response; //response object
    protected $dispatcher = null;

    public function getVmin() {
        return $this->vmin;
    }

    public function getVmax() {
        return $this->vmax;
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
        $error = new KurogoError(2, 'Module Disabled', 'This module has been disabled');
        $this->throwError($error);
    }

    /**
     * The module must be run securely (https)
     */
    protected function secureModule() {
        return false;
    }

    /**
     * The user cannot access this module
     */
    protected function unauthorizedAccess() {
        return false;
    }

    /**
     * An unrecognized command was requested
     */
    protected function invalidCommand() {
        $error = new KurogoError(5, 'Invalid Command', "The $this->id module does not understand $this->command");
        $this->throwError($error);
    }

    /**
     * The module was unable to load the version requested
     */
    protected function noVersionAvailable() {
        $error = new KurogoError(6, 'No version available', "A command matching the specified version could not be processed");
        $this->throwError($error);
    }

    /**
     * Sets the error portion of the response. Some messages can return both a response and an error
     * @param KurogoError $error an error object
     */
    protected function setResponseError(KurogoError $error) {
        $this->loadResponseIfNeeded();
        $this->response->setError($error);
    }

    /**
     * Set the response text. Typically an object or associate array (a dictionary)
     */
    protected function setResponse($response) {
        $this->loadResponseIfNeeded();
        $this->response->setResponse($response);
    }
 
    /**
     * Throw a fatal error in the API. Used for user created errors like invalid parameters. Stops 
     * execution and displays the error
     * @param KurogoError $user a valid error object
     */
    protected function throwError(KurogoError $error) {
        $this->loadResponseIfNeeded();
        $this->setResponseError($error);
        if (is_null($this->responseVersion)) {
            $this->setResponseVersion(0);
        }
        $result = $this->response->display();
        $this->error($result);
        exit();
    }

    protected function getShellConfig($name, $opts=0) {
        $opts = $opts | ConfigFile::OPTION_CREATE_WITH_DEFAULT;
        $config = ModuleConfigFile::factory($this->configModule, "shell-$name", $opts, $this);
        return $config;
    }

    protected function getShellConfigData($name) {
        $config = $this->getShellConfig($name);
        return $config->getSectionVars(Config::EXPAND_VALUE);
    }

    protected function getModuleNavigationIDs() {
        $moduleNavConfig = $this->getModuleNavigationConfig();
        
        $modules = array(
            'primary'  => array_keys($moduleNavConfig->getOptionalSection('primary_modules')),
            'secondary'=> array_keys($moduleNavConfig->getOptionalSection('secondary_modules'))
        );

        return $modules;
    }

    /**
     * Lazy load the response object
     */
    private function loadResponseIfNeeded() {
        if (!isset($this->response)) {
            $this->response = new ShellResponse($this->id, $this->configModule, $this->command);
        }
    }
  
    /**
     * Sets the requested version and minimum accepted version
     */
    private function setRequestedVersion($requestedVersion, $minimumVersion) {
        if ($requestedVersion) {
            $this->requestedVersion = intval($requestedVersion);
    
            if ($minimumVersion) {
                $this->requestedVmin = intval($minimumVersion);
            } else {
                $this->requestedVmin = $this->requestedVersion;
            }
        } else {
            $this->requestedVersion = null;
            $this->requestedVmin = null;
        }
    }
  
    /**
     * Called by the modules to set what version we are returning to the client_info
     * @param int $version the version we are returning
     */
    protected function setResponseVersion($version) {
        $this->loadResponseIfNeeded();
        $this->response->setVersion($version);
        $this->responseVersion = $this->response->getVersion();
    }

    protected function loadSiteConfigFile($name, $opts=0) {
        $config = ConfigFile::factory($name, 'site', $opts);
        Kurogo::siteConfig()->addConfig($config);

        return $config->getSectionVars(true);
    }

    /**
     * Prompts the user for input, and returns it.
     */
    protected function in($prompt, $options = null, $default = null) {
        return $this->dispatcher()->getInput($prompt, $options = null, $default = null);
    }
    
    public function out($string, $newLine = true) {
        $string = is_array($string) ? implode("\n", $string) : $string;
        return $this->dispatcher()->stdout($string, $newLine = true);
    }
    
    public function error($string) {
        $string = is_array($string) ? implode("\n", $string) : $string;
        $this->dispatcher()->stderr($string);
    }
    
    /**
     * Factory method
     * @param string $id the module id to load
     * @param string $command the command to execute
     * @param array $args an array of arguments
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
        $this->setRequestedVersion($this->getArg('v', null), $this->getArg('vmin', null));
        $this->setCommand($command);
    }
    
    /**
     * Execute the command. Will call initializeForCommand() which should set the version, error and response
     * values appropriately
     */
    public function executeCommand() {
        if (empty($this->command)) {
            throw new KurogoException("Command not specified");
        }
        $this->loadResponseIfNeeded();
        $this->loadSiteConfigFile('strings');
    
        $this->initializeForCommand();
        $json = $this->response->getJSONOutput();
        $this->out($json);
        exit();
    }
    
    /**
     * All modules must implement this method to handle the logic of each shell command.
     */
    abstract protected function initializeForCommand();
}

class ShellResponse extends APIResponse {

    public function display() {
        $json = $this->getJSONOutput();
        return $json;
    }
}



