<?php
/**
  * @package Directory
  */

/**
  * @package Directory
  */
class LDAPPerson extends Person {

  protected $dn;
  
  public function getDn() {
    return $this->dn;
  }

  public function getId() {
    $uid = $this->getFieldSingle('uid');
    return $uid ? $uid : $this->getDn();
  }
  
  private function getFieldSingle($field) {
    $values = $this->getField($field);
    if ($values) {
      return $values[0];
    }
    return NULL;
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

