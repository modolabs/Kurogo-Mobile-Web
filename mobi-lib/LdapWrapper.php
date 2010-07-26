<?php

require_once "lib_constants.inc";

// search string templates
define("SEARCH_TIMELIMIT", 30);
define("READ_TIMELIMIT", 30);
define("TELEPHONE_FILTER", "(telephonenumber=*%s*)");
define("TELEPHONE_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("EMAIL_FILTER", "(mail=*%s*)");
define("EMAIL_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("NAME_SINGLE_CHARACTER_FILTER", "(|(cn=%s*)(cn=* %s*)(cn=*-%s*))");
define("NAME_MULTI_CHARACTER_FILTER", "(|(cn=*%s*)(mail=*%s*))");
define("NAME_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("UID_FILTER", "(uid=%s)");
define("UID_SEARCH_FILTER", "(&(objectClass=person)%s)");

class LdapPerson {

  private $uid;
  private $attributes = array();

  // set values to TRUE for fields that should be returned.
  // the uid field is mandatory and not listed here.
  private static $ldapFields = array(
    'sn'                         => TRUE,
    'givenname'                  => TRUE,
    'cn'                         => TRUE,
    'title'                      => TRUE,
    'ou'                         => TRUE,
    'edupersonaffiliation'       => FALSE,
    'street'                     => FALSE,
    'homephone'                  => FALSE,
    'roomnumber'                 => TRUE,
    'initials'                   => TRUE,
    'telephonenumber'            => TRUE,
    'facsimiletelephonenumber'   => TRUE,
    'mail'                       => TRUE,
    'physicaldeliveryofficename' => TRUE,
    );

  public static function setLdapField($field, $shouldReturn) {
    self::$ldapFields[$field] = $shouldReturn;
  }

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
    foreach (self::$ldapFields as $field => $shouldReturn) {
      if ($shouldReturn && $values = self::getValues($field, $ldapEntry)) {
        $this->attributes[$field] = $values;
      }
    }

    return $this;
  }

  protected static function getValues($ldapKey, $ldapEntry) {
    if (array_key_exists($ldapKey, $ldapEntry)) {
      $result = $ldapEntry[$ldapKey];
      if ($result !== NULL) {
        unset($result["count"]);
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
        // populate placeholders in search template strings
        $emailFilter = str_replace("%s", $searchString, EMAIL_FILTER);
        $this->query = str_replace("%s", $emailFilter, EMAIL_SEARCH_FILTER);
        $success = TRUE;
      }

    } elseif (preg_match('/[A-Za-z]/', $searchString)) { // assume search by name

      $nameFilter = "";
      foreach(preg_split("/\s+/", $searchString) as $word) {
        if ($word != "") {
          if (strlen($word) == 1) {
            $filter = NAME_SINGLE_CHARACTER_FILTER;
          } else {
            $filter = NAME_MULTI_CHARACTER_FILTER;
          }
          $nameFilter .= str_replace("%s", $word, $filter);
        }
      }

      if ($nameFilter != "") {
        $this->query = str_replace("%s", $nameFilter, NAME_SEARCH_FILTER);
        $success = TRUE;
      } else {
        $this->errorMsg = "Invalid name query";
      }

    } elseif (preg_match('/[0-9]+/', $searchString)) { // assume search by telephone number

      $search = str_replace(array('(', ')', ' ', '.', '-'), '', $search);
      if ($search) {
        $telephoneFilter = str_replace("%s", $searchString, TELEPHONE_FILTER);
        $this->query = str_replace("%s", $telephoneFilter, TELEPHONE_SEARCH_FILTER);
        $success = TRUE;
      } else {
        $this->errorMsg = "Invalid telephone number";
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
      $this->errorMsg = "Search timed out";
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
      // TODO: find out if this string pattern was MIT-specific

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

