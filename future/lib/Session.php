<?php

require_once(LIB_DIR . '/AuthenticationAuthority.php');
require_once(LIB_DIR . '/User.php');

class Session
{
    protected $user;
    protected $AuthenticationAuthority;
    
    public function __construct(AuthenticationAuthority $AuthenticationAuthority)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $this->AuthenticationAuthority = $AuthenticationAuthority;
        
        $userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : '';
        if (!$user = $this->AuthenticationAuthority->getUser($userID)) {
            if ($userID) {
                error_log("Error trying to load user $userID");
            }
            $user = new AnonymousUser();
        }
        $this->setUser($user);
    }    

    private function reset() {
        $user = new AnonymousUser();
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
    }

    public function getUser()
    {
        return $this->user;
    }
    
    public function login($login, $pass)
    {
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
