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
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAppsAuthentication extends GoogleAuthentication
{
    protected $OAuthProviderClass='GoogleAppsOAuthProvider';
    protected $userClass='GoogleAppsUser';
    protected $domain;

    public function getDomain() {
        return $this->domain;
    }

    public function init($args) {
        parent::init($args);
        $args = is_array($args) ? $args : array();

        if (!isset($args['GOOGLEAPPS_DOMAIN']) || strlen($args['GOOGLEAPPS_DOMAIN'])==0) {
            throw new KurogoConfigurationException("Google Apps Domain not set");
        }

        $this->domain = $args['GOOGLEAPPS_DOMAIN'];
    }
}

/**
  * @package Authentication
  */
class GoogleAppsUser extends GoogleUser
{
    public function getDomain() {
        return $this->AuthenticationAuthority->getDomain();
    }
}
