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
