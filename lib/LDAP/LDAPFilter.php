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
class LDAPFilter
{
    const FILTER_OPTION_WILDCARD_TRAILING=1;
    const FILTER_OPTION_WILDCARD_LEADING=2;
    const FILTER_OPTION_NO_ESCAPE=4;
    const FILTER_OPTION_WILDCARD_SURROUND=8;
    const FILTER_OPTION_CUSTOM=16;
    protected $field;
    protected $value;
    protected $strval;
    protected $options;

    public function __construct($field, $value, $options=0)
    {
        $this->options = intval($options);

    	// when using custom option, the $field option is 
    	if ($this->options & self::FILTER_OPTION_CUSTOM) {
    		$this->strval = $field;
    	} else {
			$this->field = $field;
			$this->value = $value;
			$this->strval = $this->strval();
		}
    }
    
    public static function ldapEscape($str) 
    { 
        // see RFC2254 
        // http://msdn.microsoft.com/en-us/library/ms675768(VS.85).aspx 
        // http://www-03.ibm.com/systems/i/software/ldap/underdn.html        
        
        $metaChars = array('*', '(', ')', '\\', chr(0));
        $quotedMetaChars = array(); 
        foreach ($metaChars as $key => $value) {
            $quotedMetaChars[$key] = '\\'.str_pad(dechex(ord($value)), 2, '0'); 
        }
        $str = str_replace($metaChars, $quotedMetaChars, $str); 
        return ($str); 
    }

    public function __toString() {
    	return $this->strval;
    }
    
    protected function strval() 
    {
        if ($this->options & self::FILTER_OPTION_NO_ESCAPE) {
            $value = $this->value;
        } else {
            $value = self::ldapEscape($this->value);
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_LEADING) {
            $value  = '*' . $value;
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_TRAILING) {
            $value  .= '*';
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_SURROUND) {
            $value  = '*' . $value . "*";
        }
    
        return sprintf("(%s=%s)", $this->field, $value);
    }
}
