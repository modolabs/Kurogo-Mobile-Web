<?php

require_once(LIB_DIR . '/AuthenticationAuthority.php');
require_once(LIB_DIR . '/User.php');

// this authority uses a passed style file
class LDAPAuthentication extends AuthenticationAuthority
{
    private $ldapServer;
    private $ldapPort=389;
    private $ldapSearchBase;
    private $ldapAdminDN;
    private $ldapAdminPassword;
    private $ldapUIDField='uid';
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
        $auth = ldap_bind($ldap, $user->getDN(), $password);
        if ($auth) {
            return AUTH_OK;
        } else {
            return AUTH_FAILED;
        }
    }
    
    public function ldapSearchBase()
    {
        if ($this->ldapSearchBase) {
            return $this->ldapSearchBase;
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

        $search = @ldap_search($ldap, $this->ldapSearchBase(), sprintf("%s=%s", $this->ldapUIDField, $login));
        if ($search) {
            $result = @ldap_get_entries($ldap, $search);
            // see if we got a result back 
            if ($result['count']>0) {
                $entry = $result[0];
                $user = new LDAPUser();
                $user->setLdapUIDField($this->ldapUIDField);
                $user->setDN($entry['dn']);

                // single value attributes expect a maximum of one value
                $singleValueAttributes = LDAPUser::singleValueAttributes();
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

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        $this->ldapServer = isset($args['AUTHENTICATION_SERVER']) ? $args['AUTHENTICATION_SERVER'] : null;
        $this->ldapPort = isset($args['AUTHENTICATION_SERVER_PORT']) ? $args['AUTHENTICATION_SERVER_PORT'] : 389;
        $this->ldapSearchBase = isset($args['AUTHENTICATION_LDAP_SEARCH_BASE']) ? $args['AUTHENTICATION_LDAP_SEARCH_BASE'] : null;

        //used if anonymous searches are not permitted (i.e. AD)
        $this->ldapAdminDN = isset($args['AUTHENTICATION_LDAP_ADMIN_DN']) ? $args['AUTHENTICATION_LDAP_ADMIN_DN'] : null;
        $this->ldapAdminPassword = isset($args['AUTHENTICATION_LDAP_ADMIN_PASSWORD']) ? $args['AUTHENTICATION_LDAP_ADMIN_PASSWORD'] : null;

        //field use store the login name of the user. Typically uid in "regular" ldap directories. It's cn in active directory
        $this->ldapUIDField = isset($args['AUTHENTICATION_LDAP_USER_UID']) ? $args['AUTHENTICATION_LDAP_USER_UID'] : 'uid';
        
        if ( empty($this->ldapServer) || empty($this->ldapPort)) {
            throw new Exception("Invalid LDAP Options");
        }
    }
}

class LDAPUser extends BasicUser
{
    protected $ldapUIDField='uid';
    protected $dn;
    
    public function setLdapUIDField($field)
    {
        $this->ldapUIDField = $field;
    }

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
            case $this->ldapUIDField:
                $this->setUserID($value);
                break;
            default:
                parent::setAttribute($attribute, $value);
                break;
        }
    }

    public function singleValueAttributes()
    {
        return array('dn','mail','uid','sn','cn','givenname'); //there's more here. 
    }    

    protected function standardAttributes()
    {
        return array_merge(parent::standardAttributes(), array('dn','mail','sn','givenname'));
    }    
}
