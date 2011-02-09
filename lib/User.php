<?php
/**
 * User
 * @package Authentication
 */

/**
 * User class
 * @package Authentication
 */
abstract class User
{
    protected $userID;
    protected $AuthenticationAuthority;
    protected $email;
    protected $FirstName;
    protected $LastName;
    protected $FullName;
    
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
            'userid', 'email'
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
        if (in_array(strtolower($attribute), $this->standardAttributes())) {
            $method = "get" . $attribute;
            return $this->$method();
        } elseif (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
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

    public function setFullName($FullName)
    {
        $this->FullName = $FullName;
    }
    
    public function getFullName()
    {
        if (!empty($this->FullName)) {
            return $this->FullName;
        } elseif (!empty($this->FirstName) || !empty($this->LastName)) {
            return trim(sprintf("%s %s", $this->FirstName, $this->LastName));
        } else {
            return $this->getUserID();
        }
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

/**
 * Basic user class
 * @package Authentication
 */
class BasicUser extends User
{
}

/**
 * Anonymous User
 * @package Authentication
 */
class AnonymousUser extends User
{
    public function __construct()
    {
    }
}

