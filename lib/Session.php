<?php
/**
  * @package Authentication
  */

/**
  */
require_once(LIB_DIR . '/AuthenticationAuthority.php');
/**
  */
require_once(LIB_DIR . '/User.php');

/**
  * @package Authentication
  */
class Session
{
    protected $user;
    protected $auth;
    protected $auth_userID;
    
    public function __construct()
    {
        if (!isset($_SESSION)) {
            if (!is_dir(CACHE_DIR . "/session")) {
                mkdir(CACHE_DIR . "/session",0700);
            }
            ini_set('session.save_path', CACHE_DIR . "/session");
            ini_set('session.name', SITE_KEY);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_path', COOKIE_PATH);
            session_start();
        }
        
        $user = new AnonymousUser();
        
        if (isset($_SESSION['auth'])) {
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($_SESSION['auth'])) {

                $auth_userID = isset($_SESSION['auth_userID']) ? $_SESSION['auth_userID'] : '';

                if ($auth_userID) {
                
                    if ($_user = $authority->getUser($auth_userID)) {
                        $user = $_user;
                    } else {
                        error_log("Error trying to load user $auth_userID");
                    } 
                }
            }
        }
                    
        $this->setUser($user);
    }    

    public function isLoggedIn()
    {
        return strlen($this->user->getUserID()) > 0;
    }
    
    protected function setUser(User $user)
    {
        $this->user = $user;
        $_SESSION['userID'] = $user->getUserID();
        $_SESSION['auth_userID'] = $user->getUserID();
        $_SESSION['auth'] = $user->getAuthenticationAuthorityIndex();
    }

    public function getUser()
    {
        return $this->user;
    }
    
    public function login(User $user)
    {
        session_regenerate_id(true);
        $this->setUser($user);
        return $user;
    }

    public function logout()
    {
        $user = new AnonymousUser();
        $this->setUser($user);
		session_regenerate_id(true);
        return true;
    }
}
