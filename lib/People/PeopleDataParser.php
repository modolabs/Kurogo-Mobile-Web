<?php

abstract class PeopleDataParser extends DataParser
{
    protected $personClass = 'Person';
    
    public function setPersonClass($className) {
    	if ($className) {
    		if (!class_exists($className)) {
    			throw new KurogoConfigurationException("Cannot load class $className");
    		}

            $class = new ReflectionClass($className); 
            if (!$class->isSubclassOf('Person')) {
                throw new KurogoConfigurationException("$className is not a subclass of Person");
            }
			$this->personClass = $className;
		}
    }
    
    public function init($args) {
        parent::init($args);
        if (isset($args['PERSON_CLASS'])) {
            $this->setPersonClass($args['PERSON_CLASS']);
        }
    }
}

