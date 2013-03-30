<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class APIModule extends Module
{
    protected $responseVersion;
    protected $requestedVersion;
    protected $requestedVmin;
    protected $clientVersion;
    protected $clientPagetype;
    protected $clientPlatform;
    protected $clientBrowser;
    protected $vmin;
    protected $vmax;
    protected $command = '';
    protected $response; //response object
    protected $warnings = array();
  
    public function getVmin() {
        return $this->vmin;
    }

    public function getVmax() {
        return $this->vmax;
    }
    
    public function getPayload() {
        return null;
    }

    public function getWebBridgeConfig($platform=null) {
        return KurogoWebBridge::getHelloMessageForModule($this->configModule, $platform);
    }

    protected function getAllModuleNavigationData() {

        $modules = $moduleNavData = array(
            'primary'  => array(), 
            'secondary'=> array(), 
        );
        
        $navModules = Kurogo::getSiteSections('navigation', Config::APPLY_CONTEXTS_NAVIGATION);
        foreach ($navModules as $moduleID=>$moduleData) {
            $type = Kurogo::arrayVal($moduleData, 'type', 'primary');
            $moduleNavData[$type][$moduleID] = $moduleData;
        }
        
        return $moduleNavData;
    }
    
    public function warningHandler($errno, $str, $file, $line) {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }
        
        $this->loadResponseIfNeeded();
        $this->response->addWarning(new KurogoWarning($errno, $str, $file, $line));
        
        return true;
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
        $secure_host = Kurogo::getOptionalSiteVar('SECURE_HOST', $_SERVER['SERVER_NAME']);
        if (empty($secure_host)) {
            $secure_host = $_SERVER['SERVER_NAME'];
        }
        $secure_port = Kurogo::getOptionalSiteVar('SECURE_PORT', 443);
        if (empty($secure_port)) {
            $secure_port = 443;
        }

        $redirect= sprintf("https://%s%s%s", $secure_host, $secure_port == 443 ? '': ":$secure_port", $_SERVER['REQUEST_URI']);
        Kurogo::log(LOG_DEBUG, "Redirecting to secure url $redirect",'module');
        Kurogo::redirectToURL($redirect, Kurogo::REDIRECT_PERMANENT);
    }

    protected function unsecureModule() {
        $host = Kurogo::getOptionalSiteVar('HOST', $_SERVER['SERVER_NAME'], 'site settings');
        if (empty($host)) {
            $host = $_SERVER['SERVER_NAME'];
        }
        $port = Kurogo::getOptionalSiteVar('PORT', 80, 'site settings');
        if (empty($port)) {
            $port = 80;
        }

        $redirect= sprintf("http://%s%s%s", $host, $port == 80 ? '': ":$port", $_SERVER['REQUEST_URI']);
        Kurogo::log(LOG_DEBUG, "Redirecting to non-secure url $redirect",'module');
        Kurogo::redirectToURL($redirect);
    }
  
   /**
     * The user cannot access this module
     */
    protected function unauthorizedAccess() {
        $error = new KurogoError(4, 'Unauthorized', 'You are not permitted to use the '.$this->getModuleVar('title', 'module').' module');
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
        exit();
    }
  
    protected function redirectTo($command, $args=array()) {      
        $url = URL_BASE . API_URL_PREFIX . "/$this->id/$command";
    
        if (count($args)) {
          $url .= http_build_query($args);
        }
        
        //error_log('Redirecting to: '.$url);
        Kurogo::redirectToURL($url);
    }

   /**
     * Factory method
     * @param string $id the module id to load
     * @param string $command the command to execute
     * @param array $args an array of arguments
     * @return APIModule 
     */
    public static function factory($id, $command='', $args=array()) {

        if (!$module = parent::factory($id, 'api')) {
            return false;
        }
        if ($command) {
            $module->init($command, $args);
        }

        return $module;
    }

    public static function getAllModules() {
        $configFiles = glob(SITE_CONFIG_DIR . "/*/module.ini");
        $modules = array();
    
        foreach ($configFiles as $file) {
            if (preg_match("#" . preg_quote(SITE_CONFIG_DIR,"#") . "/([^/]+)/module.ini$#", $file, $bits)) {
                $id = $bits[1];
                try {
                    if ($module = APIModule::factory($id)) {
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
     * Lazy load the response object
     */
    private function loadResponseIfNeeded() {
        if (!isset($this->response)) {
            $this->response = new APIResponse($this->id, $this->configModule, $this->command);
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
    
    protected function setContext($context) {
        $this->loadResponseIfNeeded();
        $this->context = $context;
        $this->response->setContext($context);
    }
    
    protected function setClientVersion($version) {
    	$this->clientVersion = $version;
    }
    
    protected function setClient($clientString) {
        if ($clientString) {
            $parts = explode('-', $clientString);
            $this->clientPagetype = Kurogo::arrayVal($parts, 0);
            $this->clientPlatform = Kurogo::arrayVal($parts, 1);
            $this->clientBrowser = Kurogo::arrayVal($parts, 2);
            Kurogo::deviceClassifier()->setDevice($clientString);
        }
    }
  
   /**
     * Initialize the request
     */
    protected function init($command='', $args=array()) {
        set_error_handler(array($this, 'warningHandler'), E_WARNING | E_NOTICE | E_STRICT);
        
        parent::init();
        $this->setArgs($args);
        $this->setRequestedVersion($this->getArg('v', null), $this->getArg('vmin', null));
        $this->setClientVersion($this->getArg('clientv', null));
        $this->setClient($this->getArg('client'));
        if ($context = $this->getArg('context', null)) {
            $this->setContext($context);
        }
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
        if ($this->clientBrowser == 'native') {
            if ($appData = Kurogo::getAppData($this->clientPlatform)) {
            	if ($minversion = Kurogo::arrayVal($appData, 'minversion')) {
            		if (version_compare($this->clientVersion, $minversion)<0) {
            			$data = array(
            				'url'=>Kurogo::arrayVal($appData, 'url'),
            				'version'=>Kurogo::arrayVal($appData, 'version')
						);
						$error = new KurogoError(7, 'Upgrade Required', 'You must upgrade your application');
						$error->setData($data);
						$this->throwError($error);
            		}
            	}
            }
        }
    
        $this->initializeForCommand();
        
        $json = $this->response->getJSONOutput();
        $size = strlen($json);
        if ($this->logView) {
            $this->logCommand($size);
        }
        header("Content-Length: " . $size);
        header("Content-Type: application/json; charset=utf-8");
        echo $json;
        exit();
    }
    
    protected function logCommand($size=null) {
        KurogoStats::logView('api', $this->configModule, $this->command, $this->logData, $this->logDataLabel, $size);
    }
      
   /**
     * All modules must implement this method to handle the logic of each command.
     */
    abstract protected function initializeForCommand();
  
    /**
      * Implement if your app supports the buildNative command for native app shim modules.
      * Return a list of pages supported by your native app shim templates.
      */
    protected function getNativePagelist() {
        return array();
    }
}



