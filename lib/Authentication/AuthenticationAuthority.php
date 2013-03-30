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
 * An abstract class that all authorities must inherit from. 
 * @package Authentication
 */
abstract class AuthenticationAuthority
{
    
    /** 
      * The arguments used to initialize this authority
      * @var array
      */
    protected $initArgs = array();

    /** 
      * The tag used to identify this authority 
      * @var string
      */
    protected $AuthorityIndex; 

    /** 
      * The human readable title of this authority
      * @var string
      */
    protected $AuthorityTitle; 
    
    /** 
      * Image shown next to user name when logged in (optional) 
      * @var string
      */
    protected $AuthorityImage; 

    /** 
      * User Login type. One of 3 values: FORM, LINK, AUTO or NONE
      * @var string
      */
    protected $userLogin;

    /** 
      * CSS class for showing this authority
      * @var string
      */
    protected $authorityClass = '';
    
    /** 
      * Class for user objects. Most subclasses will override this
      * @var string
      */
    protected $userClass='User';

    /** 
      * Class for group objects. Most subclasses will override this
      * @var string
      */
    protected $groupClass='UserGroup';
    
    /**
      * Whether debug mode is on or off. See setDebugMode
      * @var bool
      */
    protected $debugMode = false;
    
    /**
      * Save user credentials. Needed for web services that require username/password. Only works with direct authorities
      * @var $bool
      */
    protected $saveCredentials = false;
    
    /**
     * Attempts to authenticate the user using the included credentials
     * @param string $login the userid to login (this will be blank for OAUTH based authorities)
     * @param string $password password (this will be blank for OAUTH based authorities)
     * @param User &$user This object is passed by reference and should be set to the logged in user upon sucesssful login
	 * @return int should return one of the AUTH_ constants     
     */
    abstract protected function auth($login, $password, &$user);
    
    /**
     * Retrieves a user object from this authority
     * @param string $login the userid to retrieve
	 * @return User a valid user object or false if the user could not be found
	 * @see User object
     */
    abstract public function getUser($login);
    
    public function getCurrentUser() {
        $session = Kurogo::getSession();
        return $session->getUser($this);
    }

    public function isLoggedIn() {
        $session = Kurogo::getSession();
        return $session->isLoggedIn($this);
    }
    
    /**
     * Sets the user class used to create users
     * @param string $class the class name of the user class. Should be a subclass of User
     */
    protected function setUserClass($class) {
    	$this->userClass = $class;
    }

    public function getUserClass() {
    	return $this->userClass;
    }

    /**
     * Retrieves a group object from this authority. Authorities which do not provide group information
     * should always return false
     * @param string $group the shortname of the group to retrieve
	 * @return UserGroup a valid group object or false if the group could not be found
	 * @see UserGroup object
     */
    abstract public function getGroup($group);
    
    /**
     * Validates an authority for connectivity
	 * @return boolean. True if connectivity is established or false if it is not. Authorities may also set an error object to provide more information.
     */
    abstract public function validate(&$error);

    /**
     * Initializes the authority objects based on an associative array of arguments
     * @param array $args an associate array of arguments. The argument list is dependent on the authority
     *
     * Required keys:
     * TITLE => The human readable title of the AuthorityImage
     * INDEX => The tag used to identify this authority @see AuthenticationAuthority::getAuthenticationAuthority
     * 
     * Optional keys:
     * LOGGEDIN_IMAGE_URL => a url to an image/badge that is placed next to the user name when logged in
     *
     * Specific authorities might have other required or optional keys
     * 
     * NOTE: Any subclass MUST call parent::init($args) to ensure proper operation
     *
     */
    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        $this->initArgs = $args;
        if (isset($args['DEBUG_MODE'])) {
            $this->setDebugMode($args['DEBUG_MODE']);
        } else {
            $this->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        }
                
        if (!isset($args['TITLE']) || empty($args['TITLE'])) {
            throw new KurogoConfigurationException("Invalid authority title");
        }
        
        if (!isset($args['INDEX']) || empty($args['INDEX'])) {
            throw new KurogoConfigurationException("Invalid authority index");
        }
        
        $this->setAuthorityIndex($args['INDEX']);
        $this->setAuthorityTitle($args['TITLE']);

        if (!isset($args['USER_LOGIN'])) {
            throw new KurogoConfigurationException("USER_LOGIN value not set for " . $this->AuthorityTitle);
        }

        if (!$this->setUserLogin($args['USER_LOGIN'])) {
            throw new KurogoConfigurationException("Invalid USER_LOGIN setting for " . $this->AuthorityTitle);
        }
        
        if (isset($args['SAVE_CREDENTIALS'])) {
            $this->setSaveCredentials($args['SAVE_CREDENTIALS']);
        }
        
        if (isset($args['LOGGEDIN_IMAGE_URL']) && strlen($args['LOGGEDIN_IMAGE_URL'])) {
            $this->setAuthorityImage($args['LOGGEDIN_IMAGE_URL']);
        }

        if (isset($args['USER_CLASS']) && strlen($args['USER_CLASS'])) {
            $this->setUserClass($args['USER_CLASS']);
        }
    }
    
    /**
      * Returns an array of valid user login types. Subclasses can override this to indicate valid
      * values
      * @return array a list of valid user login types
      */
    protected function validUserLogins()
    {
        return array('FORM', 'LINK', 'AUTO', 'NONE');
    }
    
    /**
      * Sets the user login type
      * @param string userLogin a valid userLogin type (FORM, LINK, AUTO, NONE)
      * @return boolean true if it was successful or false if it was not
      */
    public function setUserLogin($userLogin)
    {
        if (in_array($userLogin, $this->validUserLogins())) {
            $this->userLogin = strtoupper($userLogin);
            return true;
        }
        
        return false;
    }
    
    public function setSaveCredentials($bool) {
        $this->saveCredentials = $bool ? true : false;
        if ($this->saveCredentials && $this->userLogin != 'FORM') {
            throw new KruogoConfigurationException("Credentials can only be saved when using USER_LOGIN=FORM");
        }
    }

    /**
      * Returns the user login type
      * @return string the user Login type
      */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * Retrieves the authority index
     * @return string
    */
    public function getAuthorityIndex()
    {
        return $this->AuthorityIndex;
    }
    
    public function getAuthorityClass() {
        return $this->authorityClass;
    }

    /**
     * Sets the authority index
     * @param string $index the authority index/tag
    */
    public function setAuthorityIndex($index)
    {
        $this->AuthorityIndex = (string) $index;
    }

    /**
     * Sets the authority title
     * @param string $title a human readable title
    */
    public function setAuthorityTitle($title)
    {
        $this->AuthorityTitle = (string) $title;
    }

    /**
     * Retrieves the authority title
     * @return string
    */
    public function getAuthorityTitle()
    {
        return $this->AuthorityTitle;
    }

    /**
     * Sets the authority image, an image that is shown next to the user when logged in. If an image is not present it will show the authority title
     * @param string a url (full or relative as appropriate) to a browser viewable image/badge. For best results use an image less than the text height of the footer content
    */
    public function setAuthorityImage($url)
    {
        $this->AuthorityImage = (string) $url;
    }

    /**
     * Retrieves the authority image
     * @return string
    */
    public function getAuthorityImage()
    {
        return $this->AuthorityImage;
    }
    
    /**
     * Parses the authentication config file and returns a list of authorities and their arguments
     * @return array
    */
    public static function getDefinedAuthenticationAuthorities()
    {
        return Kurogo::getOptionalSiteSections('authentication');
    }

    public static function getDefinedAuthenticationAuthorityNames()
    {
        $authorities = self::getDefinedAuthenticationAuthorities();
        $authorityNames = array();
        foreach ($authorities as $authority=>$data) {
            $authorityNames[$authority]= $data['TITLE'];
        }

        return $authorityNames;
    }
    
    /**
     * Returns the default (i.e. the first) authentication authority in the config file. 
     * @return array 
    */
    public static function getDefaultAuthenticationAuthority()
    {
    	return self::getAuthenticationAuthority(
    		self::getDefaultAuthenticationAuthorityIndex()
    	);
    }

    public static function getDefaultAuthenticationAuthorityIndex()
    {
        $authorities = self::getDefinedAuthenticationAuthorities();
        return key($authorities);
    }

    public static function getAuthenticationAuthorityData(&$index) {
        if (strlen($index)==0) {
            return false;
        }
        
        $sections = self::getDefinedAuthenticationAuthorities();
        
        //check and see if $index is a authority index
        if (isset($sections[$index])) {
            return $sections[$index];
        }
        
        // check and see if an authority that is a class or subclass of $index has been defined
        foreach ($sections as $sectionIndex=>$sectionData) {
            if (isset($sectionData['PACKAGE'])) {
                Kurogo::includePackage($sectionData['PACKAGE']);
            }
            $className = $sectionData['CONTROLLER_CLASS'];
            $class = new ReflectionClass($className);
            if ($className==$index || $class->isSubclassOf($index)) {
                $index = $sectionIndex;
                return $sectionData;
            }
        }
        
        //nothing found
        return false;
    }
    
    public static function validateAuthority($index, $authorityData) {

        $authorityData['INDEX'] = $index;
        $authorityClass = $authorityData['CONTROLLER_CLASS'];
        $authority = self::factory($authorityClass, $authorityData);
        if (!$result = $authority->validate($error)) {
            return $error;
        }
        
        return true;
    }
    
    

    /**
     * Retrieves an authentication authority by its index. This is the preferred way to retrieve an authority
     * @param string $index the index/tag or the Authentication Authority Class to retrieve
     * @return AuthenticationAuthority object initialized based on the values in the authentication config file or false if the index was not found
    */
    public static function getAuthenticationAuthority($index)
    {
        static $cache;
        if (isset($cache[$index])) {
            return $cache[$index];
        }
        
        if ($authorityData = self::getAuthenticationAuthorityData($index)) {
            $authorityClass = $authorityData['CONTROLLER_CLASS'];
            $authorityData['INDEX'] = $index;
            $authority = self::factory($authorityClass, $authorityData);
            $cache[$index] = $authority;
            return $authority;
        }
        
        return false;
    }
    
    /**
     * Retrieves a list of installed authorities based on available class files
     * Will search both the main lib dir as well as the site lib dir
     * @return an array of class names that inherit from AuthenticationAuthority
     *
     * Note: currently not used, but will likely be used in a future admin interface 
     */
    public static function getInstalledAuthentiationAuthorities()
    {
        $dirs = array(
            LIB_DIR . '/Authentication', SITE_DIR . '/lib/Authentication'
        );
        
        $authorities = array();
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $d = dir($dir);
                while (false !== ($entry = $d->read())) {
                    $file = $dir . '/' . $entry;
                    if (preg_match("/^([A-Z].*?)\.php$/", $entry, $bits)) {
                        $class = $bits[1];
                        $info = new ReflectionClass($class);
                        if (!$info->isAbstract()) {
                            if (is_subclass_of($class, 'AuthenticationAuthority')) {
                                $authorities[$class] = $class;
                            }
                        }
                    }
                }
                $d->close();
            }
        }
                
        return $authorities;
    }
    
    /**
     * Sets debug mode
     * @param bool 
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }
    
    /**
     * 
     * Initializes an authentication authority object
     * @param string $authorityClass the name of the class to instantiate. Must be a subclass of AuthenticationAuthority
     * @param array $args an associative array of arguments. Argument values depend on the authority
     * @return AuthenticationAuthority
     * @see AuthenticationAuthority::init()
     */
    public static function factory($authorityClass, $args)
    {
        if (isset($args['PACKAGE'])) {
            Kurogo::includePackage($args['PACKAGE']);
        }

        if (!class_exists($authorityClass) || !is_subclass_of($authorityClass, 'AuthenticationAuthority')) {
            throw new KurogoConfigurationException("Invalid authentication class $authorityClass");
        }
        $authority = new $authorityClass;
        $authority->init($args);
        return $authority;
    }

    /**
     * 
     * Resets the authority and returns it to a fresh state.
     * Called by the logout method to clean up any authority specific data (caches etc). Not all authorities will need this
     * @param bool $hard if true a hard reset is done 
     */
    protected function reset($hard=false)
    {
    }
    
    /**
     * Logout the current user
     * @param Module $module the module initiating the logout
     * 
     * Subclasses should not need to override this, but instead provide additional behavior in reset()
     */
    public function logout(Session $session, $hard=false)
    {
        $this->reset($hard);
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
        $result = $this->auth($login, $password, $user);
        
        if ($result == AUTH_OK) {
            if ($this->saveCredentials) {
                $user->setCredentials($password, $this);
            }
            $session->login($user);
        }
        
        return $result;
    }
}
