<?php
/**
  * @package People
  */
  
/**
  * @package People
  */
includePackage('DataModel');
class PeopleDataModel extends ItemListDataModel
{
    protected $RETRIEVER_INTERFACE = 'PeopleRetriever';
    protected $DEFAULT_RETRIEVER_CLASS = 'LDAPPeopleRetriever';
    protected $personClass = 'Person';
    protected $capabilities=0;
    protected $attributes=array();

    public static function getPeopleRetrievers() {
        return array(
            ''=>'-',
            'LDAPPeopleRetriever'=>'LDAP',
            'ADPeopleRetriever'=>'Active Directory',
            'DatabasePeopleRetriever'=>'Database'
        );
    }

    public function debugInfo() {
        return '';
    }

    public function setAttributes($attribs) {
        if (is_array($attribs)) {
            $this->attributes =$attribs;
        } elseif ($attribs) {
            throw new KurogoException('Invalid attributes');
        } else {
            $this->attributes = array();
        }
        
        $this->retriever->setAttributes($this->attributes);
    }

    public function getCapabilities() {
        return $this->capabilities;
    }

}

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

abstract class Person implements KurogoObject
{
    protected $attributes = array();
    abstract public function getName();
    
    public function getTitle() {
        return $this->getName();
    }
        
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search':
                    return  (stripos($this->getName(), $value)!==FALSE);
                    break;
            }
        }
        
        return true;
    }

    public function getField($field) {
        if (array_key_exists($field, $this->attributes)) {
          return $this->attributes[$field];
        }
        return NULL;
    }
}

interface PeopleRetriever extends SearchDataRetriever
{
    public function getUser($id);
    public function setAttributes($attributes);
}
