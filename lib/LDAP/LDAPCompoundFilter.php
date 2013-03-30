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
class LDAPCompoundFilter extends LDAPFilter
{
    const JOIN_TYPE_AND = '&';
    const JOIN_TYPE_OR = '|';
    const JOIN_TYPE_NOT = '!';
    protected $joinType;
    protected $filters=array();
    
    public function __construct($joinType, $filter1, $filter2=null) {

        switch ($joinType)
        {
            case self::JOIN_TYPE_AND:
            case self::JOIN_TYPE_OR:
                $this->joinType = $joinType;
                break;
            case self::JOIN_TYPE_NOT:
                # Special case for not. Set data and return.
                if(!($filter1 instanceOf LDAPFilter)){
                    throw new KurogoConfigurationException("Arguement 2 must be an LDAPFilter when using join type NOT.");
                }
                $this->joinType = $joinType;
                $this->filters = array($filter1);
                return;
                break;
            default:
                throw new KurogoConfigurationException("Invalid join type $joinType");                
        }
    
        for ($i = 1; $i < func_num_args(); $i++) {
            $filter = func_get_arg($i);
            if ($filter instanceOF LDAPFilter) { 
                $this->filters[] = $filter;
            } elseif (is_array($filter)) {
                foreach ($filter as $_filter) {
                    if (!($_filter instanceOf LDAPFilter)) {
                        throw new KurogoConfigurationException("Invalid filter for in array");
                    }
                }
                $this->filters = $filter;
            } else {
                throw new KurogoConfigurationException("Invalid filter for argument $i");
            }
        }
    
        if (count($this->filters)<2) {
            throw new KurogoConfigurationException(sprintf("Only %d filters found (2 minimum)", count($filters)));
        }
    
    }
    
    public function addFilter(LDAPFilter $filter) {
        $this->filters[] = $filter;
    }
    
    function __toString() {

        $stringValue = sprintf("(%s%s)", $this->joinType, implode("", $this->filters));
        return $stringValue;
    }
}
