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
 * @subpackage AccessControlList
 */

/**
 * AccessControlList
 * Used to encapsulate a rule used for access control
 * @see Module::getAccessControlLists()
 * @see AuthenticationAuthority 
 * @package Authentication
 * @subpackage AccessControlList
 */
class AccessControlList
{
    /* enums for rule types */

    /** Rule for user access */
    const RULE_TYPE_ACCESS='U';

    /** Rule for admin access */
    const RULE_TYPE_ADMIN='A';

    /* enums for rule scopes */
    /** matches any user within the specified authority */
    const RULE_SCOPE_USER='U'; 
    
    /** matches a group/authority combo */
    const RULE_SCOPE_GROUP='G'; 
    
    /** matches everyone (including anonymous) */
    const RULE_SCOPE_EVERYONE='E'; 
    
    /** special constant to match all values of a particular scope */
    const RULE_VALUE_ALL='*'; 
    
    /* enums for rule actions */
    /** allow */
    const RULE_ACTION_ALLOW='A'; 
    
    /** deny */
    const RULE_ACTION_DENY='D';

    /**
      * Rule Action
      * @var string 
    */
    protected $ruleType;

    /**
      * Rule Action
      * @var string 
    */
    protected $ruleAction;

    /**
      * Rule scope
      * @var string 
    */
    protected $ruleScope;

    /**
      * Rule authority
      * @var string 
    */
    protected $ruleAuthority;

    /**
      * Rule value
      * @var string 
    */
    protected $ruleValue;
    
    /**
     * Returns a list of valid rule types
	 * @return array
     */
    public static function ruleTypes()
    {
        return array(
            AccessControlList::RULE_TYPE_ACCESS,
            AccessControlList::RULE_TYPE_ADMIN
        );
    }
    
    /**
     * Returns a list of valid rule scopes
	 * @return array
     */
    public static function ruleScopes()
    {
        return array(
            AccessControlList::RULE_SCOPE_USER,
            AccessControlList::RULE_SCOPE_GROUP,
            AccessControlList::RULE_SCOPE_EVERYONE
        );
    }

    /**
     * Returns a list of valid rule actions
	 * @return array
     */
    public static function ruleActions()
    {
        return array(
            AccessControlList::RULE_ACTION_ALLOW,
            AccessControlList::RULE_ACTION_DENY
        );
    }
    
    /**
     * Sees if the given user matches the rule
     * @param User $user a valid user object
	 * @return mixed, the action if the user matches the rule or false if the rule did not match
     */
    public function evaluateForUser(User $user)
    {
        switch ($this->ruleScope)
        {
            case self::RULE_SCOPE_USER:
                /* if the value is all then see if the userID is set
                   this will NOT match an anonymous user 
                */
                if ($this->ruleAuthority) {
                    if ($user->getAuthenticationAuthorityIndex()==$this->ruleAuthority) {
                        /* can match either userID or email */
                        if ($this->ruleValue==self::RULE_VALUE_ALL) {
                            if ($user->getUserID()) {
                                return $this->ruleAction;
                            }
                        } else if ($user->getUserID()==$this->ruleValue ||
                            (Validator::isValidEmail($this->ruleValue) && $user->getEmail()==$this->ruleValue)) { 
                            return $this->ruleAction;
                        }
                    }

                } elseif ($this->ruleValue==self::RULE_VALUE_ALL) {
                    if ($user->getUserID()) {
                        return $this->ruleAction;
                    }
                } else if ($user->getUserID()==$this->ruleValue ||
                    (Validator::isValidEmail($this->ruleValue) && $user->getEmail()==$this->ruleValue)) { 
                    return $this->ruleAction;
                }
                
                break;
            case self::RULE_SCOPE_GROUP:
                /* Note: a group value of ALL is not valid */

                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($this->ruleAuthority)) {
                    if ($group = $authority->getGroup($this->ruleValue)) {

                        /* see if the user is a member of the group */
                        if ($group->userIsMember($user)) {
                            return $this->ruleAction;
                        }
                    }
                }
                
                break;
            case self::RULE_SCOPE_EVERYONE:
                /* always matches */
                return $this->ruleAction;
                break;
        }
        
        return false;
    }
    
    /**
      * Returns an access control list based on a string
      * @param string Should be in format ACTION:RULE:VALUE
      * @return AccessControlList or false if the string is invalid
      */
    public static function createFromArray($aclArray) {
        $aclArray = array_merge(array(
            'type'=>'','action'=>'','scope'=>'','authority'=>'','value'=>''), 
            $aclArray
        );
            
        $acl = self::factory($aclArray['type'], $aclArray['action'], $aclArray['scope'], $aclArray['authority'], $aclArray['value']);
        return $acl;
    }
    
    public function getType() {
        return $this->ruleType;
    }
    
    public function __toString() {
        $str = '(';
        switch($this->ruleType)
        {
            case self::RULE_TYPE_ACCESS:
                $str .= 'Access';
                break;
            case self::RULE_TYPE_ADMIN:
                $str .= 'Admin';
                break;
        }
        $str .= ") ";

        switch($this->ruleAction)
        {
            case self::RULE_ACTION_ALLOW:
                $str .= 'Allow ';
                break;
            case self::RULE_ACTION_DENY:
                $str .= 'Deny ';
                break;
        }

        switch($this->ruleScope)
        {
            case self::RULE_SCOPE_USER:
                if ($this->ruleValue == self::RULE_VALUE_ALL) {
                    if ($this->ruleAuthority) {
                        $str .= " All Users";
                    } else {
                        $str .= " All Logged In Users";
                    }
            } else {
                    $str .= " User \"$this->ruleValue\"";
                }
                break;
            case self::RULE_SCOPE_GROUP:
                $str .= " Group \"$this->ruleValue\"";
                break;
            case self::RULE_SCOPE_EVERYONE:
                $str .= ' Everyone ';
                break;
        }
        
        if ($this->ruleAuthority) {
            $str .= " from \"$this->ruleAuthority\"";
        }
        
        return $str;
    }
    
    public function toArray() {
        return array(
            'TITLE'=>strval($this),
            'type'=>$this->ruleType,
            'action'=>$this->ruleAction,
            'scope'=>$this->ruleScope,
            'authority'=>$this->ruleAuthority,
            'value'=>$this->ruleValue
        );
    }

    public static function allAccess() {
        return self::factory(
            self::RULE_TYPE_ACCESS,
            self::RULE_ACTION_ALLOW,
            self::RULE_SCOPE_EVERYONE,
            null,
            null
        );
    }
    
    public static function validateACL($key, $aclArray) {
        $aclArray = array_merge(array(
            'type'=>'','action'=>'','scope'=>'','authority'=>'','value'=>''), 
            $aclArray
        );
            
        $acl = new AccessControlList($aclArray['type'], $aclArray['action'], $aclArray['scope'], $aclArray['authority'], $aclArray['value']);
        return true;
    }
    
    /**
     * Instantiates an AccessControlList 
     * @param $ruleType  string @see AccessControlList::ruleTypes()
     * @param $ruleAction string @see AccessControlList::ruleActions() 
     * @param $ruleScope string @see AccessControlList::ruleScopes() 
     * @param $ruleValue string 
	 * @return AccessControlList or false if there was an error
     */
    public static function factory($ruleType, $ruleAction, $ruleScope, $ruleAuthority, $ruleValue)
    {
        try {
            $AccessControlList = new AccessControlList($ruleType, $ruleAction, $ruleScope, $ruleAuthority, $ruleValue);
        } catch (KurogoConfigurationException $e) {
            $AccessControlList = false;
        }
        return $AccessControlList;
    }
    
    /**
     * Constructor
     * @param $ruleType  string @see AccessControlList::ruleTypes()
     * @param $ruleAction string @see AccessControlList::ruleActions() 
     * @param $ruleScope string @see AccessControlList::ruleScopes() 
     * @param $ruleValue string 
     *
     * Will throw an exception if invalid values are present
     */
    public function __construct($ruleType, $ruleAction, $ruleScope, $ruleAuthority, $ruleValue)
    {
        if (!in_array($ruleType, self::ruleTypes())) {
            throw new KurogoConfigurationException("Invalid rule type $ruleType");
        }

        if (!in_array($ruleAction, self::ruleActions())) {
            throw new KurogoConfigurationException("Invalid rule action $ruleAction");
        }

        if (!in_array($ruleScope, self::ruleScopes())) {
            throw new KurogoConfigurationException("Invalid rule scope $ruleScope");
        }

        if ($ruleScope==self::RULE_SCOPE_GROUP && $ruleValue == self::RULE_VALUE_ALL) {
            throw new KurogoConfigurationException("Rule of type Group cannot contain ALL");
        }
        
        if ($ruleScope != self::RULE_SCOPE_EVERYONE && empty($ruleValue)) {
            throw new KurogoConfigurationException("Rule value cannot be empty");
        }

        $this->ruleType = $ruleType;
        $this->ruleAction = $ruleAction;
        $this->ruleScope = $ruleScope;
        $this->ruleAuthority = $ruleAuthority;
        $this->ruleValue = $ruleValue;
    }
}
