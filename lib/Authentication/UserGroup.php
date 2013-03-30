<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
abstract class UserGroup
{
    protected $group;
    protected $AuthenticationAuthority;
    protected $gid;
    protected $members=array();
    
    protected $attributes=array();
        
    public function getGroupID()
    {
        return $this->gid;
    }

    public function setGroupID($gid)
    {
        $this->gid = intval($gid);
    }
    
    public function setGID($gid)
    {
        return $this->setGroupID($gid);
    }

    public function setGroupName($groupName)
    {
        $this->group = $groupName;
    }
    
    public function getGroupName()
    {
        return $this->group;
    }

    public function setMembers($members)
    {
        $this->members = array();
        $members = is_array($members) ? $members : array();
        
        foreach ($members as $member) {
            if ($member instanceOf User) {
                $this->members[] = $member;
            }
        }
    }
    
    public function addMember(User $member) {
        if (in_array($member, $this->getMembers())) {
            return false;
        }
        
        $this->members[] = $member;
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
        return $this->AuthenticationAuthority->getAuthorityIndex();
    }
    
    protected function standardAttributes()
    {
        return array(
            'group', 'gid', 'members'
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
    
    public function __construct(AuthenticationAuthority $AuthenticationAuthority)
    {
        $this->setAuthenticationAuthority($AuthenticationAuthority);
    }

    public function getMembers()
    {
        return $this->members;
    }
    
    public function userIsMember(User $user)
    {
        return in_array($user, $this->getMembers());
    }
}

