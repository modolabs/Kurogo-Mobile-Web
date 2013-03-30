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
 * Database Authentication
 * @package Authentication
 */
 
 Kurogo::includePackage('db');

/**
 * Authentication Authority based on values in a database
 * @see db
 * @package Authentication
 */
class DatabaseAuthentication extends AuthenticationAuthority
{
    protected $authorityClass = 'database';
    protected $userClass='DatabaseUser';
    protected $groupClass='DatabaseUserGroup';
    protected $connection;
    protected $tableMap=array();
    protected $fieldMap=array();
    protected $hmac = false;
    protected $hashAlgo='md5';
    protected $hashSaltBefore='';
    protected $hashSaltFieldBefore='';
    protected $hashSaltAfter='';
    protected $hashSaltFieldAfter='';

    protected function validUserLogins()
    {
        return array('FORM', 'NONE');
    }
    
    protected function hashPassword($password) {
        return $this->hmac ? hash_hmac($this->hashAlgo, $password, $this->hashKey) : hash($this->hashAlgo, $password);
    }
    
    protected function getSaltedValue($password, $data) {
        $hashSaltBefore = '';
        $hashSaltAfter = '';
        if ($this->hashSaltBefore) {
            $hashSaltBefore = $this->hashSaltBefore;
        } elseif ($this->hashSaltFieldBefore) {
            $hashSaltBefore = $data[$this->hashSaltFieldBefore];
        }

        if ($this->hashSaltAfter) {
            $hashSaltAfter = $this->hashSaltAfter;
        } elseif ($this->hashSaltFieldAfter) {
            $hashSaltAfter = $data[$this->hashSaltFieldAfter];
        }
        
        return $hashSaltBefore . $password . $hashSaltAfter;
    }

    public function auth($login, $password, &$user)
    {
        $fields = array(
            '`'.$this->getField('user_password').'`',
        );
        
        if ($this->hashSaltFieldAfter) {
            $fields[] = $this->hashSaltFieldAfter;
        }

        if ($this->hashSaltFieldBefore) {
            $fields[] = $this->hashSaltFieldBefore;
        }
        
        $sql = sprintf("SELECT %s FROM `%s` WHERE (`%s`=? OR `%s`=?)", implode(',',$fields), $this->getTable('user'), $this->getField('user_userid'), $this->getField('user_email'));
        $result = $this->connection->query($sql, array($login, $login));
        if ($row = $result->fetch()) {
        
            $pwd = $this->getSaltedValue($password, $row);

            if ($this->hashPassword($pwd) == $row[$this->getField('user_password')]) {
                $user = $this->getUser($login);
                return AUTH_OK;
            } else {
                return AUTH_FAILED;
            }
        } else {
            return AUTH_USER_NOT_FOUND;
        }
    }

    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE (`%s`=? or `%s`=?)", $this->getTable('user'), $this->getField('user_userid'), $this->getField('user_email'));
        $result = $this->connection->query($sql, array($login, $login));
        if ($row = $result->fetch()) {
            $user = new $this->userClass($this);
            $user->setUserID($row[$this->getField('user_userid')]);
            $user->setEmail($row[$this->getField('user_email')]);
            if (isset($row[$this->getField('user_fullname')])) {
                $user->setFullName($row[$this->getField('user_fullname')]);
            }
            if (isset($row[$this->getField('user_firstname')])) {
                $user->setFirstName($row[$this->getField('user_firstname')]) ;
            }
            if (isset($row[$this->getField('user_lastname')])) {
                $user->setLastName($row[$this->getField('user_lastname')]) ;
            }

            return $user;
        } else {
            return false;
        }
    }

    public function getGroup($group)
    {
        if (strlen($group)==0) {
            return false;
        }

        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=?", $this->getTable('group'), $this->getField('group_groupname'));
        $result = $this->connection->query($sql, array($group));
        if ($row = $result->fetch()) {
            $group = new $this->groupClass($this);
            $group->setGroupID($row[$this->getField('group_gid')]);
            $group->setGroupName($row[$this->getField('group_groupname')]);
            return $group;
        } else {
            return false;
        }
    }

    public function init($args)
    {
        parent::init($args);
        $args = is_array($args) ? $args : array();
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge(Kurogo::getSiteSection('database'), $args);
        }
        
        $this->connection = new db($args);

        $this->tableMap = array(
            'user'=>'users',
            'group'=>'groups',
            'groupmembers'=>'groupmembers'
        );

        $this->fieldMap = array(
            'user_userid'=>'userID',
            'user_password'=>'password',
            'user_email'=>'email',
            'user_firstname'=>'firstname',
            'user_lastname'=>'lastname',
            'user_fullname'=>'fullname',
            'group_groupname'=>'group',
            'group_gid'=>'gid',
            'group_groupmember'=>'gid',
            'groupmember_group'=>'gid',
            'groupmember_user'=>'userID',
            'groupmember_authority'=>''
        );
        
        foreach ($args as $arg=>$value) {
            if (preg_match("/^db_(user|group|groupmember)_(.*?)_field$/", strtolower($arg), $bits)) {
                $key = sprintf("%s_%s", $bits[1], $bits[2]);
                if (isset($this->fieldMap[$key])) {
                    $this->fieldMap[$key] = $value;
                }
            } elseif (preg_match("/^db_(.*?)_table$/", strtolower($arg), $bits)) {
                $key = $bits[1];
                if (isset($this->tableMap[$key])) {
                    $this->tableMap[$key] = $value;
                }
            } else {
                switch ($arg)
                {
                    case 'DB_USER_PASSWORD_HASH':
                        if (preg_match("/^hmac_(.+)$/", $value, $bits)) {
                            if (!isset($args['DB_USER_PASSWORD_KEY'])) {
                                throw new KurogoConfigurationException ("HMAC hash requires DB_USER_PASSWORD_KEY");
                            }

                            $this->hmac = true;
                            $value = $bits[1];
                        }
                        
                        if (!in_array($value, hash_algos())) {
                            throw new KurogoConfigurationException ("Hashing algorithm $value not available");
                        }
                        $this->hashAlgo = $value;
                        break;
                    case 'DB_USER_PASSWORD_KEY':
                        $this->hashKey = $value;
                        break;

                    case 'DB_USER_PASSWORD_SALT':
                    case 'DB_USER_PASSWORD_SALT_BEFORE':
                        $this->hashSaltBefore = $value;
                        break;
                    
                    case 'DB_USER_PASSWORD_SALT_AFTER':
                        $this->hashSaltAfter = $value;
                        break;

                    case 'DB_USER_PASSWORD_SALT_FIELD_BEFORE':
                        $this->hashSaltFieldBefore = $value;
                        break;
                        
                    case 'DB_USER_PASSWORD_SALT_FIELD_AFTER':
                        $this->hashSaltFieldAfter = $value;
                        break;

                    case 'DB_GROUP_GROUPMEMBER_PROPERTY':
                        if (!in_array($value, array('group','gid'))) {
                            throw new KurogoConfigurationException("Invalid value for DB_GROUP_GROUPMEMBER_PROPERTY $value. Should be gid or group");
                        }
                        $this->fieldMap['group_groupmember'] = $value;
                        break;
                }
            }
        }
        
    }
    
    public function validate(&$error) {
        if ($this->userLogin !='NONE') {
            if (!$result = $this->connection->query(sprintf("SELECT %s, %s, %s FROM %s", $this->getField('user_password'), $this->getField('user_email'), $this->getField('user_userid'), $this->getTable('user')), array(), true)) {
                $error = new KurogoError(1, "Error connecting", "Error validating user table (" . $this->connection->getLastError() . ")");
                return false;
            }
        }
        
        return true;
    }
    
    public function getTable($table)
    {
        return isset($this->tableMap[$table]) ? $this->tableMap[$table] : null;
    }

    public function getField($field)
    {
        return isset($this->fieldMap[$field]) ? $this->fieldMap[$field] : null;
    }
    
    public function connection()
    {
        return $this->connection;
    }
    
}

/**
 * Database User
 * @package Authentication
 */
class DatabaseUser extends User
{
}

/**
 * Database Group
 * @package Authentication
 */
class DatabaseUserGroup extends UserGroup
{
    
    public function getMembers()
    {
        $property = $this->AuthenticationAuthority->getField('group_groupmember');
        if ($this->AuthenticationAuthority->getField('group_authority')) {
            $sql = sprintf("SELECT `%s`,`%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('groupmember_authority'),
                $this->AuthenticationAuthority->getField('groupmember_user'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group')
            );
        } else {
            $sql = sprintf("SELECT `%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('groupmember_user'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group')
            );
        }
        
        $connection = $this->AuthenticationAuthority->connection();
        $result = $connection->query($sql, array($this->$property));
        $members = array();
        while ($row = $result->fetch()) {
            $userID = $row[$this->AuthenticationAuthority->getField('userID')];
            if ($this->AuthenticationAuthority->getField('groupmember_authority')) {
                if (!$authority = AuthenticationAuthority::getAuthenticationAuthority($row[$this->AuthenticationAuthority->getField('authority')])) {
                    continue;
                }
            } else {
                $authority = $this->getAuthenticationAuthority();
            }

            if ($user = $authority->getUser($userID)) {
                $members[] = $user;
            }
        }

        return $members;
    }
    
    public function userIsMember(User $user)
    {
        $property = $this->AuthenticationAuthority->getField('group_groupmember');
        if ($this->AuthenticationAuthority->getField('groupmember_authority')) {
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group'),
                $this->AuthenticationAuthority->getField('groupmember_authority'),
                $this->AuthenticationAuthority->getField('groupmember_user')
            );
            $parameters = array($this->$property, $user->getAuthenticationAuthorityIndex(), $user->getUserID());
        } elseif ($user->getAuthenticationAuthorityIndex()==$this->getAuthenticationAuthorityIndex()) {
            //if we don't use authorities in this database then make sure the user is from the same authority
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('groupmember_group'),
                $this->AuthenticationAuthority->getField('groupmember_user')
            );
            $parameters = array($this->$property, $user->getUserID());
        } else {
            //user is from another authority
            return false;
        }

        $connection = $this->AuthenticationAuthority->connection();
        $result = $connection->query($sql, $parameters); 
        if ($row = $result->fetch()) {
            return true;
        }
        return false;
    }
}
