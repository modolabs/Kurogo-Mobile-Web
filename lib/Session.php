<?php
/**
  * @package Authentication
  */

/**
  * @package Authentication
  */
class Session
{
    const SESSION_GC_TIME = 21600;  
    const TOKEN_COOKIE='lt';
    const USERHASH_COOKIE='lh';
    protected $session_id;
    protected $users = array();
    protected $login_token;
    /*
    protected $user;
    protected $auth;
    protected $auth_userID;
    */
    protected $useDB = false;
    protected $maxIdleTime=0;
    protected $remainLoggedIn = false;
    protected $remainLoggedInTime=0;
    protected $loginCookiePath;
    
    public function __construct($args) {

        //load arguments
        $this->useDB = isset($args['AUTHENTICATION_USE_SESSION_DB']) ? $args['AUTHENTICATION_USE_SESSION_DB'] : false;
        $this->maxIdleTime = isset($args['AUTHENTICATION_IDLE_TIMEOUT']) ? intval($args['AUTHENTICATION_IDLE_TIMEOUT']) : 0;
        $this->remainLoggedInTime = isset($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) ? intval($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) : 0;
        $this->loginCookiePath = URL_BASE . 'login';
        
        if (!isset($_SESSION)) {
            // set session ini values
            ini_set('session.name', SITE_KEY);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_path', COOKIE_PATH);
            ini_set('session.gc_maxlifetime', self::SESSION_GC_TIME);
            
            if ($this->useDB) {
                includePackage('db');
                // set the database session handlers
                session_set_save_handler(
                    array($this, 'sess_open'),
                    array($this, 'sess_close'),
                    array($this, 'sess_read'),
                    array($this, 'sess_write'),
                    array($this, 'sess_destroy'),
                    array($this, 'sess_gc')
                );
            } else {
                ini_set('session.save_handler', 'files');
                
                // make sure session directory exists
                if (!is_dir(CACHE_DIR . "/session")) {
                    mkdir(CACHE_DIR . "/session", 0700, true);
                }
                
                ini_set('session.save_path', CACHE_DIR . "/session");
            }
            
            session_start();
            $this->session_id = session_id();
            $_SESSION['platform'] = $GLOBALS['deviceClassifier']->getPlatform();
            $_SESSION['pagetype'] = $GLOBALS['deviceClassifier']->getPagetype();
            $_SESSION['user_agent'] = $GLOBALS['deviceClassifier']->getUserAgent();
        }
        
        // see if a user is active        
        if (isset($_SESSION['users']) && is_array($_SESSION['users'])) {
            
            $lastPing = isset($_SESSION['ping']) ? $_SESSION['ping'] : 0;
            $diff = time() - $lastPing;
            
            // see if max idle time has been reached
            if ( $this->maxIdleTime && ($diff > $this->maxIdleTime)) {
                // right now the user is just logged off, but we could show and error if necessary.
            } else {
                $ok = false;
                foreach ($_SESSION['users'] as $userData) {
                    if ($authority = AuthenticationAuthority::getAuthenticationAuthority($userData['auth'])) {

                        if ($user = $authority->getUser($userData['auth_userID'])) {
                            $ok = true;
                            $this->setUser($user);
                        } else {
                            error_log("Error trying to load user " . $userData['auth_userID']);
                        }
                    }
                }
                
                if ($ok) {
                    return;
                }
            }
        } elseif ($users = $this->getLoginCookie()) {
            $this->login_cookie = $_COOKIE[self::TOKEN_COOKIE];
            foreach ($users as $user) {
                $this->setUser($user);
            }

            //regenerate new login cookie
            $this->setLoginCookie();
            return;
        } else {
            //anonymous user
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
    private function setUser(User $user) {
        if ($auth = $user->getAuthenticationAuthorityIndex()) {
            $this->users[$auth] = $user;
            $this->setSessionVars();
        }
    }
    
    public function setRemainLoggedIn($remainLoggedIn) {
        $this->remainLoggedIn = $remainLoggedIn ? true : false;
    }
    
    private function setSessionVars() {
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
    
    public function getUsers() {
        return $this->users;
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
            throw new Exception("Invalid authority $authority");
        }
        
        /* will check for the authority index or user or authority class. */
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
        session_regenerate_id(true);
        $this->setUser($user);
        $this->setLoginCookie();
        return $user;
    }

    /**
      * Logout the current user
      */
    public function logout(AuthenticationAuthority $authority) {
        if (!$this->isLoggedIn($authority)) {
            return false;
        }
        
        $authority->logout($this);
        unset($this->users[$authority->getAuthorityIndex()]);
        $this->setSessionVars();
        $this->setLoginCookie();
        session_regenerate_id(true);
        return true;
    }

	public function getSessionByID($id) {
	    $sessions = self::getAllSessions();
	    return isset($sessions[$id]) ? $sessions[$id] : null;
	}

    /**
      * returns a list of logged in users
      * @return array
      */
	public function getActiveSessions() {
        $users = array();

	    if ($this->useDB) {
            $conn = SiteDB::connection();
            $sql = "SELECT * FROM sessions
                    WHERE length(userID)>0
                    ORDER BY ts DESC";
            
            $result = $conn->query($sql);
            while ($row = $result->fetch()) {
                $users[$row['id']] = self::unserialize_session_data($row['data']);
            }
        } else {
            $sessions = $this->getAllSessions();
            foreach ($sessions as $id=>$data) {
                if ($data['userID']) {
                    $users[$id] = $data;
                }
            }
        }
        
		return $users;
	}

    /**
      * removes a session
      * @param string $id
      */
	public function deleteSession($id) {
	    if (!preg_match("/^[a-z0-9]+$/", $id)) {
	        throw new Exception("Invalid session id $id");
	    }
	    
	    if ($id==$this->session_id) {
	        return false;
	    }
	    
	    if ($this->useDB) {
            $conn = SiteDB::connection();
            $sql = "DELETE FROM sessions
                    WHERE id=?";
            $result = $conn->query($sql, array($id));
            return true;
        } else {
            $dir = ini_get('session.save_path');
            $file = $dir . '/sess_' . $id;
            if (file_exists($file)) {
                return unlink($file);
            }
            
            return false;
        }
	}

    /**
      * returns a list of all sessions
      * @return array
      */
	public function getAllSessions() {
        $users = array();

	    if ($this->useDB) {
            $conn = SiteDB::connection();
            $sql = "SELECT * FROM sessions
                    ORDER BY ts DESC";
            
            $result = $conn->query($sql);
            while ($row = $result->fetch()) {
                $users[$row['id']] = self::unserialize_session_data($row['data']);
            }
        } else {
            $dir = ini_get('session.save_path');
            $d = dir($dir);
            while (false !== ($entry = $d->read())) {
                if (preg_match("/^sess_([a-z0-9]+)$/", $entry, $bits)) {
                    $data = file_get_contents($dir . "/" . $entry);
                    $users[$bits[1]] = self::unserialize_session_data($data);
                }
            }
        }
		return $users;
	}
	
    /**
      * creates the session and login_tokens tables
      */
    protected function createDatabaseTables() {
    
        $conn = SiteDB::connection();

        $sql = "SELECT 1 FROM sessions";
        if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sql = "CREATE TABLE sessions (
                    id char(32) primary key, 
                    data text, 
                    auth text,
                    userID text,
                    ts int)";
            $conn->query($sql);
        }
        
        $sql = "SELECT 1 FROM login_tokens";
        if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sql = "CREATE TABLE login_tokens (
                    token char(32) primary key, 
                    data text,
                    timestamp int,
                    expires int)";
            $conn->query($sql);
        }
    }

    private function loginTokenFolder() {
        return ini_get('session.save_path');
    }
    
    private function loginTokenFile($token) {
        return $this->loginTokenFolder() . "/login_" . $token;
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
      * sets the cookie that permits logins after the session has expired
      */
    private function setLoginCookie() {

    	if ($this->isLoggedIn()) {
    	    //generate a random value
			$login_token = md5(uniqid(rand(), true));
			if ($this->remainLoggedIn) {
                $expires = time() + $this->remainLoggedInTime;
            } else {
                $expires = 0;
            }
            
            $data = $this->getSessionData();
            
			if ($this->useDB) {
			
			    //if the token is already set, update it with the new value
                if ($this->login_token) {
                    $sql = "UPDATE login_tokens SET token=?, timestamp=?, expires=?, data=? WHERE token=?";
                    $params = array($login_token, time(), $expires, serialize($data), $this->login_token);
                } else {
                    $sql = "INSERT INTO login_tokens (token, timestamp, expires, data) VALUES (?,?,?,?)";
                    $params = array($login_token, time(), $expires, serialize($data));
                }
                
                $conn = SiteDB::connection();
                $result = $conn->query($sql, $params);
            } else {
                $users = array();
                
                $params = array(
                    'timestamp'=>time(),
                    'expires'=>$expires,
                    'data'=>$data
                );
                
                $file = $this->loginTokenFile($login_token);
                if ($this->login_token) {
                    $oldfile = $this->loginTokenFile($this->login_token);
                    unlink($oldfile);
                }
                
                file_put_contents($file, serialize($params));
                chmod($file, 0600);
            }

            // set the values and the cookies
			$this->login_token = $login_token;
			setCookie(self::TOKEN_COOKIE, $this->login_token, $expires, $this->loginCookiePath);
			setCookie(self::USERHASH_COOKIE, $this->getUserHash($data), $expires, $this->loginCookiePath);
		} else {
		    //clean up just in case
		    $this->clearLoginCookie();
		}
    }
    
    /**
      * attempts to see if a valid login cookie is present. 
      */
    private function getLoginCookie() {
    
    	if (isset($_COOKIE[self::TOKEN_COOKIE], $_COOKIE[self::USERHASH_COOKIE])) {
    	    if ($this->useDB) {
                $conn = SiteDB::connection();
                
                // see if we have on record the token and it hasn't expired
        		$sql = "SELECT data FROM login_tokens WHERE token=? and expires>?";
                $result = $conn->query($sql,array($_COOKIE[self::TOKEN_COOKIE], time()));
                
                if ($data = $result->fetch()) {
                    $data['data'] = unserialize($data['data']);
                }

    	    } else {
                $file = $this->loginTokenFile($_COOKIE[self::TOKEN_COOKIE]);
                $data = false;
                if (file_exists($file)) {
                    if ($data = file_get_contents($file)) {
                        $data = unserialize($data);
                        if ($data['expires']<time()) {
                            $data = false;
                        }
                    }
                }
    	    }
    	    
    	    if ($data) {
    	        $users = array();
                if ($this->getUserHash($data['data']) == $_COOKIE[self::USERHASH_COOKIE]) {
                    foreach ($data['data'] as $userData) {

                        if ($authority = AuthenticationAuthority::getAuthenticationAuthority($userData['auth'])) {
                            if ($user = $authority->getUser($userData['userID'])) {
                                $user->setSessionData($userData['data']);
                                $users[] = $user;
                            } else {
                                error_log("Unable to load user " . $userData['userID']  . " for " . $userData['auth']);
                            }
                        } else {
                            error_log("Unable to load authority ".  $userData['auth']);
                        }
                    }
                    
                    if (count($users)>0) {
                        return $users;
                    }
                }
            }

            // something did not match so clean up
            $this->clearLoginCookie();
        }
        
        return false;
    }
    	
    /**
      * clears any login cookies
      */
    private function clearLoginCookie() {
    	if (isset($_COOKIE[self::TOKEN_COOKIE], $_COOKIE[self::USERHASH_COOKIE])) {
    	    if ($this->useDB) {
                $conn = SiteDB::connection();
                $sql = "DELETE FROM login_tokens WHERE token=?"; 
        		$result = $conn->query($sql,array($_COOKIE[self::TOKEN_COOKIE]));

                // clean up expired cookies
                $sql = "DELETE FROM login_tokens WHERE expires<?";
        		$result = $conn->query($sql,array(time()));
            } else {
                $file = $this->loginTokenFile($_COOKIE[self::TOKEN_COOKIE]);
                @unlink($file);

                // clean up expired cookies
                $files = glob($this->loginTokenFolder() . "/login_*");
                foreach ($files as $file) {
                    if ($data = file_get_contents($file)) {
                        $data = unserialize($data);
                        if ($data['expires']<time()) {
                            unlink($file);
                        }
                    }
                }
            }

            setCookie(self::TOKEN_COOKIE, false, 1225344860, $this->loginCookiePath);
            setCookie(self::USERHASH_COOKIE, false, 1225344860, $this->loginCookiePath);
            $this->login_token = '';   
    	}
    }
    
    /**
      * reads in session data from the database. 
      */
	public function sess_read($id) {
		$sql = "SELECT data FROM sessions WHERE id=?";
        $conn = SiteDB::connection();
		
		$result = $conn->query($sql, array($id), db::IGNORE_ERRORS);
		if (!$result) {
		    self::createDatabaseTables();
		    return '';
		}

		if ($row = $result->fetch()) {
			$return = $row['data'];
		} else {
			$return = '';
		}

		return $return;
	}

	public function unserialize_session_data( $serialized_string ) {
		$variables = array(  );
		$a = preg_split( "/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		
		for( $i = 0; $i < count( $a ); $i = $i+2 ) {
			$variables[$a[$i]] = unserialize( $a[$i+1] );
		}
		return( $variables );
	}
	
	public function sess_open($path, $name) {
		return true;
	} 
	 
	public function sess_close() {
		return true;
	} 
		 
	public function sess_write($id, $data) {

		$dataArr = self::unserialize_session_data($data);
		
		$userID = isset($dataArr['userID']) ? $dataArr['userID'] : '';
		$auth = isset($dataArr['auth']) ? $dataArr['auth'] : '';
		$timestamp = time();
		
        $conn = SiteDB::connection();
		
		$sql = "REPLACE INTO sessions (id, data, auth, userID, ts)
				VALUES (?,?,?,?,?)";
		$result = $conn->query($sql, array($id, $data, $auth, $userID, $timestamp), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
		 
	public function sess_destroy($id) {
        $conn = SiteDB::connection();
		$sql = "DELETE FROM sessions WHERE id=?";
		$result = $conn->query($sql, array($id), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
		 
	public function sess_gc($max_time=null) {
        $conn = SiteDB::connection();
		$sql = "DELETE FROM sessions
				WHERE ts < ?";
		$result = $conn->query($sql, array(time() - $max_time), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
}
