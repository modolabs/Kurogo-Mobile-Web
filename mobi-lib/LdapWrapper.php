<?php

require_once "lib_constants.inc";
require_once "LdapUtilities.php";

class LdapPerson {

  private $uid;
  private $fullname = '';
  private $attributes = array();

  // TODO: put this whitelist in static or config
  // on a per-institution basis
  private static $ldapWhitelist = array(
    'sn',
    'givenname',
    'cn',
    'title',
    'ou',
    'roomnumber',
    'initials',
    'telephonenumber',
    'facsimiletelephonenumber',
    'mail',
    'postaladdress',
    //'physicaldeliveryofficename',
    //'edupersonaffiliation',
    //'street',
    //'homephone',
    );

  public function getId() {
    return $this->uid;
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
    // skip LDAP entries with no useful information
    if (!self::getValues("sn", $ldapEntry)) {
      return FALSE;
    }

    // get uid
    if ($uidArr = self::getValues("uid", $ldapEntry)) {
      $this->uid = $uidArr[0];
    } else {
      $this->uid = $ldapEntry["dn"];
    }

    // get remaining attributes
    foreach (self::$ldapWhitelist as $field) {

      $value = self::getValues($field, $ldapEntry);
      if ($field == 'cn')
	      $this->fullname = $value[0];
      
      $this->attributes[$field] = $value;
    }

    // Harvard: if full name shows up at beginning of office address, remove
    $address = count($this->attributes['postaladdress']) ? 
        $this->attributes['postaladdress'][0] : NULL;
    $nameParts = explode(' ', $this->fullname);
    if ($address && $nameParts) {
      // lines in office address are literally delimited by a $ symbol
      $namePattern = '/^' . $nameParts[0] . '[\w\s\.]*' . end($nameParts) . '\$/';
      $address = preg_replace($namePattern, '', $address);
      // Also replace '&amp;' with '&'. Both mobile web and the API need that symbol in the '&' form.
      //$address = preg_replace('/\&amp\;/', '&', $address);
      
      $this->attributes['postaladdress'] = array($address);
    }

    return $this;
  }

  protected static function getValues($ldapKey, $ldapEntry) {
    if (array_key_exists($ldapKey, $ldapEntry)) {
      $result = $ldapEntry[$ldapKey];
      // Sometimes LDAP returns duplicate values for one field, but we don't want to send 
      // duplicate values back to the client.
      //
      // Note: array_unique does not re-arrange the indexing. So the unique values must be manually copied over
      $new_result = array_unique($result);
      $result = array(); // critical to re-declare the array here.
      unset($new_result["count"]);
      $unique_index = 0;
      foreach($new_result as $index => $val) {
            $result[$unique_index] = $val;
            $unique_index++;
      }
      
      if ($result !== NULL) {
        foreach ($result as $index => $value) {            
          $result[$index] = ldap_decode(htmlentities($value));
        }
        return $result;
      }
    }

    return array();
  }

}

class LdapWrapper {

  protected $query;
  protected $errorMsg;

  public function buildQuery($searchString) {

    $success = FALSE;

    if (strpos($searchString, "@") != FALSE) { // assume email search

      if (strpos($searchString, " ") != FALSE) {
        $this->errorMsg = "Invalid email address";
      } else {
	    // This function is defined in LdapUtilities.	  
        $emailFilter = buildEmailLDAPQuery($searchString);

        if ($emailFilter == "") {
          $this->errorMsg = "Invalid email query";
        } else {
		  $this->query = $emailFilter;
		  $success = TRUE;
	    }
      }

    } elseif (preg_match('/[A-Za-z]+/', $searchString)) { // assume search by name
	  // This function is defined in LdapUtilities.	  
      $nameFilter = buildNameAndEmailLDAPQuery($searchString);

      if ($nameFilter == "") {
        $this->errorMsg = "Invalid name query";
      } else {
		$this->query = $nameFilter;
		$success = TRUE;
	  }

    } elseif (preg_match('/[0-9]+/', $searchString)) { // assume search by telephone number
      $telephoneFilter = buildTelephoneQuery($search);

      if ($telephoneFilter == "") {
        $this->errorMsg = "Invalid name query";
      } else {
		$this->query = $telephoneFilter;
		$success = TRUE;
	  }

    } else {
      $this->errorMsg = "Invalid query";
    }
    return $success;
  }

  public function getError() {
    return $this->errorMsg;
  }

  /* return results, or FALSE on error.
   */
  public function doQuery() {

    $ds = ldap_connect(LDAP_SERVER);
    if (!$ds) {
      $this->errorMsg = "Could not connect to LDAP server";
      return FALSE;
    }

    // suppress warnings on non-dev servers
    // about searches that go over the result limit
    if (!MOBILE_DEV_SERVER) {
      $error_reporting = ini_get('error_reporting');
      error_reporting($error_reporting & ~E_WARNING);
    }

    $sr = ldap_search($ds, LDAP_PATH, $this->query, array(), 0, 0, SEARCH_TIMELIMIT);
    if (!$sr) {
        if($ds) {
            $this->errorMsg = generateErrorMessage($ds);
        }
      return FALSE;
    }

    if (!MOBILE_DEV_SERVER) {
      error_reporting($error_reporting);
    }

    $entries = ldap_get_entries($ds, $sr);
    if (!$entries) {
      $this->errorMsg = "Could not get result entries";
      return FALSE;
    }

    $results = array();
    for ($i = 0; $i < $entries["count"]; $i++) {
      if ($person = new LdapPerson($entries[$i])) {
        $results[] = $person;
      }
    }

    return $results;
  }

  /* returns a person object on success
   * FALSE on failure
   */
  public function lookupUser($id) {

    if (strstr($id, '=')) { 
      // assume we're looking up person by "dn" (distinct ldap name)

      $ds = ldap_connect(LDAP_SERVER);
      if (!$ds) {
        $this->errorMsg = "Could not connect to LDAP server";
        return FALSE;
      }

      // get all attributes of the person identified by $id
      $sr = ldap_read($ds, $id, "(objectclass=*)", array(), 0, 0, READ_TIMELIMIT);
      if (!$sr) {
        $this->errorMsg = "Search timed out";
        return FALSE;
      }

      $entries = ldap_get_entries($ds, $sr);
      if (!$entries) {
        $this->errorMsg = "Could not get result entries";
        return FALSE;
      }

      return new LdapPerson($entries[0]);

    } else {

      $uidFilter = str_replace("%s", $id, UID_FILTER);
      $this->query = str_replace("%s", $uidFilter, UID_SEARCH_FILTER);
      if (!$result = $this->doQuery()) {
        return FALSE;
      } else {
        return $result[0];
      }
    }

  }

}

function compare_people($person1, $person2) {
  if ($person1['surname'] != $person2['surname']) {
    return ($person1['surname'] < $person2['surname']) ? -1 : 1;
  } elseif ($person1['givenname'] == $person2['givenname']) {
    return 0;
  } else {
    return ($person1['givenname'] < $person2['givenname']) ? -1 : 1;
  }
}

function ldap_decode($ldap_str)
{
    return preg_replace_callback("/0x(\d|[A-F]){4}/", "unicode2utf8", $ldap_str);
}

function unicode2utf8($match_array)
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

