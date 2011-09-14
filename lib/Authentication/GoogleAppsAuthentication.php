<?php
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
