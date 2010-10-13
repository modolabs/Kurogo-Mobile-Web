<?php

class HarvardPerson extends LDAPPerson
{

    function __construct($ldapEntry)
    {
        parent::__construct($ldapEntry);
        
        // Harvard: if full name shows up at beginning of office address, remove
        if ($address = $this->getFieldSingle('postaladdress')) {
            $nameParts = explode(' ', $this->getFullName());
            if ($address && $nameParts) {
                // lines in office address are literally delimited by a $ symbol
                $namePattern = '/^' . $nameParts[0] . '[\w\s\.]*' . end($nameParts) . '\$/';
                $address = preg_replace($namePattern, '', $address);
      
                $this->attributes['postaladdress'] = array($address);
            }
        }
    }
}
