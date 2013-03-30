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

Kurogo::includePackage('Authentication');

/**
  * @package Authentication
  */
abstract class Session
{
    const SESSION_GC_TIME = 21600;  
    const TOKEN_COOKIE='lt';
    const USERHASH_COOKIE='lh';
    const API_TOKEN_COOKIE='alt';
    const API_USERHASH_COOKIE='alh';
    protected $session_id;
    protected $users = array();
    protected $login_token;
    protected $maxIdleTime=0;
    protected $remainLoggedIn = false;
    protected $remainLoggedInTime=0;
    protected $saveUsername = false;
    protected $saveUsernameTime = 0;
    protected $authorityIndex;
    protected $loginCookiePath;
    protected $apiCookiePath;
    protected $debugMode = false;
    
    abstract protected function getLoginTokenData($token);
    abstract protected function clearLoginTokenData($token);
    abstract protected function saveLoginTokenData($new_login_token, $expires, $data);
    
    public static function getSessionClasses() {
        return array(
            'SessionFiles'=>"Flat files",
            'SessionDB'=>'Database'
        );
    }
    
    public static function factory($sessionClass, $args=array()) {
        $args = is_array($args) ? $args : array();

        if (!class_exists($sessionClass)) {
            throw new KurogoConfigurationException("Session class $sessionClass not defined");
        }
        
        Kurogo::log(LOG_DEBUG, "Initializing session class $sessionClass", 'session');
        $session = new $sessionClass;
        
        if (!$session instanceOf Session) {
            throw new KurogoConfigurationException("$sessionClass is not a subclass of Session");
        }

        $session->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        $session->initialize($args);

        return $session;
    }
    
    private function initialize($args) {
        $this->init($args);
        
        session_start();
        $this->session_id = session_id();
        $this->login_token = isset($_COOKIE[self::TOKEN_COOKIE]) ? $_COOKIE[self::TOKEN_COOKIE] : '';

        if ($user = $this->getCurrentSessionUser()) {
            $this->setUser($user);
        } elseif ($user = $this->getUserFromLoginCookie()) {
            $this->setUser($user);
            $this->remainLoggedIn = true;
            $this->setLoginCookie();
        }
        $this->setSessionVars();
    }
    
    protected function getUserFromLoginCookie() {
        return $this->getLoginCookie();
    }
    
    protected function getCurrentSessionUser() {
        
        $users = array();
        if (isset($_SESSION['users']) && is_array($_SESSION['users'])) {
            
            $lastPing = isset($_SESSION['ping']) ? $_SESSION['ping'] : 0;
            $diff = time() - $lastPing;
            
            // see if max idle time has been reached
            if ( $this->maxIdleTime && ($diff > $this->maxIdleTime)) {
                Kurogo::log(LOG_NOTICE, "User was logged off after $diff seconds", 'session');
                $this->logoutAllUsers();
                // right now the user is just logged off, but we could show and error if necessary.
            } else {
                $ok = false;
                foreach ($_SESSION['users'] as $userData) {
                    if ($authority = AuthenticationAuthority::getAuthenticationAuthority($userData['auth'])) {
                        $authority->setDebugMode($this->debugMode);

                        if ($user = $authority->getUser($userData['auth_userID'])) {
                            $users[] = $user;
                            $ok = true;
                        } else {
                            Kurogo::log(LOG_WARNING, "Error trying to load user " . $userData['auth_userID'], 'session');
                        }
                    }
                }
            }
        }
        
        return $users;
    }
    
    protected function init($args) {
    
        //load arguments
        $this->maxIdleTime = isset($args['AUTHENTICATION_IDLE_TIMEOUT']) ? intval($args['AUTHENTICATION_IDLE_TIMEOUT']) : 0;
        $this->remainLoggedInTime = isset($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) ? intval($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) : 0;
        $this->saveUsernameTime = isset($args['AUTHENTICATION_SAVE_USERNAME']) ? intval($args['AUTHENTICATION_SAVE_USERNAME']) : 0;
        $loginModuleID = isset($args['LOGIN_MODULE']) ? $args['LOGIN_MODULE'] : 'login';
        $this->loginCookiePath = URL_BASE . $loginModuleID;
        $this->apiCookiePath = URL_BASE . API_URL_PREFIX . '/' . $loginModuleID;
        $this->debugMode = isset($args['DEBUG_MODE']) ? $args['DEBUG_MODE'] : false;
                
        if (!isset($_SESSION)) {
            // set session ini values
            ini_set('session.name', SITE_KEY);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_path', COOKIE_PATH);
            ini_set('session.gc_maxlifetime', self::SESSION_GC_TIME);
        }
    }    
    
    /**
      * returns whether a user is logged in or not
      * @return boolean
      */
    public function isLoggedIn($authority=null) {
        if ($authority) {
            $user = $this->getUser($authority);
            return strlen($user->getUserID())>0 ? true : false;
        } else {
            return count($this->users) > 0;
        }
    }
    
    /**
      * sets the active user
      * @param User $user
      */
    protected function setUser($user) {
        if (is_array($user)) {
            foreach ($user as $_user) {
                $this->setUser($_user);
            }
            return;
        } elseif ($user instanceOf User) {
            if ($auth = $user->getAuthenticationAuthorityIndex()) {
                Kurogo::log(LOG_DEBUG, "Setting user to $auth:".$user->getUserID(), 'session');
                $this->users[$auth] = $user;
                $this->setSessionVars();
            }
        } 
    }
    
    public function setRemainLoggedIn($remainLoggedIn) {
        $this->remainLoggedIn = $remainLoggedIn ? true : false;
    }
    
    public function setSaveUsername($saveUsername) {
        $this->saveUsername = $saveUsername ? true : false;
    }

    public function setAuthorityIndex($authorityIndex) {
        $this->authorityIndex = $authorityIndex;
    }
    
    protected function setSessionVars() {
        $users = array();
        foreach ($this->users as $user) {
            $users[] = array(
                'userID'=>$user->getUserID(),
                'auth_userID'=>$user->getUserID(),
                'auth'=>$user->getAuthenticationAuthorityIndex(),
            );
        }
        $_SESSION['users'] = $users;
        $_SESSION['ping'] = time();
    }
    
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }

    public function getUsers($returnAnonymous=false) {
        if ( count($this->users)>0 || !$returnAnonymous) {
            return $this->users;
        } else {
            return array(new AnonymousUser());
        }
    }

    /**
      * Return the active user
      * @return User
      */
    public function getUser($authority='User') {
        if (!$authority) {
            $authority = 'User';
        } elseif ($authority instanceOf AuthenticationAuthority) {
            $authority = $authority->getAuthorityIndex();
        } elseif (!is_scalar($authority)) {
            throw new KurogoException("Invalid authority $authority");
        }
        
        // will check for the authority index or user or authority class. 
        if (isset($this->users[$authority])) {
            return $this->users[$authority];
        }  else {
            foreach ($this->users as $user) {
                if ($user instanceOf $authority) {
                    return $user;
                } elseif ($user->getAuthenticationAuthority() instanceOf $authority) {
                    return $user;
                }
            }
            return new AnonymousUser();
        }
    }
    
    /**
      * Return the session id
      * @return string
      */
    public function getSessionID() {    
        return $this->session_id;
    }

    /**
      * Return the session id
      * @return string
      */
    public function getLoginToken() {
        return $this->login_token;
    }
    
    /**
      * Logs in the user
      * @param User $user
      * @return User
      */
    public function login(User $user) {
        Kurogo::log(LOG_NOTICE, sprintf("Logging in user %s:%s", $user->getAuthenticationAuthorityIndex(), $user->getUserID()), 'session');
        session_regenerate_id(true);
        $this->setUser($user);
        $this->setLoginCookie();
        return $user;
    }

    /**
      * Logout the current user
      */
    public function logout(AuthenticationAuthority $authority, $hard=false) {
        if (!$this->isLoggedIn($authority)) {
            return false;
        }

        $user = $this->getUser($authority);
        Kurogo::log(LOG_NOTICE, sprintf("Logging out user %s:%s", $user->getAuthenticationAuthorityIndex(), $user->getUserID()), 'session');
        $authority->logout($this, $hard);
        unset($this->users[$authority->getAuthorityIndex()]);
        $this->setSessionVars();
        $this->setLoginCookie();
        if (count($this->users) == 0) {
            $_SESSION = array();
        }
        session_regenerate_id(true);
        return true;
    }
    
    protected function logoutAllUsers() {
        foreach ($_SESSION['users'] as $userData) {
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($userData['auth'])) {
                $authority->logout($this, false);
            }
        }
        $this->setSessionVars();
    }

    private function getSessionData() {
        $data = array();

        foreach ($this->users as $auth=>$user) {
            $data[] = array(
                'auth'  => $user->getAuthenticationAuthorityIndex(),
                'userID'=> $user->getUserID(),
                'data'  => $user->getSessionData(),
                'hash'  => $user->getUserHash()
            );
        }
        
        return $data;
    }
    
    private function getUserHash($users) {
        $hash = '';

        foreach ($users as $user) {
            $hash .= $user['hash'];
        }
        
        return md5($hash);
    }
    
    /**
      * creates a login token that can be used for login later
      */
    private function setLoginCookie() {
    	if ($this->isLoggedIn()) {

    	    //generate a random value
			$new_login_token = md5(uniqid(rand(), true));
			
			if ($this->remainLoggedIn) {
                $expires = time() + $this->remainLoggedInTime;
            } else {
                $expires = 0;
            }

            // set save username cookie
			if ($this->saveUsername) {
                $saveUsernameExpires = time() + $this->saveUsernameTime;
            }else {
                $saveUsernameExpires = time() - 1;
            }
            if ($this->authorityIndex) {
                $user = $this->getUser();
                $userID = $user->getUserID();
                setCookie($this->authorityIndex . "_username", $userID, $saveUsernameExpires, $this->loginCookiePath);
            }
            
            $data = $this->getSessionData();
            
            $this->saveLoginTokenData($new_login_token, $expires, $data);
            
            // set the values and the cookies
			$this->login_token = $new_login_token;
			Kurogo::log(LOG_DEBUG, "Setting login token to $new_login_token", 'session');
			setCookie(self::TOKEN_COOKIE, $this->login_token, $expires, $this->loginCookiePath);
			setCookie(self::USERHASH_COOKIE, $this->getUserHash($data), $expires, $this->loginCookiePath);

		} else {
		    //clean up just in case
		    $this->clearLoginToken();
		}
    }
    
    /**
      * attempts to see if a valid login cookie is present. 
      */
    private function getLoginCookie() {
        $token ='';
        $hash = '';
    	if (isset($_COOKIE[self::TOKEN_COOKIE], $_COOKIE[self::USERHASH_COOKIE])) {
    	    $token = $_COOKIE[self::TOKEN_COOKIE];
    	    $hash = $_COOKIE[self::USERHASH_COOKIE];
    	} elseif (isset($_COOKIE[self::API_TOKEN_COOKIE], $_COOKIE[self::API_USERHASH_COOKIE])) {
    	    $token = $_COOKIE[self::API_TOKEN_COOKIE];
    	    $hash = $_COOKIE[self::API_USERHASH_COOKIE];
    	}
    	    	
        // a token exists
    	if ($token) {
    	    
    	    //get the token data
    	    if ($data  = $this->getLoginTokenData($token)) {

    	        $this->login_token = $token;
    	        $users = array();

                //validate the hash
                if ($this->getUserHash($data['data']) == $hash) {
                    foreach ($data['data'] as $userData) {

                        // attempt to get the user
                        if ($authority = AuthenticationAuthority::getAuthenticationAuthority($userData['auth'])) {
                            if ($user = $authority->getUser($userData['userID'])) {
                                $user->setSessionData($userData['data']);
                                $users[] = $user;
                            } else {
                                Kurogo::log(LOG_WARNING,"Unable to load user " . $userData['userID']  . " for " . $userData['auth'], 'session');
                            }
                        } else {
                            Kurogo::log(LOG_WARNING, "Unable to load authority ".  $userData['auth'], 'session');
                        }
                    }
                    
                    if (count($users)>0) {
                        return $users;
                    }
                }
            }

            // something did not match so clean up
            $this->clearLoginToken();
        }
        
        return false;
    }
    
    /**
      * clears any login cookies
      */
    private function clearLoginToken() {
    	if ($this->login_token) {
    	
    	    $this->clearLoginTokenData($this->login_token);

            setCookie(self::TOKEN_COOKIE, false, 1225344860, $this->loginCookiePath);
            setCookie(self::USERHASH_COOKIE, false, 1225344860, $this->loginCookiePath);
            setCookie(self::API_TOKEN_COOKIE, false, 1225344860, $this->apiCookiePath);
            setCookie(self::API_USERHASH_COOKIE, false, 1225344860, $this->apiCookiePath);
            $this->login_token = '';
            $_SESSION['login_token'] = '';
    	}
    }
    
}
