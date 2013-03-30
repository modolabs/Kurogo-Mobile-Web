<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('db');

/**
  * @package Authentication
  */
class SessionDB extends Session
{
    protected $conn;
    
    public function init($args) {
        parent::init($args);
        // set the database session handlers
        session_set_save_handler(
            array($this, 'sess_open'),
            array($this, 'sess_close'),
            array($this, 'sess_read'),
            array($this, 'sess_write'),
            array($this, 'sess_destroy'),
            array($this, 'sess_gc')
        );
        // necessary http://www.php.net/manual/en/function.session-set-save-handler.php
        register_shutdown_function('session_write_close');
    }    
    
    protected function saveLoginTokenData($new_login_token, $expires, $data) {

        //if the token is already set, update it with the new value
        if ($this->login_token) {
            $sql = "UPDATE login_tokens SET token=?, timestamp=?, expires=?, data=? WHERE token=?";
            $params = array($new_login_token, time(), $expires, serialize($data), $this->login_token);
        } else {
            $sql = "INSERT INTO login_tokens (token, timestamp, expires, data) VALUES (?,?,?,?)";
            $params = array($new_login_token, time(), $expires, serialize($data));
        }
        
        $result = $this->conn->query($sql, $params);
    }  
    
    protected function getLoginTokenData($token) {
        
        // see if we have on record the token and it hasn't expired
        $sql = "SELECT data FROM login_tokens WHERE token=? and expires>?";
        $result = $this->conn->query($sql,array($token, time()));
        
        if ($data = $result->fetch()) {
            $data['data'] = unserialize($data['data']);
        }

        return $data;
    }
    
    protected function clearLoginTokenData($token) {
        $sql = "DELETE FROM login_tokens WHERE token=?"; 
        $result = $this->conn->query($sql,array($token));

        // clean up expired cookies
        $sql = "DELETE FROM login_tokens WHERE expires<?";
        $result = $this->conn->query($sql,array(time()));
    }

    /**
      * creates the session and login_tokens tables
      */
    private function createDatabaseTables() {
    
        $sql = "SELECT 1 FROM sessions";
        if (!$result = $this->conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sql = "CREATE TABLE sessions (
                    id char(32) primary key, 
                    data text, 
                    ts int)";
            $this->conn->query($sql);
        }
        
        $sql = "SELECT 1 FROM login_tokens";
        if (!$result = $this->conn->query($sql, array(), db::IGNORE_ERRORS)) {
            $sql = "CREATE TABLE login_tokens (
                    token char(32) primary key, 
                    data text,
                    timestamp int,
                    expires int)";
            $this->conn->query($sql);
        }
    }

    /**
      * creates a login token that can be used for login later
      */
    /**
      * reads in session data from the database. 
      */
	public function sess_read($id) {
		$sql = "SELECT data FROM sessions WHERE id=?";
		
		$result = $this->conn->query($sql, array($id), db::IGNORE_ERRORS);
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
        $this->conn = SiteDB::connection();
		return true;
	} 
	 
	public function sess_close() {
		return true;
	} 
		 
	public function sess_write($id, $data) {

		$timestamp = time();
		
		$sql = "REPLACE INTO sessions (id, data, ts)
				VALUES (?,?,?)";
		$result = $this->conn->query($sql, array($id, $data, $timestamp), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
		 
	public function sess_destroy($id) {
		$sql = "DELETE FROM sessions WHERE id=?";
		$result = $this->conn->query($sql, array($id), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
		 
	public function sess_gc($max_time=null) {
		$sql = "DELETE FROM sessions
				WHERE ts < ?";
		$result = $this->conn->query($sql, array(time() - $max_time), db::IGNORE_ERRORS);
		return $result ? true : false;
	}
}
