<?php
/**
  * @package Authentication
  */

/**
  */
require_once(LIB_DIR . '/AuthenticationAuthority.php');
/**
  */
require_once(LIB_DIR . '/User.php');

/**
  * @package Authentication
  */
class Session
{
    const SESSION_GC_TIME = 21600;  
    const TOKEN_COOKIE='lt';
    const USERHASH_COOKIE='lh';
    const API_TOKEN_COOKIE='alt';
    const API_USERHASH_COOKIE='alh';
    protected $session_id;
    protected $user;
    protected $auth;
    protected $auth_userID;
    protected $login_token;
    protected $useDB = false;
    protected $maxIdleTime=0;
    protected $remainLoggedInTime=0;
    protected $loginCookiePath;
    protected $apiCookiePath;
    
    public function __construct($args) {

        //load arguments
        $this->useDB = isset($args['AUTHENTICATION_USE_SESSION_DB']) ? $args['AUTHENTICATION_USE_SESSION_DB'] : false;
        $this->maxIdleTime = isset($args['AUTHENTICATION_IDLE_TIMEOUT']) ? intval($args['AUTHENTICATION_IDLE_TIMEOUT']) : 0;
        $this->remainLoggedInTime = isset($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) ? intval($args['AUTHENTICATION_REMAIN_LOGGED_IN_TIME']) : 0;
        $this->loginCookiePath = URL_BASE . 'login';
        $this->apiCookiePath = URL_BASE . API_URL_PREFIX . '/login';
        
        if (!isset($_SESSION)) {
            // set session ini values
            ini_set('session.name', SITE_KEY);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_path', COOKIE_PATH);
            ini_set('session.gc_maxlifetime', self::SESSION_GC_TIME);
            
            if ($this->useDB) {
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
        
        $user = new AnonymousUser();

        // see if a user is active        
        if (isset($_SESSION['auth'])) {
        
            $lastPing = isset($_SESSION['ping']) ? $_SESSION['ping'] : 0;
            $diff = time() - $lastPing;
            
            // see if max idle time has been reached
            if ( $this->maxIdleTime && ($diff > $this->maxIdleTime)) {
                // right now the user is just logged off, but we could show and error if necessary.
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($_SESSION['auth'])) {

                $auth_userID = isset($_SESSION['auth_userID']) ? $_SESSION['auth_userID'] : '';

                //attempt to load the user
                if ($auth_userID) {
                    if ($_user = $authority->getUser($auth_userID)) {
                        $user = $_user;
                        $this->login_token = isset($_SESSION['login_token']) ? $_SESSION['login_token'] : null;
                    } else {
                        error_log("Error trying to load user $auth_userID");
                    } 
                }
            }
        } elseif ($_user = $this->getLoginCookie()) {
            // valid login cookie was found
            $this->setUser($_user);
            
            //regenerate new login token
            $this->setLoginToken();
            return;
        } else {
            //anonymous user
        }
                    
        $this->setUser($user);
    }    
    
    /**
      * returns whether a user is logged in or not
      * @return boolean
      */
    public function isLoggedIn() {
        return strlen($this->user->getUserID()) > 0;
    }
    
    /**
      * sets the active user
      * @param User $user
      */
    private function setUser(User $user) {
        $this->user = $user;
        $_SESSION['userID'] = $user->getUserID();
        $_SESSION['auth_userID'] = $user->getUserID();
        $_SESSION['auth'] = $user->getAuthenticationAuthorityIndex();
        $_SESSION['ping'] = time();
    }

    /**
      * Return the active user
      * @return User
      */
    public function getUser() {
        return $this->user;
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
    public function login(User $user, $remainLoggedIn=false) {
        session_regenerate_id(true);
        $this->setUser($user);
        if ($remainLoggedIn) {
            $this->setLoginToken();
        }
        return $user;
    }

    /**
      * Logout the current user
      */
    public function logout() {
        $user = new AnonymousUser();
        $this->setUser($user);
        $this->clearLoginToken();
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
    
        $sql = "SELECT 1 FROM sessions";
        $conn = SiteDB::connection();
        if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sqls[] = "CREATE TABLE sessions (
                    id char(32) primary key, 
                    data text, 
                    auth text,
                    userID text,
                    ts int)";
            $sqls[] = "CREATE TABLE login_tokens (
                    token char(32) primary key, 
                    auth text,
                    userID text, 
                    data text,
                    timestamp int,
                    expires int)";
            
            foreach ($sqls as $sql) {
                $conn->query($sql);
            }
        }
    }
    
    private function loginTokenFile($token) {
        return ini_get('session.save_path') . "/login_" . $token;
    }
    
    /**
      * creates a login token that can be used for login later
      */
    private function setLoginToken() {
    	if ($this->isLoggedIn()) {
    	    //generate a random value
			$login_token = md5(uniqid(rand(), true));
			$expires = $this->remainLoggedInTime ? time() + $this->remainLoggedInTime : 0;
			
			if ($this->useDB) {
			
			    //if the token is already set, update it with the new value
                if ($this->login_token) {
                    $sql = "UPDATE login_tokens SET token=?, timestamp=?, expires=?, data=? WHERE token=?";
                    $params = array($login_token, time(), $expires, serialize($this->user->getSessionData()), $this->login_token);
                } else {
                    $sql = "INSERT INTO login_tokens (token, auth, userID, timestamp, expires, data) VALUES (?,?,?,?,?,?)";
                    $params = array($login_token, $this->user->getAuthenticationAuthorityIndex(), $this->user->getUserID(), time(), $expires, serialize($this->user->getSessionData()));
                }
                
                $conn = SiteDB::connection();
                $result = $conn->query($sql, $params);
            } else {
                $params = array(
                    'auth'=>$this->user->getAuthenticationAuthorityIndex(),
                    'userID'=>$this->user->getUserID(),
                    'timestamp'=>time(),
                    'expires'=>$expires,
                    'data'=>$this->user->getSessionData()
                );

                $file = $this->loginTokenFile($login_token);
                if ($this->login_token) {
                    $oldfile = $this->loginTokenFile($this->login_token);
                    unlink($oldfile);
                }
                
                file_put_contents($file, serialize($params));
            }

            // set the values and the cookies
			$this->login_token = $login_token;
			$_SESSION['login_token'] = $login_token;
			
            setCookie(self::TOKEN_COOKIE, $this->login_token, $expires, $this->loginCookiePath);
            setCookie(self::USERHASH_COOKIE, $this->user->getUserHash(), $expires, $this->loginCookiePath);
            setCookie(self::API_TOKEN_COOKIE, $this->login_token, $expires, $this->apiCookiePath);
            setCookie(self::API_USERHASH_COOKIE, $this->user->getUserHash(), $expires, $this->apiCookiePath);
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
    	
    	if ($token) {
    	    if ($this->useDB) {
                $conn = SiteDB::connection();
                
                // see if we have on record the token and it hasn't expired
        		$sql = "SELECT auth, userID, data FROM login_tokens WHERE token=? and expires>?";
                $result = $conn->query($sql,array($token, time()));
                
                if ($data = $result->fetch()) {
                    $data['data'] = unserialize($data['data']);
                }

    	    } else {
                $file = $this->loginTokenFile($token);
                $data = false;
                if (file_exists($file)) {
                    if ($data = file_get_contents($file)) {
                        $data = unserialize($data);
                    }
                }
    	    }
    	    
    	    if ($data) {
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($data['auth'])) {
                    if ($user = $authority->getUser($data['userID'])) {
                    
                        // see if the hash matches the user hash
                        if ($user->getUserHash() == $hash) {
                            // matched
                            $this->login_token = $token;
                            $_SESSION['login_token'] = $this->login_token;
                            $user->setSessionData($data['data']);
                            return $user;
                        } else {
                            error_log("Hash " . $user->getUserHash() . " does not match " . $hash);
                        }
                    } else {
                        error_log("Unable to load user " . $data['userID']  . " for " . $data['auth']);
                    }
                } else {
                    error_log("Unable to load authority ".  $data['auth']);
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
    	    if ($this->useDB) {
                $conn = SiteDB::connection();
                $sql = "DELETE FROM login_tokens WHERE token=?"; 
        		$result = $conn->query($sql,array($this->login_token));

                // clean up expired cookies
                $sql = "DELETE FROM login_tokens WHERE expires<?";
        		$result = $conn->query($sql,array($this->login_token));
            } else {
                $file = $this->loginTokenFile($this->login_token);
                @unlink($file);
            }

            setCookie(self::TOKEN_COOKIE, false, 1225344860, $this->loginCookiePath);
            setCookie(self::USERHASH_COOKIE, false, 1225344860, $this->loginCookiePath);
            setCookie(self::API_TOKEN_COOKIE, false, 1225344860, $this->apiCookiePath);
            setCookie(self::API_USERHASH_COOKIE, false, 1225344860, $this->apiCookiePath);
            $this->login_token = '';   
            $_SESSION['login_token'] = '';
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
