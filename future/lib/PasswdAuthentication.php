<?php

// this authority uses a passed style file
class PasswdAuthentication extends AuthenticationAuthority
{
    private $passwdFile;
    private $users = array();
    
    private function loadData()
    {
        if ($this->users) {
            return;
        }
        
        $data = file_get_contents($this->passwdFile);
        $lines = explode(PHP_EOL, $data);
        $this->users = array();
        foreach ($lines as $line) {
            if ($user = $this->parseLine($line)) {
                $this->users[$user['userID']] = $user;
            }
        }
    }
    
    private function parseLine($line)
    {
        $line = trim($line);
        if (strlen($line)==0 || preg_match("/^#/", $line)) {
            return false;
        }

        $fields = explode(":", $line);
        if (!count($fields)==3) {
            return false;
        }
        
        $user = array(
            'userID'=>$fields[0],
            'md5'=>$fields[1],
            'email'=>$fields[2]
        );

        return $user;
    }
    
    public function auth($login, $password, &$user)
    {
        $this->loadData();
        if (isset($this->users[$login])) {
            if (md5($password) == $this->users[$login]['md5']) {
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
        $this->loadData();
        if (empty($login)) {
            return new AnonymousUser();       
        }

        if (isset($this->users[$login])) {
            $user = new BasicUser();
            $user->setUserID($this->users[$login]['userID']);
            $user->setEmail($this->users[$login]['email']);
            return $user;
        } else {
            return false;
            return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
        }
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        $this->passwdFile = isset($args['AUTHENTICATION_FILE']) ? $args['AUTHENTICATION_FILE'] : null;
        if (!file_exists($this->passwdFile)) {
            throw new Exception("Unable to load password file $this->passwdFile");
        }
    }
}
