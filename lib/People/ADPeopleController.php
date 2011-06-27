<?php
/**
  * @package Directory
  */

/**
  * @package Directory
  */
class ADPeopleController extends LDAPPeopleController {

    protected function init($args) {
        $args = array_merge(
            array(
                'LDAP_USERID_FIELD'=>'samaccountname'
            ), $args
        );
                
        parent::init($args);
    }
}
