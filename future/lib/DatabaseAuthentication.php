<?php

// this authority uses a database 
class DatabaseAuthentication extends AuthenticationAuthority
{
    protected $connection;
    protected $tableMap=array();
    protected $fieldMap=array();
    
    public function auth($login, $password, &$user)
    {
        $sql = sprintf("SELECT `%s` FROM `%s` WHERE `%s`=?", $this->getField('password'), $this->getTable('user'), $this->getField('userid'));
        $result = $this->connection->query($sql, array($login));
        if ($row = $result->fetch()) {
            if (md5($password) == $row[$this->getField('password')]) {
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

        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=?", $this->getTable('user'), $this->getField('userid'));
        $result = $this->connection->query($sql, array($login));
        if ($row = $result->fetch()) {
            $user = new BasicUser($this);
            $user->setUserID($row[$this->getField('userid')]);
            $user->setEmail($row[$this->getField('email')]);
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

        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=?", $this->getTable('group'), $this->getField('groupname'));
        $result = $this->connection->query($sql, array($group));
        if ($row = $result->fetch()) {
            $group = new DatabaseUserGroup($this);
            $group->setGroupID($row[$this->getField('gid')]);
            $group->setGroupName($row[$this->getField('groupname')]);
            return $group;
        } else {
            return false;
        }
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge($GLOBALS['siteConfig']->getSection('database'), $args);
        }
        
        $this->connection = new db($args);

        $this->tableMap = array(
            'user'=>'users',
            'group'=>'groups',
            'groupmembers'=>'groupmembers'
        );

        $this->fieldMap = array(
            'userid'=>'userID',
            'password'=>'password',
            'email'=>'email',
            'groupname'=>'group',
            'gid'=>'gid',
            'authority'=>''
        );
        
        foreach ($args as $arg=>$value) {
            if (preg_match("/^(user|group)_(.*?)_(field|table)$/", strtolower($arg), $bits)) {
                if (isset($this->fieldMap[$bits[2]])) {
                    $this->fieldMap[$bits[2]] = $value;
                }
            }
        }
        
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

class DatabaseUserGroup extends UserGroup
{
    public function getMembers()
    {
        if ($this->AuthenticationAuthority->getField('authority')) {
            $sql = sprintf("SELECT `%s`,`%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('authority'),
                $this->AuthenticationAuthority->getField('userid'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('gid')
            );
        } else {
            $sql = sprintf("SELECT `%s` FROM `%s` WHERE %s=?",
                $this->AuthenticationAuthority->getField('userid'),
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('gid')
            );
        }
        
        $connection = $this->AuthenticationAuthority->connection();
        $result = $connection->query($sql, array($this->gid));
        $members = array();
        while ($row = $result->fetch()) {
            $userID = $row[$this->AuthenticationAuthority->getField('userID')];
            $authority = $row[$this->AuthenticationAuthority->getField('authority')];
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authority)) {
                if ($user = $authority->getUser($userID)) {
                    $members[] = $user;
                }
            }
        }

        return $members;
    }
    
    public function userIsMember(User $user)
    {
        if ($this->AuthenticationAuthority->getField('authority')) {
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('gid'),
                $this->AuthenticationAuthority->getField('authority'),
                $this->AuthenticationAuthority->getField('userid')
            );
            $parameters = array($this->gid, $user->getAuthenticationAuthorityIndex(), $user->getUserID());
        } elseif ($user->getAuthenticationAuthorityIndex()==$this->getAuthenticationAuthorityIndex()) {
            //if we don't use authorities in this database then make sure the user is from the same authority
            $sql = sprintf("SELECT * FROM `%s` WHERE %s=? AND %s=?", 
                $this->AuthenticationAuthority->getTable('groupmembers'),
                $this->AuthenticationAuthority->getField('gid'),
                $this->AuthenticationAuthority->getField('userid')
            );
            $parameters = array($this->gid, $user->getUserID());
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
