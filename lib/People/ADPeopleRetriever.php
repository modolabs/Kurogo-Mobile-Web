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

/**
  * @package People
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
