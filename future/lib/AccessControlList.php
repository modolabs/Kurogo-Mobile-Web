<?php

class AccessControlList
{
    const RULE_TYPE_AUTHORITY='A';
    const RULE_TYPE_USER='U';
    const RULE_TYPE_GROUP='G';
    const RULE_TYPE_EVERYONE='E';
    const RULE_VALUE_ALL='*';
    const RULE_ACTION_ALLOW='A';
    const RULE_ACTION_DENY='D';
    protected $ruleAction;
    protected $ruleType;
    protected $ruleValue;
    
    public function ruleTypes()
    {
        return array(
            AccessControlList::RULE_TYPE_AUTHORITY,
            AccessControlList::RULE_TYPE_USER,
            AccessControlList::RULE_TYPE_GROUP,
            AccessControlList::RULE_TYPE_EVERYONE
        );
    }

    public function ruleActions()
    {
        return array(
            AccessControlList::RULE_ACTION_ALLOW,
            AccessControlList::RULE_ACTION_DENY
        );
    }
    
    public function evaluateForUser(User $user)
    {
        switch ($this->ruleType)
        {
            case self::RULE_TYPE_AUTHORITY:
                if ($this->ruleValue==self::RULE_VALUE_ALL) {
                    if ($user->getUserID() && $user->getAuthenticationAuthority()) {
                        return $this->ruleAction;
                    }
                } elseif ($user->getUserID() && $user->getAuthenticationAuthorityIndex()==$this->ruleValue) {
                    return $this->ruleAction;
                }
                
                break;
            case self::RULE_TYPE_USER:
                if ($this->ruleValue==self::RULE_VALUE_ALL) {
                    if ($user->getUserID()) {
                        return $this->ruleAction;
                    }
                } else { 
                    $values = explode("|", $this->ruleValue);
                    $authority = isset($values[0]) ? $values[0] : '';
                    $userID = isset($values[1]) ? $values[1] : '';
                    
                    if ($user->getUserID()==$userID && $user->getAuthenticationAuthorityIndex()==$authority) {
                        return $this->ruleAction;
                    }
                }
                break;
            case self::RULE_TYPE_GROUP:
                $values = explode("|", $this->ruleValue);
                $authority = isset($values[0]) ? $values[0] : '';
                $group = isset($values[1]) ? $values[1] : '';
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authority)) {
                    if ($group = $authority->getGroup($group)) {
                        if ($group->userIsMember($user)) {
                            return $this->ruleAction;
                        }
                    }
                }
                
                break;
            case self::RULE_TYPE_EVERYONE:
                return $this->ruleAction;
                break;
        }
        
        return false;
    }
    
    public static function factory($ruleAction, $ruleType, $ruleValue)
    {
        try {
            $AccessControlList = new AccessControlList($ruleAction, $ruleType, $ruleValue);
        } catch (Exception $e) {
            $AccessControlList = false;
        }
        return $AccessControlList;
    }
    
    public function __construct($ruleAction, $ruleType, $ruleValue)
    {
        if (!in_array($ruleAction, self::ruleActions())) {
            throw new Exception("Invalid rule action $ruleAction");
        }

        if (!in_array($ruleType, self::ruleTypes())) {
            throw new Exception("Invalid rule type $ruleType");
        }

        if ($ruleType==self::RULE_TYPE_GROUP && $ruleValue == self::RULE_VALUE_ALL) {
            throw new Exception("Rule of type Group cannot contain ALL");
        }
        
        if ($ruleType != self::RULE_TYPE_EVERYONE && empty($ruleValue)) {
            throw new Exception("Rule value cannot be empty");
        }

        $this->ruleAction = $ruleAction;
        $this->ruleType = $ruleType;
        $this->ruleValue = $ruleValue;
    }
}

