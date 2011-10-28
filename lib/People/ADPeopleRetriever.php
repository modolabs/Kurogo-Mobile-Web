<?php
/**
  * @package Directory
  */

/**
  * @package Directory
  */
class ADPeopleRetriever extends LDAPPeopleRetriever {

    protected function init($args) {
        $args = array_merge(
            array(
                'LDAP_USERID_FIELD'=>'samaccountname'
            ), $args
        );
                
        parent::init($args);
    }
}
