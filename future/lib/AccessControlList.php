<?php
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
    /** matches any user within the specified authority */
    const RULE_TYPE_AUTHORITY='A'; 
    
    /** matches any user within the specified authority */
    const RULE_TYPE_USER='U'; 
    
    /** matches a group/authority combo */
    const RULE_TYPE_GROUP='G'; 
    
    /** matches everyone (including anonymous) */
    const RULE_TYPE_EVERYONE='E'; 
    
    /** special constant to match all values of a particular type */
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
    protected $ruleAction;

    /**
      * Rule type
      * @var string 
    */
    protected $ruleType;

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
            AccessControlList::RULE_TYPE_AUTHORITY,
            AccessControlList::RULE_TYPE_USER,
            AccessControlList::RULE_TYPE_GROUP,
            AccessControlList::RULE_TYPE_EVERYONE
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
        switch ($this->ruleType)
        {
            case self::RULE_TYPE_AUTHORITY:
                /* if the value is all then see if the userID and authority are set and it's a MATCH
                   this will NOT match an anonymous user 
                */
                if ($this->ruleValue==self::RULE_VALUE_ALL) {
                    if ($user->getUserID() && $user->getAuthenticationAuthority()) {
                        return $this->ruleAction;
                    }

                /* Otherwise see if the userID is set and the authority matches the rule value */
                } elseif ($user->getUserID() && $user->getAuthenticationAuthorityIndex()==$this->ruleValue) {
                    return $this->ruleAction;
                }
                
                break;
            case self::RULE_TYPE_USER:
                /* if the value is all then see if the userID is set
                   this will NOT match an anonymous user 
                */
                if ($this->ruleValue==self::RULE_VALUE_ALL) {
                    if ($user->getUserID()) {
                        return $this->ruleAction;
                    }
                } else { 
                    /* user values are specified as AUTHORITY|userID */
                    $values = explode("|", $this->ruleValue);
                    $authority = isset($values[0]) ? $values[0] : '';
                    $userID = isset($values[1]) ? $values[1] : '';
                    
                    /* see if the userID and authority match */
                    if ($user->getUserID()==$userID && $user->getAuthenticationAuthorityIndex()==$authority) {
                        return $this->ruleAction;
                    }
                }
                break;
            case self::RULE_TYPE_GROUP:
                /* Note: a group value of ALL is not valid */

                /* group values are specified as AUTHORITY|group */
                $values = explode("|", $this->ruleValue);
                $authority = isset($values[0]) ? $values[0] : '';
                $group = isset($values[1]) ? $values[1] : '';

                /* attempt to load the authority, then get the group */
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authority)) {
                    if ($group = $authority->getGroup($group)) {

                        /* see if the user is a member of the group */
                        if ($group->userIsMember($user)) {
                            return $this->ruleAction;
                        }
                    }
                }
                
                break;
            case self::RULE_TYPE_EVERYONE:
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
    public static function createFromString($aclString)
    {
        $values = explode(':', $aclString);
        if (count($values)==3) {
            return AccessControlList::factory($values[0], $values[1], $values[2]);
        } else {
            return false;
        }
    }
    
    /**
     * Instantiates an AccessControlList 
     * @param $ruleAction string @see AccessControlList::ruleActions() 
     * @param $ruleType string @see AccessControlList::ruleTypes() 
     * @param $ruleValue string 
	 * @return AccessControlList or false if there was an error
     */
    public static function factory($ruleAction, $ruleType, $ruleValue)
    {
        try {
            $AccessControlList = new AccessControlList($ruleAction, $ruleType, $ruleValue);
        } catch (Exception $e) {
            $AccessControlList = false;
        }
        return $AccessControlList;
    }
    
    /**
     * Constructor
     * @param $ruleAction string @see AccessControlList::ruleActions() 
     * @param $ruleType string @see AccessControlList::ruleTypes() 
     * @param $ruleValue string 
     *
     * Will throw an exception if invalid values are present
     */
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
