<?php

require_once(LIB_DIR . '/Session.php');

abstract class User
{
    protected $userID;
    protected $email;
    
    public function getUserID()
    {
        return $this->userID;
    }

    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setUserID($userID)
    {
        $this->userID = $userID;
    }
    
    public static function factory(AuthenticationAuthority $AuthenticationAuthority)
    {
        //load the session object which contains the current session user
        $session = new Session($AuthenticationAuthority); 
        return $session->getUser();
    }
}

class BasicUser extends User
{
}

class AnonymousUser extends User
{
}

