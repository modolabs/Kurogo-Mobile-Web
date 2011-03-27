<?php
/**
  * @package Directory
  */
  
/**
  * @package Directory
  */
abstract class PeopleController
{
    abstract public function lookupUser($id);
    abstract public function search($searchTerms);
    abstract public function getError();
    abstract public function setAttributes($attributes);
    
    public static function getPeopleControllers() {
        return array(
            ''=>'-',
            'LDAPPeopleController'=>'LDAP',
            'GoogleAppsPeopleController'=>'Google Apps'
        );
    }

    protected $debugMode=false;
    protected $personClass = 'Person';
    
    public function debugInfo()
    {
        return '';
    }

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

        if (isset($args['PERSON_CLASS'])) {
            $this->setPersonClass($args['PERSON_CLASS']);
        }
    }

    public static function factory($controllerClass, $args)
    {
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;

        if (!$controller instanceOf PeopleController) {
            throw new Exception("$controller class is not a subclass of PeopleController");
        }
        
        $controller->init($args);
        
        return $controller;
    }
}

abstract class Person
{
    protected $attributes = array();
    abstract public function getId();
    
    public function getField($field) {
    if (array_key_exists($field, $this->attributes)) {
      return $this->attributes[$field];
    }
    return array();
  }
}

