<?php

abstract class Person implements KurogoObject
{
    protected $attributes = array();
    abstract public function getName();
    
    public function getTitle() {
        return $this->getName();
    }
    
    // Some feeds can return images as data (e.g. LDAP)
    // If your subclass does, implement these
    public function getPhotoMIMEType() {
        return '';
    }
    public function getPhotoData() {
        return null;
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
