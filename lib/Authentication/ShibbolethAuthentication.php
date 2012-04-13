<?php

class ShibbolethAuthentication extends AuthenticationAuthority
{
    protected $userClass = 'ShibbolethUser';
    protected $fieldMap=array();

    protected function auth($login, $password, &$user) {

        $user = new $this->userClass($this);
        if ($user->loadUserData()) {
            return AUTH_OK;
        }
        
        $user = null;
        return AUTH_FAILED;
    }

    public function getUser($login) {
        $user = new $this->userClass($this);
        $user->loadUserData();
        if ($user->getUserID() == $login) {
            return $user;
        }

        return false;
    }

    public function getGroup($group) {
      return false;
    }

    public function validate(&$error) {
       return true;
    }

    public function getField($field)
    {
        return isset($this->fieldMap[$field]) ? $this->fieldMap[$field] : null;
    }
    
    public function init($args) {
        parent::init($args);

        // set field map using SHIB_XXX_FIELD = "" maps to $_SERVER values
        foreach ($args as $arg=>$value) {
            if (preg_match("/^shib_(email|firstname|lastname|fullname)_field$/", strtolower($arg), $bits)) {
                $key = strtolower($bits[1]);
                $this->fieldMap[$key] = $value;
            }
        }
    }
}

class ShibbolethUser extends User
{
    public function loadUserData() {
        if (isset($_SERVER['REMOTE_USER'])) {
            $this->setUserID($_SERVER['REMOTE_USER']);
            if ( ($field = $this->AuthenticationAuthority->getField('email')) && isset($_SERVER[$field])) {
                $this->setEmail($_SERVER[$field]);
            }
            
            if ( ($field = $this->AuthenticationAuthority->getField('fullname')) && isset($_SERVER[$field])) {
                $this->setFullName($_SERVER[$field]);
            }

            if ( ($field = $this->AuthenticationAuthority->getField('firstname')) && isset($_SERVER[$field])) {
                $this->setFirstName($_SERVER[$field]);
            }

            if ( ($field = $this->AuthenticationAuthority->getField('lastname')) && isset($_SERVER[$field])) {
                $this->setLastName($_SERVER[$field]);
            }
            
            return true;
        }
    }
}