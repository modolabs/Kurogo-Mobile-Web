<?php

abstract class PeopleController
{
    protected $host;
    protected $debugMode=false;
    protected $personClass;

    public function host()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }
    
    public function setPersonClass($className)
    {
    	if ($className) {
    		if (!class_exists($className)) {
    			throw new Exception("Cannot load class $className");
    		}
			$this->personClass = $className;
		}
    }
    
    protected function init($args)
    {
        if (isset($args['HOST'])) {
            $this->setHost($args['HOST']);
        }

        if (isset($args['PERSON_CLASS'])) {
            $this->setPersonClass($args['PERSON_CLASS']);
        }
    }
    
    public static function factory($args)
    {
        $controllerClass = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        $controller->init($args);
        
        return $controller;
    }
}

