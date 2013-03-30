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
  * @package Authentication
  */

/**
  * @package Authentication
  */
class GoogleAuthentication extends OAuthAuthentication
{
    protected $OAuthProviderClass='GoogleOAuthProvider';
    protected $authorityClass = 'google';
    protected $userClass='GoogleUser';
    
    public function validate(&$error) {
        return true;
    }

    protected function reset($hard=false)
    {
        parent::reset($hard);
        if ($hard) {
            // this where we would log out of google
        }
    }

    protected function getUserFromArray(array $array) {
        $user = new $this->userClass($this);
        if ($user->setVars($array)) {
            $this->cacheUserArray($user->getUserID(), $array);
            return $user;
        }
        
        return false;
    }

    protected function cacheUserArray($login, array $array) {
        $umask = umask(0077);
        $return = file_put_contents($this->cacheFile($login), serialize($array));
        umask($umask);
        return $return;
    }

    protected function cacheFile($login) {
        $cacheDir = CACHE_DIR . '/GoogleOpenID' ;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }
        return $cacheDir . "/" . md5($login);
    }

    public function getUser($login) {

        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        $filename = $this->cacheFile($login) ;
        $user = false;
        if (file_exists($filename)) {
            if ($array = unserialize(file_get_contents($filename))) {
                $user = $this->getUserFromArray($array);
            }
        }

        return $user;
    }
}

/**
  * @package Authentication
  */
class GoogleUser extends OAuthUser
{

    protected function valueKeyForTypeKey($key) {
        if (preg_match("/^openid_(.*?)_type_(.*?)$/", $key, $matches)) {
            return sprintf("openid_%s_value_%s", $matches[1], $matches[2]);
        }
        
        return null;
    }
    
    public function setVars(array $array) {
    
        if (!isset($array['openid_identity'])) {
            return false;
        }
        
        $this->setUserID($array['openid_identity']);
        
        if ( ($type_key = array_search('http://schema.openid.net/contact/email', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setEmail($array[$value_key]);
            }
        }

        if ( ($type_key = array_search('http://axschema.org/namePerson/first', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setFirstName($array[$value_key]);
            }
        }

        if ( ($type_key = array_search('http://axschema.org/namePerson/last', $array)) !== false) {
            if ( ($value_key = $this->valueKeyForTypeKey($type_key)) && isset($array[$value_key])) {
                $this->setLastName($array[$value_key]);
            }
        }

        return true;
    }
}
