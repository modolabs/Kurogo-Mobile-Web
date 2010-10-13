<?php

// common ldap error codes
define("LDAP_TIMELIMIT_EXCEEDED", 0x03);
define("LDAP_SIZELIMIT_EXCEEDED", 0x04);
define("LDAP_PARTIAL_RESULTS", 0x09);
define("LDAP_INSUFFICIENT_ACCESS", 0x32);

class LDAPPerson {

  protected $dn;
  protected $attributes = array();
  
  public function getDn() {
    return $this->dn;
  }

  public function getId() {
    $uid = $this->getFieldSingle('uid');
    return $uid ? $uid : $this->getDn();
  }
  
  public function getFullName()
  {
        if ($this->getFieldSingle('cn')) {
            return $this->getFieldSingle('cn');
        } 
        
        return trim(sprintf("%s %s", $this->getFieldSingle('givenName'), $this->getFieldSingle('sn')));
  }

  public function getFieldSingle($field) {
    $values = $this->getField($field);
    if ($values) {
      return $values[0];
    }
    return NULL;
  }

  public function getField($field) {
    if (array_key_exists($field, $this->attributes)) {
      return $this->attributes[$field];
    }
    return array();
  }
  
  public function __construct($ldapEntry) {
    $this->dn = $ldapEntry['dn'];
    $this->attributes = array();

    for ($i=0; $i<$ldapEntry['count']; $i++) {
        $attribute = $ldapEntry[$i];
        $count = $ldapEntry[$attribute]['count'];
        $this->attributes[$attribute] = array();
        for ($j=0; $j<$count; $j++) {
            if (!in_array($ldapEntry[$attribute][$j], $this->attributes[$attribute])) {
                $this->attributes[$attribute][] = $ldapEntry[$attribute][$j];
            }
        }
    }
  }
}

class LDAPCompoundFilter extends LDAPFilter
{
    const JOIN_TYPE_AND='&';
    const JOIN_TYPE_OR='|';
    protected $joinType;
    protected $filters=array();
    
    public function __construct($joinType, LDAPFilter $filter1, LDAPFilter $filter2)
    {
        switch ($joinType)
        {
            case self::JOIN_TYPE_AND:
            case self::JOIN_TYPE_OR:
                $this->joinType = $joinType;
                break;
            default:
                throw new Exception("Invalid join type $joinType");                
        }
        
        for ($i=1; $i < func_num_args(); $i++) {
            $filter = func_get_arg($i);
            if (is_a($filter, 'LDAPFilter')) { 
                $this->filters[] = $filter;
            } else {
                throw new Exception("Invalid filter for argumnent $i");
            }
        }
        
        if (count($this->filters)<2) {
            throw new Exception(sprintf("Only %d filters found (2 minimum)", count($filters)));
        }
        
    }
    
    function __toString()
    {
        $stringValue = sprintf("(%s%s)", $this->joinType, implode("", $this->filters));
        return $stringValue;
    }
}

class LDAPFilter
{
    const FILTER_OPTION_WILDCARD_TRAILING=1;
    const FILTER_OPTION_WILDCARD_LEADING=2;
    protected $field;
    protected $value;
    protected $options;
    
    public function __construct($field, $value, $options=0)
    {
        $this->field = $field;
        $this->value = $value;
        $this->options = intval($options);
    }

    protected function ldapEscape($str) 
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
    
    public function __toString()
    {
        $value = self::ldapEscape($this->value);
        
        if ($this->options & self::FILTER_OPTION_WILDCARD_LEADING) {
            $value  = '*' . $value;
        }

        if ($this->options & self::FILTER_OPTION_WILDCARD_TRAILING) {
            $value  .= '*';
        }
        
        return sprintf("(%s=%s)", $this->field, $value);
    }
}

class LDAPWrapper {
  protected $personClass = 'LDAPPerson';
  protected $filter;
  protected $errorNo;
  protected $errorMsg;
  
  public function query($searchString)
  {
    $this->buildQuery($searchString);
    return $this->doQuery();
  }
  
  public function getLDAPField($_field)
  {
       if ($field = $GLOBALS['siteConfig']->getVar(sprintf('LDAP_%s_FIELD', strtoupper($_field)))) {
            return $field;
       }
       
       return $_field;
  }
  
  private function getLDAPSearchAttributes()
  {
        $attributes = $GLOBALS['siteConfig']->getVar('LDAP_RETURN_FIELDS');
        return $attributes ? $attributes : array();
  }

  public function buildQuery($searchString) {

    $this->filter = $filter = false;
    $this->errorNo = $this->errorMsg = null;
    
    $objectClassQuery = new LDAPFilter('objectClass', 'person');

    if (Validator::isValidEmail($searchString)) {
    
        $filter = new LDAPFilter($this->getLDAPField('mail'), $searchString);
    
    } elseif (Validator::isValidPhone($searchString, $phone_bits)) {
        array_shift($phone_bits);
        $searchString = implode("", $phone_bits); // remove any separators. This might be an issue for people with formatted numbers in their directory
        $filter = new LDAPFilter($this->getLDAPField('phone'), $searchString);


    } elseif (preg_match('/[A-Za-z]+/', $searchString)) { // assume search by name

        $givenNameFilter = new LDAPFilter($this->getLDAPField('givenName'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
        $snFilter = new LDAPFilter($this->getLDAPField('sn'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
        $mailFilter = new LDAPFilter($this->getLDAPField('mail'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
        
        $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_OR, $givenNameFilter, $snFilter, $mailFilter);
        
    } else {
      $this->errorMsg = "Invalid query";
    }
    
    if ($filter) {
        $this->filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_AND, $objectClassQuery, $filter);
    }
    
    return $this->filter;
  }

  public function getErrorNo() {
    return $this->errorNo;
  }

  public function getError() {
    return $this->errorMsg;
  }

  /* return results, or FALSE on error.
   */
  public function doQuery() {
  
    if (!$this->filter) {
        return FALSE;
    }
    
    $ds = ldap_connect($GLOBALS['siteConfig']->getVar('LDAP_SERVER'));
    if (!$ds) {
      $this->errorMsg = "Could not connect to LDAP server";
      return FALSE;
    }

    // suppress warnings on non-dev servers
    // about searches that go over the result limit
    if (!$GLOBALS['siteConfig']->getVar('LDAP_DEBUG')) {
      $error_reporting = ini_get('error_reporting');
      error_reporting($error_reporting & ~E_WARNING);
    }
    
    $filter = strval($this->filter);
    $sr = ldap_search($ds, $GLOBALS['siteConfig']->getVar('LDAP_PATH'), 
      $filter, $this->getLDAPSearchAttributes(), 0, 0, 
      $GLOBALS['siteConfig']->getVar('LDAP_SEARCH_TIMELIMIT'));
    if (!$sr) {
        if($ds) {
            $this->errorMsg = self::generateErrorMessage($ds);
        }
      return FALSE;
    }
    
    if ($this->errorNo = ldap_errno($ds)) {
        $this->errorMsg = $this->generateErrorMessage($ds);
    }
    
    if (!$GLOBALS['siteConfig']->getVar('LDAP_DEBUG')) {
      error_reporting($error_reporting);
    }

    $entries = ldap_get_entries($ds, $sr);
    
    if (!$entries) {
      $this->errorMsg = "Could not get result entries";
      return FALSE;
    }
    
    $results = array();
    for ($i = 0; $i < $entries["count"]; $i++) {
      if ($person = new $this->personClass($entries[$i])) {
        $results[] = $person;
      }
    }

    return $results;
    
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

  /* returns a person object on success
   * FALSE on failure
   */
  public function lookupUser($id) {
    if (strstr($id, '=')) { 
      // assume we're looking up person by "dn" (distinct ldap name)

      $ds = ldap_connect($GLOBALS['siteConfig']->getVar('LDAP_SERVER'));
      if (!$ds) {
        $this->errorMsg = "Could not connect to LDAP server";
        return FALSE;
      }

      // get all attributes of the person identified by $id
      $sr = ldap_read($ds, $id, "(objectclass=*)", $this->getLDAPSearchAttributes(), 0, 0, 
        $GLOBALS['siteConfig']->getVar('LDAP_READ_TIMELIMIT'));
      if (!$sr) {
        $this->errorMsg = "Search timed out";
        return FALSE;
      }

      $entries = ldap_get_entries($ds, $sr);
      if (!$entries) {
        $this->errorMsg = "Could not get result entries";
        return FALSE;
      }

      return new $this->personClass($entries[0]);

    } else {

      $this->filter = new LDAPFilter($this->getLDAPField('uid'), $id);
      if (!$result = $this->doQuery()) {
        return FALSE;
      } else {
        return $result[0];
      }
    }

  }

protected function generateErrorMessage($ldap_resource) {
   $error_code = ldap_errno($ldap_resource);
   $error_name = ldap_error($ldap_resource);
   $error_codes = array(
   	// LDAP error codes.
   	    LDAP_SIZELIMIT_EXCEEDED => "There are more results than can be displayed.",
       	LDAP_PARTIAL_RESULTS => "There are more results than can be displayed.",
/*       	LDAP_INSUFFICIENT_ACCESS => "Too many results to display (more than 50). Please refine your search.", */
       	LDAP_TIMELIMIT_EXCEEDED => "The directory service is not responding. Please try again later.",
    );
    if(isset($error_codes[$error_code])) {
        return $error_codes[$error_code];
    } else { // return a generic error message
        return "Your request cannot be processed at this time.";
    }
}

}
