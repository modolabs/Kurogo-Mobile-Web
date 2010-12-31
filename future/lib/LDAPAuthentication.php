<?php

require_once(LIB_DIR . '/AuthenticationAuthority.php');
require_once(LIB_DIR . '/User.php');

class LDAPAuthentication extends AuthenticationAuthority
{
    private $ldapServer;
    private $ldapPort=389;
    private $ldapUserSearchBase;
    private $ldapGroupSearchBase;
    private $ldapAdminDN;
    private $ldapAdminPassword;
    private $fieldMap=array();
    private $ldapResource;
    
    private function connectToServer()
    {
        if (!$this->ldapResource) {
            $this->ldapResource = ldap_connect($this->ldapServer, $this->ldapPort);
            if ($this->ldapResource) {
                ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);
            } else {
                error_log("Error connecting to LDAP Server $this->ldadServer using port $this->ldapPort");
            }
        }
        
        return $this->ldapResource;
    }
        
    public function auth($login, $password, &$user)
    {
        $ldap = $this->connectToServer();
        if (!$ldap) {
            return AUTH_ERROR;
        }

        // we need to find the user first to get the DN 
        if (!$user = $this->getUser($login)) {
            return AUTH_USER_NOT_FOUND;
        }
     
        // attempt to bind as this user
        $auth = @ldap_bind($ldap, $user->getDN(), $password);
        if ($auth) {
            return AUTH_OK;
        } else {
            return AUTH_FAILED;
        }
    }
    
    public function getField($field)
    {
        return isset($this->fieldMap[$field]) ? $this->fieldMap[$field] : null;
    }
    
    public function ldapSearchBase($type)
    {
        switch ($type)
        {
            case 'user':
                if ($this->ldapUserSearchBase) {
                    return $this->ldapUserSearchBase;
                }
                break;
            case 'group':
                if ($this->ldapGroupSearchBase) {
                    return $this->ldapGroupSearchBase;
                }
                break;
        }
        
        //we can attempt to "discover" the search base in many cases, but this might have some performance implications
        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }

        $search = ldap_read($ldap, "", "(objectClass=*)", array('namingcontexts'));
        if ($search) {
            $result = ldap_get_entries($ldap, $search);
            if ($result['count']>0) {
                $this->ldapSearchBase = $result[0]['namingcontexts'][0];
                return $this->ldapSearchBase;
            } else {
                error_log("Unable to determine search base for LDAP Server $this->ldapServer: " . ldap_error($ldap));
                return false;
            }
            
        } else {
            error_log("Error discovering search base for LDAP Server $this->ldapServer: " . ldap_error($ldap));
            return false;
        }
    }

    public function getUser($login)
    {
        // don't try if it's empty
        if (empty($login)) {
            return new AnonymousUser();       
        }

        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }
        
        /*
            some servers don't permit anonymous searches so we need to bind as a valid user 
             Note: it does not, and should not be an account with administrative privilages. 
                    Usually a regular service account will suffice
        */
        if ($this->ldapAdminDN) {
            if (!ldap_bind($ldap, $this->ldapAdminDN, $this->ldapAdminPassword)) {
                error_log("Error binding to LDAP Server $this->ldapServer for $this->ldapAdminDN: " . ldap_error($ldap));
                return false;
            }
        }
        
        if (!$this->getField('uid')) {
            throw new Exception('LDAP uid field not specified');
        }

        $search = @ldap_search($ldap, $this->ldapSearchBase('user'), sprintf("%s=%s", $this->getField('uid'), $login));
        if ($search) {
            $result = @ldap_get_entries($ldap, $search);
            // see if we got a result back 
            if ($result['count']>0) {
                $entry = $result[0];
                $user = new LDAPUser($this);
                $user->setDN($entry['dn']);

                // single value attributes expect a maximum of one value
                $singleValueAttributes = $user->singleValueAttributes();
                for ($i=0; $i<$entry['count']; $i++) {
                    $attrib = $entry[$i];
                    
                    if (in_array($attrib, $singleValueAttributes)) {
                        $value = $entry[$attrib][0];
                    } else {
                        $value = $entry[$attrib];
                        unset($value['count']);
                    }
                    
                    $user->setAttribute($attrib, $value);
                }
                return $user;
            } else {
                return false;
                return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
            }
        } else {
            error_log("Error searching LDAP Server $this->ldapServer for uid=$login: " . ldap_error($ldap));
            return false;
        }
    }

    public function getGroup($group)
    {
        // don't try if it's empty
        if (empty($group)) {
            return false;
        }

        $ldap = $this->connectToServer();
        if (!$ldap) {
            return false;
        }
        
        /*
            some servers don't permit anonymous searches so we need to bind as a valid user 
             Note: it does not, and should not be an account with administrative privilages. 
                    Usually a regular service account will suffice
        */
        if ($this->ldapAdminDN) {
            if (!ldap_bind($ldap, $this->ldapAdminDN, $this->ldapAdminPassword)) {
                error_log("Error binding to LDAP Server $this->ldapServer for $this->ldapAdminDN: " . ldap_error($ldap));
                return false;
            }
        }

        if (!$this->getField('groupname')) {
            throw new Exception('LDAP group name field not specified');
        }

        if (!$this->getField('members')) {
            throw new Exception('LDAP group members field not specified');
        }
        
        $search = @ldap_search($ldap, $this->ldapSearchBase('group'), sprintf("%s=%s", $this->getField('groupname'), $group));
        if ($search) {
            $result = @ldap_get_entries($ldap, $search);
            // see if we got a result back 
            if ($result['count']>0) {
                $entry = $result[0];
                $group = new LDAPUserGroup($this);
                $group->setDN($entry['dn']);

                // single value attributes expect a maximum of one value
                $singleValueAttributes = $group->singleValueAttributes();
                for ($i=0; $i<$entry['count']; $i++) {
                    $attrib = $entry[$i];
                    
                    if (in_array($attrib, $singleValueAttributes)) {
                        $value = $entry[$attrib][0];
                    } else {
                        $value = $entry[$attrib];
                        unset($value['count']);
                    }
                    
                    $group->setAttribute($attrib, $value);
                }
                return $group;
            } else {
                return false;
            }
        } else {
            error_log("Error searching LDAP Server $this->ldapServer for group=$group: " . ldap_error($ldap));
            return false;
        }
    }
    
    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        $this->ldapServer = isset($args['HOST']) ? $args['HOST'] : null;
        $this->ldapPort = isset($args['PORT']) ? $args['PORT'] : 389;
        $this->ldapUserSearchBase = isset($args['USER_SEARCH_BASE']) ? $args['USER_SEARCH_BASE'] : null;
        $this->ldapGroupSearchBase = isset($args['GROUP_SEARCH_BASE']) ? $args['GROUP_SEARCH_BASE'] : null;

        //used if anonymous searches are not permitted (i.e. AD)
        $this->ldapAdminDN = isset($args['ADMIN_DN']) ? $args['ADMIN_DN'] : null;
        $this->ldapAdminPassword = isset($args['ADMIN_PASSWORD']) ? $args['ADMIN_PASSWORD'] : null;
        
        $this->fieldMap = array(
            'uid'=>'',
            'groupname'=>'',
            'members'=>'',
            'gid'=>''
        );
        
        foreach ($args as $arg=>$value) {
            if (preg_match("/^(user|group)_(.*?)_field$/", strtolower($arg), $bits)) {
                if (isset($this->fieldMap[$bits[2]])) {
                    $this->fieldMap[$bits[2]] = strtolower($value);
                }
            }
        }
        
        if ( empty($this->ldapServer) || empty($this->ldapPort)) {
            throw new Exception("Invalid LDAP Options");
        }
    }
}

class LDAPUser extends BasicUser
{
    protected $dn;
    
    public function getDN()
    {
        return $this->dn;
    }

    public function setDN($dn)
    {
        $this->dn = $dn;
    }
    
    public function setAttribute($attribute, $value)
    {
        switch ($attribute)
        {
            case 'mail':
                $this->setEmail($value);
                break;
            case 'sn':
                $this->setLastName($value);
                break;
            case 'givenname':
                $this->setFirstName($value);
                break;
            case $this->AuthenticationAuthority->getField('uid'):
                $this->setUserID($value);
                break;
            default:
                parent::setAttribute($attribute, $value);
                break;
        }
    }

    public function singleValueAttributes()
    {
        return array('dn','mail',$this->AuthenticationAuthority->getField('uid'),'sn','cn','givenname'); //there's more here. 
    }    

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('dn'));
    }    
}

class LDAPUserGroup extends BasicUserGroup
{
    protected $dn;
    
    public function getDN()
    {
        return $this->dn;
    }

    public function setDN($dn)
    {
        $this->dn = $dn;
    }

    public function singleValueAttributes()
    {
        return array('cn', $this->AuthenticationAuthority->getField('gid')); //there's more here. 
    }    

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('dn'));
    }    

    public function setAttribute($attribute, $value)
    {
        switch ($attribute)
        {
            case $this->AuthenticationAuthority->getField('groupname'):
                $this->setGroupName($value);
                break;
            case $this->AuthenticationAuthority->getField('gid'):
                $this->setGroupID($value);
                break;
            case $this->AuthenticationAuthority->getField('members'):
                $this->members=$value;
                break;
            default:
                parent::setAttribute($attribute, $value);
                break;
        }
    }

    public function getMembers()
    {
        //lazy load the members since performance might be a factor
        $members = array();
        foreach ($this->members as $userID) {
            if ($user = $this->AuthenticationAuthority->getUser($userID)) {
                $members[] = $user;
            }
        }
        
        return $members;
    }
    
    public function userIsMember(User $user)
    {
        //by definition LDAP groups can only contain users from the same authority
        if ($user->getAuthenticationAuthorityIndex()==$this->getAuthenticationAuthorityIndex()) {
            if (in_array($user->getUserID(), $this->members)) {
                return true;
            }
        }
        
        return false;
    }
}