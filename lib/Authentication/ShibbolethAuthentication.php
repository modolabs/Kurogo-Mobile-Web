<?php

class ShibbolethAuthentication extends AuthenticationAuthority
{
    protected $userClass = 'ShibbolethUser';
    protected function auth($login, $password, &$user) {
        if (isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER'])) {
            $user = new $this->userClass($this);
            $user->setUserID($_SERVER['REMOTE_USER']);
            return AUTH_OK;
        }
        
        return AUTH_FAIL;
    }

    public function getUser($login) {
        if (isset($_SERVER['REMOTE_USER']) && $_SERVER['REMOTE_USER'] == $login) {
            $user = new $this->userClass($this);
            $user->setUserID($_SERVER['REMOTE_USER']);
            return $user;        
        }
        
        return false;
    }

    public function getGroup($group) {
      return false;
    }

    public function validate(&$error) {
       return true;
    }   

}

class ShibbolethUser extends User
{
}