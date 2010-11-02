<?php

class User
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
    
    public static function factory()
    {
        //for now we'll just return an non-loggedin user;
        return new AnonymousUser();
    }

    public function isLoggedIn()
    {
        return true;
    }
}

class AnonymousUser extends User
{
    protected $userID='';
    protected $email='';
    
    public function isLoggedIn()
    {
        return false;
    }
}

