<?php

require_once(LIB_DIR . '/AuthenticationAuthority.php');
require_once(LIB_DIR . '/User.php');

class Session
{
    protected $user;
    protected $auth;
    protected $auth_userID;
    protected $AuthenticationAuthorityIndex;
    
    public function getAuthenticationAuthority()
    {
        return $this->AuthenticationAuthority;
    }

    public function getAuthenticationAuthorityIndex()
    {
        return $this->AuthenticationAuthorityIndex;
    }
    
    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $user = new AnonymousUser();
        
        if (isset($_SESSION['auth'])) {
            if ($this->setAuthenticationAuthority($_SESSION['auth'])) {
                $auth_userID = isset($_SESSION['auth_userID']) ? $_SESSION['auth_userID'] : '';

                if ($auth_userID) {
                
                    if (!$user = $this->AuthenticationAuthority->getUser($auth_userID)) {
                        error_log("Error trying to load user $userID");
                        $user = new AnonymousUser();
                    } 
                }
            }
        }
                    
        $this->setUser($user);
    }    

    private function reset() {
        $user = new AnonymousUser();
        $this->setAuthenticationAuthority(null);
        $this->setUser($user);
		session_regenerate_id(true);
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
        $_SESSION['auth'] = $this->AuthenticationAuthorityIndex;
    }

    public function getUser()
    {
        return $this->user;
    }
    
    protected function setAuthenticationAuthority($authorityIndex)
    {
        if ($AuthenticationAuthority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
            $this->AuthenticationAuthority = $AuthenticationAuthority;
            $this->AuthenticationAuthorityIndex = $authorityIndex;
            return true;
        } elseif (is_null($authorityIndex)) {
            $this->AuthenticationAuthority = null;
            $this->AuthenticationAuthorityIndex = null;
            return true;
        }
        
        return false;
    }
    
    public function login($login, $pass, $authority)
    {
        if (!$this->setAuthenticationAuthority($authority)) {
            return AUTH_INVALID_AUTHORITY;
        }
        
        $result = $this->AuthenticationAuthority->auth($login, $pass, $user);
        
        if ($result == AUTH_OK) {
            session_regenerate_id(true);
            $this->setUser($user);
        }
        
        return $result;
    }

    public function logout()
    {
    	$this->reset();
        return true;
    }
}
