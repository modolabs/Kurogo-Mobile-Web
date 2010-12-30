<?php

require_once(LIB_DIR . '/Session.php');
require_once(LIB_DIR . '/UserGroup.php');

abstract class User
{
    protected $userID;
    protected $AuthenticationAuthority;
    protected $email;
    protected $FirstName;
    protected $LastName;
    
    protected $attributes=array();
    
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
    
    public function setAuthenticationAuthority(AuthenticationAuthority $AuthenticationAuthority)
    {
        $this->AuthenticationAuthority = $AuthenticationAuthority;
    }

    public function getAuthenticationAuthority()
    {
        return $this->AuthenticationAuthority;
    }

    public function getAuthenticationAuthorityIndex()
    {
        if ($authority = $this->getAuthenticationAuthority()) {
            return $authority->getAuthorityIndex();
        } 
        
        return null;
    }
    
    protected function standardAttributes()
    {
        return array(
            'userID', 'email'
        );
    }
    
    public function setAttribute($attribute, $value)
    {
        if (in_array($attribute, $this->standardAttributes())) {
            $method = "set" . $attribute;
            return $this->$method($value);
        } else {
            $this->attributes[$attribute] = $value;
        }
    
    }
    
    public function getAttribute($attribute)
    {
        if (in_array($attribute, $this->standardAttributes())) {
            $method = "get" . $var;
            return $this->$method();
        } elseif (array_key_exists($var, $this->attributes)) {
            return $this->attributes[$var];
        }
    }
    
    public function setFirstName($FirstName)
    {
        $this->FirstName = $FirstName;
    }

    public function setLastName($LastName)
    {
        $this->LastName = $LastName;
    }
    
    public function getFirstName()
    {
        return $this->FirstName;
    }

    public function getLastName()
    {
        return $this->LastName;
    }
    
    public function __construct(AuthenticationAuthority $AuthenticationAuthority)
    {
        $this->setAuthenticationAuthority($AuthenticationAuthority);
    }
    
    public function isMemberOfGroup(UserGroup $group)
    {
        return $group->userIsMember($this);
    }
}

class BasicUser extends User
{
}

class AnonymousUser extends User
{
    public function __construct()
    {
    }
}

