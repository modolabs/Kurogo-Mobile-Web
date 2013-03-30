<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package DataModel
 */

/**
 * A generic class to handle the retrieval of external data
 * 
 * Handles retrieval, caching and parsing of data. 
 * @package DataModel
 */
includePackage('DataModel'); 
includePackage('DataRetriever');
class DataModel {
    
    protected $DEFAULT_PARSER_CLASS;
    protected $DEFAULT_RETRIEVER_CLASS='URLDataRetriever';
    protected $RETRIEVER_INTERFACE = 'DataRetriever';
    protected $initArgs=array();
    protected $retriever;
    protected $title;
    protected $debugMode=false;
    protected $options = array();

    /**
      * Clears the internal cache for a new request. All responses and options are erased and 
      * clearInternalCache is called on the retriever
      */
    public function clearInternalCache() {
        $this->options = array();
        $this->retriever->clearInternalCache();
    }
    
    
    /**
     * Turns on or off debug mode. In debug mode, URL requests and information are logged to the php error log
     * @param bool 
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }
   
    protected function setOption($option, $value) {
        $this->options[$option] = $value;
        $this->retriever->setOption($option, $value);
    }

    protected function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Sets the data retriever to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the retriever dynamically.
     * @param retriever a instantiated DataRetriever object
     */
    public function setRetriever(DataRetriever $retriever) {
        if ($retriever instanceOf $this->RETRIEVER_INTERFACE) {
            $this->retriever = $retriever;
        } else {
            throw new KurogoException("Data Retriever " . get_class($retriever) . " must conform to $this->RETRIEVER_INTERFACE");
        }
    }
    
    /**
     * Returns the data retriever
     * @return DataRetriever
     */
    public function getRetriever() {
        return $this->retriever;
    }
    
    /**
     * Sets the title of the controller. Subclasses could use this if the title is dynamic.
     * @param string
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Returns the title of the controller.
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * The initialization function. Sets the common parameters based on the $args. This method is
     * called by the public factory method. Subclasses can override this method, but must call parent::init()
     * FIRST. Arguments are also passed to the data retiever object
     * @param array $args an associative array of arguments and paramters
     */
    protected function init($args) {
        $this->initArgs = $args;

        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        }

        // use a retriever class if set, otherwise use the default retrieve class from the controller
        $args['RETRIEVER_CLASS'] = isset($args['RETRIEVER_CLASS']) ? $args['RETRIEVER_CLASS'] : $this->DEFAULT_RETRIEVER_CLASS;
        $args['CACHE_FOLDER'] = isset($args['CACHE_FOLDER']) ? $args['CACHE_FOLDER'] : get_class($this);
        if ($this->DEFAULT_PARSER_CLASS) {
            $args['DEFAULT_PARSER_CLASS'] = $this->DEFAULT_PARSER_CLASS;
        }
        
        //instantiate the retriever class and add it to the controller
        $retriever = DataRetriever::factory($args['RETRIEVER_CLASS'], $args);
        $this->setRetriever($retriever);
        $retriever->setDataModel($this);

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }
    }
    
    protected function getData() {
        return $this->retriever->getData();
    }
    
    /**
     * Public factory method. This is the designated way to instantiated data controllers. Takes a string
     * for the classname to load and an array of arguments. Subclasses should generally not override this
     * method, but instead override init() to provide initialization behavior
     * @param string $controllerClass the classname to instantiate
     * @param array $args an associative array of arguments that get passed to init()
     * @return DataModel a data model object
     */
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();
        Kurogo::log(LOG_DEBUG, "Initializing DataModel $controllerClass", "data");

        if (!class_exists($controllerClass)) {
            throw new KurogoConfigurationException("DataModel class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf DataModel) {
            throw new KurogoConfigurationException("$controllerClass is not a subclass of DataModel");
        }

        $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));

        //get global options from the site data_model section
        $args = array_merge(Kurogo::getOptionalSiteSection('data_model'), $args);
        $controller->init($args);

        return $controller;
    }

    public function getResponse() {
        return $this->retriever->getResponse();
    }
    
    /**
     * Interceptor. forward the method that not exist in this class to the retriever 
     */
    public function __call($method, $arguments) {
        if (is_callable(array($this->retriever, $method))) {
            return call_user_func_array(array($this->retriever, $method), $arguments);
        } else {
            throw new KurogoDataException("Call of unknown function '$method'.");
        }
    }
    
    public function getInitArg($type='') {
        return isset($this->initArgs[$type]) ? $this->initArgs[$type] : null;
    }
}

