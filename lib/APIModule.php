<?php

abstract class APIModule extends Module
{
  protected $responseVersion;
  protected $requestedVersion;
  protected $minimumVersion; 
  protected $command = '';
  protected $response; //response object

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
    $error = new KurogoError(3, 'Secure access required', 'This module must be used using https');
    $this->throwError($error);
  }

 /**
   * The user cannot access this module
   */
  protected function unauthorizedAccess() {
    $error = new KurogoError(4, 'Unauthorized', 'You are not permitted to use this module');
    $this->throwError($error);
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
    $this->response->display();
  }
  
 /**
   * Factory method
   * @param string $id the module id to load
   * @param string $command the command to execute
   * @param array $args an array of arguments
   * @return APIModule 
   */
  public static function factory($id, $command='', $args=array()) {

    $module = parent::factory($id, 'api');
    if ($command) {
        $module->init($command, $args);
    }
    return $module;
   }

 /**
   * Lazy load the response object
   */
  private function loadResponseIfNeeded() {
    if (!isset($this->response)) {
      $this->response = new APIResponse($this->id, $this->command);
    }
  }
  
 /**
   * Sets the requested version and minimum accepted version
   */
  private function setRequestedVersion($requestedVersion, $minimumVersion) {
    if ($requestedVersion) {
        $this->requestedVersion = intval($requestedVersion);

        if ($minimumVersion) {
            $this->minimumVersion = intval($minimumVersion);
        } else {
            $this->minimumVersion = $this->requestedVersion;
        }
    } else {
        $this->requestedVersion = null;
        $this->minimumVersion = null;
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
    $this->loadResponseIfNeeded();
    $this->initializeForCommand();
    $this->response->display();
  }
  
 /**
   * All modules must implement this method to handle the logic of each command.
   */
  abstract protected function initializeForCommand();

}


