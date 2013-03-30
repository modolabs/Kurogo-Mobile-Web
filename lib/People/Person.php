<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class Person extends KurogoDataObject
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

    public function setField($field, $data){
        $this->attributes[$field] = $data;
    }

    public function setFieldArray($field, $data){
        if(!isset($this->attributes[$field])){
            $this->attributes[$field] = array();
        }
        $this->attributes[$field][] = $data;
    }
}
