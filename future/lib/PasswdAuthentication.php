<?php

// this authority uses a passed style file
class PasswdAuthentication extends AuthenticationAuthority
{
    private $passwdFile;
    private $groupFile;
    private $users = array();
    private $groups = array();
    
    private function loadUserData()
    {
        if ($this->users) {
            return;
        }
        
        $data = file_get_contents($this->passwdFile);
        $lines = explode(PHP_EOL, $data);
        $this->users = array();
        foreach ($lines as $line) {
            if ($user = $this->parseUserLine($line)) {
                $this->users[$user['userID']] = $user;
            }
        }
    }
    
    private function parseUserLine($line)
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

    private function loadGroupData()
    {
        if ($this->groups) {
            return;
        }
        
        $data = file_get_contents($this->groupFile);
        $lines = explode(PHP_EOL, $data);
        $this->groups = array();
        foreach ($lines as $line) {
            if ($group = $this->parseGroupLine($line)) {
                $this->groups[$group['group']] = $group;
            }
        }
    }
    
    private function parseGroupLine($line)
    {
        $line = trim($line);
        if (strlen($line)==0 || preg_match("/^#/", $line)) {
            return false;
        }

        $fields = explode(":", $line);
        if (!count($fields)==3) {
            return false;
        }
        
        $group = array(
            'group'=>$fields[0],
            'gid'=>$fields[1],
            'members'=>explode(",",$fields[2])
        );
        foreach ($group['members'] as $i=>$member) {
            $group['members'][$i] = trim($member);
        }

        return $group;
    }
    
    public function auth($login, $password, &$user)
    {
        $this->loadUserData();
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
        $this->loadUserData();
        if (empty($login)) {
            return new AnonymousUser();       
        }

        if (isset($this->users[$login])) {
            $user = new BasicUser($this);
            $user->setUserID($this->users[$login]['userID']);
            $user->setEmail($this->users[$login]['email']);
            return $user;
        } else {
            return false;
            return AUTH_USER_NOT_FOUND; // not sure which one is correct yet
        }
    }

    public function getGroup($groupName)
    {
        $this->loadGroupData();
        if (strlen($groupName)==0) {
            return false;
        }

        if (isset($this->groups[$groupName])) {
            $group = new BasicUserGroup($this);
            $group->setGroupID($this->groups[$groupName]['gid']);
            $group->setGroupName($this->groups[$groupName]['group']);
            
            $members = is_array($this->groups[$groupName]['members']) ? $this->groups[$groupName]['members'] : array();
            $groupMembers = array();
            foreach ($members as $user) {
                $userPieces = explode("|", $user);
                $authority = count($userPieces)==2 ? $userPieces[0] : $this->getAuthorityIndex();
                $userID = count($userPieces)==2 ? $userPieces[1] : $userPieces[0];
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authority)) {
                    if ($user = $authority->getUser($userID)) {
                        $groupMembers[] = $user;
                    }
                }
            }
            $group->setMembers($groupMembers);
            return $group;
        } else {
            return false;
        }
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        $this->passwdFile = isset($args['USER_FILE']) ? $args['USER_FILE'] : null;
        $this->groupFile = isset($args['GROUP_FILE']) ? $args['GROUP_FILE'] : null;
        if (!file_exists($this->passwdFile)) {
            throw new Exception("Unable to load password file $this->passwdFile");
        }
    }
}
