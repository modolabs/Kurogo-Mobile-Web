<?php

// this authority uses a database 
class DatabaseAuthentication extends AuthenticationAuthority
{
    protected $connection;
    protected $dbUserTable = 'users';
    protected $dbUserIDField = 'userID';
    protected $dbPasswordField = 'password';
    protected $dbEmailField = 'email';
    
    public function auth($login, $password, &$user)
    {
        $sql = sprintf("SELECT `%s` FROM `%s` WHERE `%s`=?", $this->dbPasswordField, $this->dbUserTable, $this->dbUserIDField);
        $result = $this->connection->query($sql, array($login));
        if ($row = $result->fetch()) {
            if (md5($password) == $row[$this->dbPasswordField]) {
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

        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=?", $this->dbUserTable, $this->dbUserIDField);
        $result = $this->connection->query($sql, array($login));
        if ($row = $result->fetch()) {
            $user = new BasicUser($this);
            $user->setUserID($row[$this->dbUserIDField]);
            $user->setEmail($row[$this->dbEmailField]);
            return $user;
        } else {
            return false;
            return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
        }
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge($GLOBALS['siteConfig']->getSection('database'), $args);
        }
        
        $this->connection = new db($args);
        if (isset($args['DB_USER_TABLE'])) {
            $this->dbUserIDField = $args['DB_USER_TABLE'];
        }

        if (isset($args['DB_USERID_FIELD'])) {
            $this->dbUserIDField = $args['DB_USERID_FIELD'];
        }

        if (isset($args['DB_PASSWORD_FIELD'])) {
            $this->dbPasswordField = $args['DB_PASSWORD_FIELD'];
        }

        if (isset($args['DB_EMAIL_FIELD'])) {
            $this->dbEmailField = $args['DB_EMAIL_FIELD'];
        }
    }
}
