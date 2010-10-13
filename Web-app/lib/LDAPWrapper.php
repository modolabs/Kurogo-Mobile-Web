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

/*
function queryForFirstLastName($firstName, $lastName) {
    DEbug::die_here(func_get_args());
    return "(&(givenName=".LDAPUtils::ldapEscape($firstName)."*)(sn=".LDAPUtils::ldapEscape($lastName)."*))";
}
*/

/*
function queryForNames($names)
{
	$nameCount = count($names);

	if ($nameCount == 1) {
		// Just one name -- could be given or surname.
		return "(|(givenName=".LDAPUtils::ldapEscape($names[0])."*)(sn=".LDAPUtils::ldapEscape($names[0])."*))";
	}
	elseif ($nameCount == 2) {
		// Two names, assume one is given, one is surname.  Assume that they 
		// start the names correctly, but we wildcard the end.
		return queryForFirstLastName($names[0], $names[1]) . 
		       queryForFirstLastName($names[1], $names[0]);
	}
	elseif ($nameCount > 2) {
	    $queries = array();
	    
	    // Either the first word is the first name, or it's a title and the 
	    // second word is the first name.
	    $possibleFirstNames = array($names[0], $names[1]);
	    
	    // Either the last word is the last name, or the last two words taken
	    // together are the last name.
	    $possibleLastNames = array($names[$nameCount - 1],
	                               $names[$nameCount - 2] . " " . $names[$nameCount - 1]);

	    foreach ($possibleFirstNames as $i => $firstName) {
	        foreach ($possibleLastNames as $j => $lastName) {
	            $queries[] = queryForFirstLastName($firstName, $lastName);
	        }
	    }

		// Kitchen sink -- just string them all together with wildcards
		// and hope that it's a match on the common name.
		$queries[] = "(cn=" . implode("*", array_map(array('LDAPUtils',"ldapEscape"), $names)) . "*)";
		
		return "(|" . implode($queries) . ")";
	}
}
*/

/*
function queryForEmail($words)
{
	return "(mail=" . implode("*", array_map(array('LDAPUtils',"ldapEscape"), $words)) . "*)";
}
*/

/*
function buildNameAndEmailLDAPQuery($search)
{
	$words = preg_split("/\s+/", $search);
	$query = "(|" . self::queryForNames($words) . self::queryForEmail($words) . ")";

	// error_log("SAFE SEARCH: " . $safeSearch);
	// error_log("QUERY: " . $query);

	// Put the gathered clauses in the person search template.
    $searchFilter = str_replace("%s", $query, NAME_SEARCH_FILTER);
    return($searchFilter);	
}
*/

/*
function buildEmailLDAPQuery($search) {
    DEbug::die_here(func_get_args());
  $emailFilter = str_replace("%s", LDAPUtils::ldapEscape($search), EMAIL_FILTER);
  return str_replace("%s", $emailFilter, EMAIL_SEARCH_FILTER);	
}
*/

/*
function buildTelephoneQuery($search) {
    DEbug::die_here(func_get_args());
}
*/

protected function generateErrorMessage($ldap_resource) {
   $error_code = ldap_errno($ldap_resource);
   $error_name = ldap_error($ldap_resource);
   Die("$error_name $error_code");
   $error_codes = array(
   	// LDAP error codes.
   	LDAP_SIZELIMIT_EXCEEDED => "There are more results than can be displayed. Showing the first 50.",
       	LDAP_PARTIAL_RESULTS => "There are more results than can be displayed. Showing the first 50.",
       	LDAP_INSUFFICIENT_ACCESS => "Too many results to display (more than 50). Please refine your search.",
       	LDAP_TIMELIMIT_EXCEEDED => "The directory service is not responding. Please try again later.",
    );
    if(isset($error_codes[$error_code])) {
        return $error_codes[$error_code];
    } else { // return a generic error message
        return "Your request cannot be processed at this time.";
    }
}

}

class LDAPUtils
{
    public static function unicode2utf8($match_array)
    {
        $c = hexdec($match_array[0]);
        if ($c < 0x80) {
            return chr($c);
        } else if ($c < 0x800) {
            return chr( 0xc0 | ($c >> 6) ).chr( 0x80 | ($c & 0x3f) );
        } else if ($c < 0x10000) {
            return chr( 0xe0 | ($c >> 12) ).chr( 0x80 | (($c >> 6) & 0x3f) ).chr( 0x80 | ($c & 0x3f) );
        } else if ($c < 0x200000) {
            return chr(0xf0 | ($c >> 18)).chr(0x80 | (($c >> 12) & 0x3f)).chr(0x80 | (($c >> 6) & 0x3f)).chr(0x80 | ($c & 0x3f));
        }
        return false;
    }
    
    public static function ldapDecode($ldap_str)
    {
        return preg_replace_callback("/0x(\d|[A-F]){4}/", array("LDAPUtils", "unicode2utf8"), $ldap_str);
    }

    /*
    // from http://php.net/manual/en/function.ldap-search.php
    function ldapEscape($str) 
    { 
        Debug::wp();
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
    */
}




// Scenarios:
// 
// A: 1 search term. Could be given name, surname, or email. Query for "hewitt":
//
//  (|
//	  (|(givenName=hewitt*)(sn=hewitt*))
//    (mail=hewitt*)
//  )
//
// B: 2 search terms. Names could be in either order. Query for "doug hall":
//
//	(|
//	  (&(givenName=doug*)(sn=hall*))
//	  (&(givenName=hall*)(sn=doug*))
//	  (mail=doug*hall*)
//	)
//
// C: 3 or more search terms. Query for "Prof Maria R. Garcia Castillo" (queries
//     this long can come from Courses, where names are entered freeform): 
//
//  (|
//    (|
//      (&(givenName=Prof*)(sn=Castillo*)) # First {...} Last
//      (&(givenName=Prof*)(sn=Garcia Castillo*)) # First {...} Last1 Last2
//      (&(givenName=Maria*)(sn=Castillo*)) # Title First {...} Last
//      (&(givenName=Maria*)(sn=Garcia Castillo*)) # Title First {...} Last1 Last2
//      (cn=Prof*Maria*R.*Garcia*Castillo*) # Try everything, sometimes gets nicknames
//    )
//    (mail=Prof*Maria*R.*Garcia*Castillo*)
//  )
//
// We've been having problems having too many matches returned, so we're 
// intentionally making this a little less forgiving:
//
// 1. It's assumed that anything you write comes at the beginning of a name or
//    email address.  So "ith" will not match against "Smith".
// 2. If there are more than two names written, we fall back to assuming an 
//    ordering of first, middle, then last name, and not trying out every 
//    possible combination.


