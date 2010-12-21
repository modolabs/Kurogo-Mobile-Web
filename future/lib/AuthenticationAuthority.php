<?php

define('AUTH_OK', 1);
define('AUTH_FAILED', -1);
define('AUTH_USER_NOT_FOUND', -2);
define('AUTH_USER_DISABLED', -3);
define('AUTH_ERROR', -4); // server or i/o error

abstract class AuthenticationAuthority
{
    //Should return one of the auth constants, and set the user variable appropriately
    abstract public function auth($login, $password, &$user);
    
    //Should return a valid User object (see User.php)
    abstract public function getUser($login);

    //Initializes the authority objects based on an associative array of arguments
    abstract function init($args);

    public static function factory($authorityClass, $args)
    {
        $authority = new $authorityClass;
        $authority->init($args);
        return $authority;
    }
}
