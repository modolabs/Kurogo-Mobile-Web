<?php
/**
 * User
 * @package Authentication
 */

/**
 * User class
 * @package Authentication
 */
abstract class User
{
    protected $userID;
    protected $AuthenticationAuthority;
    protected $email;
    protected $FirstName;
    protected $LastName;
    protected $FullName;
    protected $userData;
    
    protected $attributes=array();
    
    public function __toString() {
        return $this->getAuthenticationAuthorityIndex() . ':' . $this->getUserID();
    }
    
    public function getUserID()
    {
        return $this->userID;
    }

    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setUserID($userID)
    {
        $this->userID = $userID;
    }
    
    public function setAuthenticationAuthority(AuthenticationAuthority $AuthenticationAuthority)
    {
        $this->AuthenticationAuthority = $AuthenticationAuthority;
    }

    public function getAuthenticationAuthority()
    {
        return $this->AuthenticationAuthority;
    }

    public function getAuthenticationAuthorityIndex()
    {
        if ($authority = $this->getAuthenticationAuthority()) {
            return $authority->getAuthorityIndex();
        } 
        
        return null;
    }
    
    protected function standardAttributes()
    {
        return array(
            'userid', 'email'
        );
    }
    
    public function setAttribute($attribute, $value)
    {
        if (in_array($attribute, $this->standardAttributes())) {
            $method = "set" . $attribute;
            return $this->$method($value);
        } else {
            $this->attributes[$attribute] = $value;
        }
    
    }
    
    public function getAttribute($attribute)
    {
        if (in_array(strtolower($attribute), $this->standardAttributes())) {
            $method = "get" . $attribute;
            return $this->$method();
        } elseif (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
    }
    
    public function setFirstName($FirstName)
    {
        $this->FirstName = $FirstName;
    }

    public function setLastName($LastName)
    {
        $this->LastName = $LastName;
    }

    public function setFullName($FullName)
    {
        $this->FullName = $FullName;
    }
    
    public function getFullName()
    {
        if (!empty($this->FullName)) {
            return $this->FullName;
        } elseif (!empty($this->FirstName) || !empty($this->LastName)) {
            return trim(sprintf("%s %s", $this->FirstName, $this->LastName));
        } else {
            return $this->getUserID();
        }
    }
    public function getFirstName()
    {
        return $this->FirstName;
    }

    public function getLastName()
    {
        return $this->LastName;
    }
    
    public function __construct(AuthenticationAuthority $AuthenticationAuthority)
    {
        $this->setAuthenticationAuthority($AuthenticationAuthority);
    }
    
    public function isMemberOfGroup(UserGroup $group)
    {
        return $group->userIsMember($this);
    }
    
    public function getUserHash() {
        return md5(SITE_KEY . $this->getAuthenticationAuthorityIndex() . $this->getUserID());
    }
    
    public function getSessionData() {
        return array();
    }

    public function setSessionData($data) {
    
    }

    private function getUserDataFolder() {
        return CACHE_DIR . "/UserData";
    }
    
    private function getUserDataFile() {
        return $this->getUserDataFolder() . "/" . $this->getUserHash();
    }
    
    public function setCredentials($credentials) {
        try {
            $value = Kurogo::encrypt($credentials);
        } catch (KurogoException $e) {
            $value = $credentials;
        }
    
        $this->setUserData('KurogoCredentialsCache', $value);
    }
    
    public function getCredentials() {
        $value = $this->getUserData('KurogoCredentialsCache');
        try {
            $credentials = Kurogo::decrypt($value);
        } catch (KurogoException $e) {
            $credentials = $value;
        }
        return $credentials;
    }
    
    public function setUserData($key, $value) {
        if (!is_dir($this->getUserDataFolder())) {
            if (!mkdir($this->getUserDataFolder(), 0700, true)) {
                throw new Execption("Error creating userData Folder" , $this->getUserDataFolder());
            }
        }
        
        if (!preg_match("/^[A-Za-z0-9_-]+$/", $key)) {
            throw new Execption("Invalid key $key. Keys must be alphanumeric");
        }

        $userData = $this->getUserData();
        if (isset($userData[$key]) && $userData[$key]===$value) {
            //no change
            return true;
        }
        $userData[$key] = $value;
        $umask = umask(0077);
        file_put_contents($this->getUserDataFile(), serialize($userData));
        umask($umask);
        $this->userData = $userData;
    }

    public function getUserData($key=null) {
        if (is_null($this->userData)) {
            if (is_file($this->getUserDataFile())) {
                $this->userData = unserialize(file_get_contents($this->getUserDataFile()));
            } else {
                $this->userData = array();
            }
        }
        
        if (strlen($key)) {
            return isset($this->userData[$key]) ? $this->userData[$key] : null;
        } else {
            return $this->userData;
        }
    }
}
