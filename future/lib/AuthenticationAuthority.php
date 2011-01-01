<?php

define('AUTH_OK', 1);
define('AUTH_FAILED', -1);
define('AUTH_USER_NOT_FOUND', -2);
define('AUTH_USER_DISABLED', -3);
define('AUTH_INVALID_AUTHORITY', -4);
define('AUTH_ERROR', -10); // server or i/o error

abstract class AuthenticationAuthority
{
    protected $AuthorityIndex;
    protected $AuthorityTitle;
    protected $AuthorityImage; // image shown next to user name when logged in
    
    //Should return one of the auth constants, and set the user variable appropriately
    abstract public function auth($login, $password, &$user);
    
    //Should return a valid User object (see User.php)
    abstract public function getUser($login);

    //Should return a valid Group object (see UserGroup.php)
    abstract public function getGroup($group);

    //Initializes the authority objects based on an associative array of arguments
    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['TITLE'], $args['INDEX'])) {
            throw new Exception("Title and index must be set");
        }
        
        $this->setAuthorityIndex($args['INDEX']);
        $this->setAuthorityTitle($args['TITLE']);
        
        if (isset($args['LOGGEDIN_IMAGE_URL']) && strlen($args['LOGGEDIN_IMAGE_URL'])) {
            $this->setAuthorityImage($args['LOGGEDIN_IMAGE_URL']);
        }
        
    }

    public function getAuthorityIndex()
    {
        return $this->AuthorityIndex;
    }

    public function setAuthorityIndex($index)
    {
        $this->AuthorityIndex = $index;
    }

    public function setAuthorityTitle($title)
    {
        $this->AuthorityTitle = $title;
    }

    public function getAuthorityTitle()
    {
        return $this->AuthorityTitle;
    }

    public function setAuthorityImage($url)
    {
        $this->AuthorityImage = $url;
    }

    public function getAuthorityImage()
    {
        return $this->AuthorityImage;
    }

    public static function getDefinedAuthenticationAuthorities()
    {
        static $configFile;
        if (!$configFile) {
            $configFile = ConfigFile::factory('authentication', 'feeds');
        }
        
        return $configFile->getSectionVars();
    }
    
    public static function getDefaultAuthenticationAuthority()
    {
        $authorities = self::getDefinedAuthenticationAuthorities();
        return current($authorities);
    }

    public static function getAuthenticationAuthority($index)
    {
        static $configFile;
        if (!$configFile) {
            $configFile = ConfigFile::factory('authentication', 'feeds');
        }
        
        if ($authorityData = $configFile->getSection($index)) {
            $authorityClass = $authorityData['CONTROLLER_CLASS'];
            $authorityData['INDEX'] = $index;
            $authority = self::factory($authorityClass, $authorityData);
            return $authority;
        }
        
        return false;
    }
    
    public static function getInstalledAuthentiationAuthorities()
    {
        $dirs = array(
            LIB_DIR, SITE_DIR . '/lib'
        );
        
        $authorities = array();
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $d = dir($dir);
                while (false !== ($entry = $d->read())) {
                    $file = $dir . '/' . $entry;
                    if (preg_match("/^([A-Z].*?)\.php$/", $entry, $bits)) {
                        $class = $bits[1];
                        if (@include_once($file)) {
                            if (class_exists($class) && is_subclass_of($class, 'AuthenticationAuthority')) {
                                $authorities[$class] = $class;
                            }
                        }
                    }
                }
            }
        }
                
        return $authorities;
    }

    public static function factory($authorityClass, $args)
    {
        if (!class_exists($authorityClass)) {
            throw new Exception("Invalid authentication class $authorityClass");
        }
        $authority = new $authorityClass;
        $authority->init($args);
        return $authority;
    }
    
    protected function reset()
    {
    }
    
    public function logout(Module $module)
    {
        $session = $module->getSession();
        $session->logout();
        $this->reset();
    }
    
    public function login($login, $pass, Module $module)
    {
        $result = $this->auth($login, $pass, $user);
        
        if ($result == AUTH_OK) {
            $session = $module->getSession();
            $session->login($user);
        }
        
        return $result;
    }
}
