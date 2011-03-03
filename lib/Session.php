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
    protected $user;
    protected $auth;
    protected $auth_userID;
    protected $useDB = false;
    protected $maxIdleTime=0;
    
    public function __construct($useDB=false, $maxIdleTime=0) {

        $this->useDB = $useDB;
        $this->maxIdleTime = intval($maxIdleTime);

        if (!isset($_SESSION)) {
            ini_set('session.name', SITE_KEY);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_path', COOKIE_PATH);
            ini_set('session.gc_maxlifetime', self::SESSION_GC_TIME);
            
            if ($this->useDB) {
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
                if (!is_dir(CACHE_DIR . "/session")) {
                    mkdir(CACHE_DIR . "/session", 0700, true);
                }
                
                ini_set('session.save_path', CACHE_DIR . "/session");
            }
            
            session_start();
        }
        
        $user = new AnonymousUser();
        
        if (isset($_SESSION['auth'])) {
        
            $lastPing = isset($_SESSION['ping']) ? $_SESSION['ping'] : 0;
            $diff = time() - $lastPing;
            
            if ( $this->maxIdleTime && ($diff > $this->maxIdleTime)) {
                // right now the user is just logged off, but we could show and error if necessary.
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($_SESSION['auth'])) {

                $auth_userID = isset($_SESSION['auth_userID']) ? $_SESSION['auth_userID'] : '';

                if ($auth_userID) {
                
                    if ($_user = $authority->getUser($auth_userID)) {
                        $user = $_user;
                    } else {
                        error_log("Error trying to load user $auth_userID");
                    } 
                }
            }
        }
                    
        $this->setUser($user);
    }    
    
    public function isLoggedIn() {
        return strlen($this->user->getUserID()) > 0;
    }
    
    protected function setUser(User $user) {
        $this->user = $user;
        $_SESSION['userID'] = $user->getUserID();
        $_SESSION['auth_userID'] = $user->getUserID();
        $_SESSION['auth'] = $user->getAuthenticationAuthorityIndex();
        $_SESSION['ping'] = time();
    }

    public function getUser() {
        return $this->user;
    }
    
    public function login(User $user) {
        session_regenerate_id(true);
        $this->setUser($user);
        return $user;
    }

    public function logout() {
        $user = new AnonymousUser();
        $this->setUser($user);
		    session_regenerate_id(true);
        return true;
    }

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
                if (preg_match("/^sess_([a-z0-9]+)$/i", $entry, $bits)) {
                    $data = file_get_contents($dir . "/" . $entry);
                    $users[$bits[1]] = self::unserialize_session_data($data);
                }
            }
        }
		return $users;
	}
	
    protected function createDatabaseTables() {
    
        $sql = "SELECT 1 FROM sessions";
        $conn = SiteDB::connection();
        if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sql = "CREATE TABLE sessions (
                    id char(32) primary key, 
                    data text, 
                    auth text,
                    userID text,
                    ts int)";
            $conn->query($sql);
        }
    }
    
	public static function sess_read($id) {
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

	public static function unserialize_session_data( $serialized_string ) {
		$variables = array(  );
		$a = preg_split( "/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		
		for( $i = 0; $i < count( $a ); $i = $i+2 ) {
			$variables[$a[$i]] = unserialize( $a[$i+1] );
		}
		return( $variables );
	}
	
	public static function sess_open($path, $name) {
		return true;
	} 
	 
	public static function sess_close() {
		return true;
	} 
		 
	public static function sess_write($id, $data) {

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
		 
	public static function sess_destroy($id) {
        $conn = SiteDB::connection();
		$sql = "DELETE FROM sessions WHERE id=?";
		$result = $conn->query($sql, array($id), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
		 
	public static function sess_gc($max_time=null) {
        $conn = SiteDB::connection();
		$sql = "DELETE FROM sessions
				WHERE ts < ?";
		$result = $conn->query($sql, array(time() - Session::SESSION_GC_TIME), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
}
