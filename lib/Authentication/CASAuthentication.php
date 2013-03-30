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
 * An authentication method for the Central Authentication Service (CAS) http://www.jasig.org/cas
 *
 * @package Authentication
 */
class CASAuthentication
    extends AuthenticationAuthority
{
    /**
     * Class for user objects. Most subclasses will override this
     * @var string
     */
    protected $userClass='CASUser';
    
    /** 
      * Class for group objects. Most subclasses will override this
      * @var string
      */
    protected $groupClass='CASUserGroup';

    /**
     * Initializes the authority objects based on an associative array of arguments
     * @param array $args an associate array of arguments. The argument list is dependent on the authority
     *
     * General - Required keys:
     *   TITLE => The human readable title of the AuthorityImage
     *   INDEX => The tag used to identify this authority @see AuthenticationAuthority::getAuthenticationAuthority
     *
     * General - Optional keys:
     *   LOGGEDIN_IMAGE_URL => a url to an image/badge that is placed next to the user name when logged in
     *
     * CAS - Required keys:
     *   CAS_PROTOCOL => The protocol to use. Should be equivalent to one of the phpCAS constants, e.g. "2.0":
     *                   CAS_VERSION_1_0 => '1.0', CAS_VERSION_2_0 => '2.0', SAML_VERSION_1_1 => 'S1'
     *   CAS_HOST => The host name of the CAS server, e.g. "cas.example.edu"
     *   CAS_PORT => The port the CAS server is listening on, e.g. "443"
     *   CAS_PATH => The path of the CAS application, e.g. "/cas/"
     *   CAS_CA_CERT => The filesystem path to a CA certificate that will be used to validate the authenticity
     *                  of the CAS server, e.g. "/etc/tls/pki/certs/my_ca_cert.crt". If empty, no certificate
     *                  validation will be performed (not recommended for production).
     *
     * CAS - Optional keys:
     *   ATTRA_EMAIL => Attribute name for the user's email adress, e.g. "email". This only applies if your 
     *                  CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
     *   ATTRA_FIRST_NAME => Attribute name for the user's first name, e.g. "givename". This only applies if your 
     *                       CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
     *   ATTRA_LAST_NAME => Attribute name for the user's last name, e.g. "surname". This only applies if your 
     *                      CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
     *   ATTRA_FULL_NAME => Attribute name for the user's full name, e.g. "displayname". This only applies if your 
     *                      CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
     *   ATTRA_MEMBER_OF => Attribute name for the user's groups, e.g. "memberof". This only applies if your 
     *                      CAS server returns attributes in a SAML-1.1 or CAS-2.0 response.
     *
     * NOTE: Any subclass MUST call parent::init($args) to ensure proper operation
     *
     */
    public function init($args)
    {
        parent::init($args);
    
        // include the PHPCAS library
        if (empty($args['CAS_PHPCAS_PATH']))
            require_once('CAS.php');
        else
            require_once($args['CAS_PHPCAS_PATH'].'/CAS.php');
    
        if (!empty($args['CAS_DEBUG_LOG']))
            phpCAS::setDebug($args['CAS_DEBUG_LOG']);
        
        if (empty($args['CAS_PROTOCOL']))
            throw new KurogoConfigurationException('CAS_PROTOCOL value not set for ' . $this->AuthorityTitle);
    
        if (empty($args['CAS_HOST']))
            throw new KurogoConfigurationException('CAS_HOST value not set for ' . $this->AuthorityTitle);
    
        if (empty($args['CAS_PORT']))
            throw new KurogoConfigurationException('CAS_PORT value not set for ' . $this->AuthorityTitle);
    
        if (empty($args['CAS_PATH']))
            throw new KurogoConfigurationException('CAS_PATH value not set for ' . $this->AuthorityTitle);
        
        if (empty($args['CAS_PROXY_INIT'])) {
            phpCAS::client($args['CAS_PROTOCOL'], $args['CAS_HOST'], intval($args['CAS_PORT']), $args['CAS_PATH'], false);
        } else {
            phpCAS::proxy($args['CAS_PROTOCOL'], $args['CAS_HOST'], intval($args['CAS_PORT']), $args['CAS_PATH'], false);
            
            if (!empty($args['CAS_PROXY_TICKET_PATH']) && !empty($args['CAS_PROXY_TICKET_DB_DSN']))
            	throw new KurogoConfigurationException('Only one of CAS_PROXY_TICKET_PATH or CAS_PROXY_TICKET_DB_DSN may be set for ' . $this->AuthorityTitle);
             
            if (!empty($args['CAS_PROXY_TICKET_PATH'])) {
                if (version_compare(PHPCAS_VERSION, '1.3', '>=')) {
                    phpCAS::setPGTStorageFile($args['CAS_PROXY_TICKET_PATH']);
                } else {
                    phpCAS::setPGTStorageFile('', $args['CAS_PROXY_TICKET_PATH']);
                }
            }
            
            if (!empty($args['CAS_PROXY_TICKET_DB_DSN'])) {
                $user = $pass = $table = $driver_opts = '';
                if (!empty($args['CAS_PROXY_TICKET_DB_USER']))
                    $user = $args['CAS_PROXY_TICKET_DB_USER'];
                if (!empty($args['CAS_PROXY_TICKET_DB_PASS']))
                    $pass = $args['CAS_PROXY_TICKET_DB_PASS'];
                if (!empty($args['CAS_PROXY_TICKET_DB_TABLE']))
                    $table = $args['CAS_PROXY_TICKET_DB_TABLE'];
                if (!empty($args['CAS_PROXY_TICKET_DB_DRIVER_OPTS']))
                    $driver_opts = $args['CAS_PROXY_TICKET_DB_DRIVER_OPTS'];
                phpCAS::setPGTStorageDb($args['CAS_PROXY_TICKET_DB_DSN'], $user, $pass, $table, $driver_opts);
            }
            
            if (!empty($args['CAS_PROXY_FIXED_CALLBACK_URL']))
                phpCAS::setFixedCallbackURL($args['CAS_PROXY_FIXED_CALLBACK_URL']);
        }
    
        if (empty($args['CAS_CA_CERT']))
            phpCAS::setNoCasServerValidation();
        else
            phpCAS::setCasServerCACert($args['CAS_CA_CERT']);
        
        // Record any attribute mapping configured.
        if (!empty($args['ATTRA_EMAIL']))
            CASUser::mapAttribute('Email', $args['ATTRA_EMAIL']);
        if (!empty($args['ATTRA_FIRST_NAME']))
            CASUser::mapAttribute('FirstName', $args['ATTRA_FIRST_NAME']);
        if (!empty($args['ATTRA_LAST_NAME']))
            CASUser::mapAttribute('LastName', $args['ATTRA_LAST_NAME']);
        if (!empty($args['ATTRA_FULL_NAME']))
            CASUser::mapAttribute('FullName', $args['ATTRA_FULL_NAME']);
        // Store an attribute for group membership if configured.
        if (!empty($args['ATTRA_MEMBER_OF']))
            CASUser::mapAttribute('MemberOf', $args['ATTRA_MEMBER_OF']);
    }
    
    /**
     * Login a user based on supplied credentials
     * @param string $login 
     * @param string $password
     * @param Module $module 
     * @see AuthenticationAuthority::reset()
     * 
     */
    public function login($login, $password, Session $session, $options)
    {
        phpCAS::forceAuthentication();
        $user = new $this->userClass($this);
        $session->login($user);
        return AUTH_OK;
    }
    
    /**
     * Attempts to authenticate the user using the included credentials
     * @param string $login the userid to login (this will be blank for OAUTH based authorities)
     * @param string $password password (this will be blank for OAUTH based authorities)
     * @param User &$user This object is passed by reference and should be set to the logged in user upon sucesssful login
     * @return int should return one of the AUTH_ constants
     */
    protected function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    /**
     * Retrieves a user object from this authority
     * @param string $login the userid to retrieve
     * @return User a valid user object or false if the user could not be found
     * @see User object
     */
    public function getUser($login)
    {
        // don't try if it's empty
        if (empty($login) || !phpCAS::isAuthenticated()) {
            return new AnonymousUser();
        }

        if ($login == phpCAS::getUser()) {
            return new $this->userClass($this);
        }
    }

    /**
     * Retrieves a group object from this authority. Authorities which do not provide group information
     * should always return false
     * @param string $group the shortname of the group to retrieve
     * @return UserGroup a valid group object or false if the group could not be found
     * @see UserGroup object
     */
    public function getGroup($group)
    {
        if (empty($this->initArgs['ATTRA_MEMBER_OF']))
            return false;
        if (empty($group))
            return false;
        if (!phpCAS::isAuthenticated())
            return false;
        
        // Create an empty group
        return new $this->groupClass($this, $group);
    }

    /**
     * Validates an authority for connectivity
     * @return boolean. True if connectivity is established or false if it is not. Authorities may also set an error object to provide more information.
     */
    public function validate(&$error)
    {
        return true;
    }

    /**
      * Returns an array of valid user login types. Subclasses can override this to indicate valid
      * values
      * @return array a list of valid user login types
      */
    protected function validUserLogins()
    {
        return array('LINK', 'NONE');
    }

    /***
     * Resets the authority and returns it to a fresh state.
     * Called by the logout method to clean up any authority specific data (caches etc). Not all authorities will need this
     * @param bool $hard if true a hard reset is done
     */
    protected function reset($hard=false)
    {
        // Log out from the CAS server
        if (phpCAS::isAuthenticated()) {
            $service = "http".(IS_SECURE ? 's' : '')."://".SERVER_HOST.$_SERVER['REQUEST_URI'];
            phpCAS::logoutWithRedirectServiceAndUrl($service, $service);
        }
        return true;
    }
}

/**
  * @package Authentication
  */
class CASUser
    extends User
{
    /**
     * An array of the attribute mapping.
     * 
     * @var array $attributeMap
     */
    private static $attributeMap = array();
    
    /**
     * Configure attribute mappings.
     * 
     * @param string $userProperty
     * @return string $remoteAttribute
     */
    public static function mapAttribute ($userProperty, $remoteAttribute)
    {
        if (empty($userProperty))
            throw new KurogoConfigurationException('$userProperty must not be empty.');
        if (isset(self::$attributeMap[$userProperty]))
            throw new KurogoConfigurationException('User property '.$userProperty.' is already mapped.');
        if (!method_exists('CASUser', 'set'.$userProperty))
            throw new KurogoConfigurationException('Unknown User property '.$userProperty.'.');
        if (empty($remoteAttribute))
            throw new KurogoConfigurationException('$remoteAttribute must not be empty.');

        self::$attributeMap[$userProperty] = $remoteAttribute;
    }
    
    /**
     * Constructor
     *
     * @param AuthenticationAuthority $AuthenticationAuthority
     * @return void
     */
    public function __construct (AuthenticationAuthority $AuthenticationAuthority)
    {
        parent::__construct($AuthenticationAuthority);

        if (!phpCAS::isAuthenticated())
            phpCAS::forceAuthentication();

        $this->setUserID(phpCAS::getUser());
        
        if (!method_exists('phpCAS', 'getAttribute'))
            throw new KurogoConfigurationException('CASAuthentication attribute mapping requires phpCAS 1.2.0 or greater.');
        
        foreach (self::$attributeMap as $property => $attribute) {
            if (phpCAS::hasAttribute($attribute)) {
                $method = 'set'.$property;
                $this->$method(phpCAS::getAttribute($property));
            }
        }
    }
    
    /**
     * An array of groups the user is a member of.
     * 
     * @var array $memberOf
     */
    private $memberOf = array();
    
    /**
     * Set the memberOf attribute
     * 
     * @param mixed array or string $memberOf
     * @return void
     */
    public function setMemberOf ($memberOf)
    {
        if (is_array($memberOf))
            $this->memberOf = $memberOf;
        else
            $this->memberOf = array($memberOf);
    }
    
    /**
     * Answer the memberOf attribute
     * 
     * @return array
     */
    public function getMemberOf ()
    {
        return $this->memberOf;
    }
}

/**
  * @package Authentication
  */
class CASUserGroup
    extends UserGroup
{
    
    /**
     * Constructor
     * 
     * @param string $groupName
     * @return void
     */
    public function __construct (AuthenticationAuthority $AuthenticationAuthority, $groupName)
    {
        parent::__construct($AuthenticationAuthority);
        if (empty($groupName))
            throw new KurogoException('$groupName must not be empty.');
        $this->setGroupName($groupName);
    }
    
    public function userIsMember (User $user)
    {
        if (!$user instanceof CASUser)
            return false;
        
        if ($user->getUserID() != phpCAS::getUser())
            return false;
        
        $memberOf = $user->getMemberOf();
        if (!is_array($memberOf))
            return false;
        
        return in_array($this->getGroupName(), $memberOf);
    }
}