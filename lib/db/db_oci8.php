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
  * Oracle database abstraction
  * @package Database
  */

/**
  * Oracle database abstraction
  * @package Database
  */
 
Kurogo::includePackage('db', 'oci8');

class db_oci8 extends db
{
    public static function connection($dsn_data) {
        if (!isset($dsn_data['DB_HOST']) || empty($dsn_data['DB_HOST'])) {
            throw new KurogoConfigurationException("Oracle host not specified");
        }

        if (!isset($dsn_data['DB_USER']) || empty($dsn_data['DB_USER'])) {
            throw new KurogoConfigurationException("Oracle user not specified");
        }

        if (!isset($dsn_data['DB_PASS']) || empty($dsn_data['DB_PASS'])) {
            throw new KurogoConfigurationException("Oracle password not specified");
        }

        if (!isset($dsn_data['DB_DBNAME']) || empty($dsn_data['DB_DBNAME'])) {
            throw new KurogoConfigurationException("Oracle database not specified");
        }

        $connectionString = $dsn_data['DB_HOST'];
        
        if (isset($dsn_data['DB_PORT']) && !empty($dsn_data['DB_PORT'])) {
            $connectionString .= ':'. $dsn_data['DB_PORT'];
        }

        $connectionString .= '/' . $dsn_data['DB_DBNAME'];

        if (isset($dsn_data['DB_CHARSET']) && !empty($dsn_data['DB_CHARSET'])) {
            $charSet = $dsn_data['DB_CHARSET'];
        }else{
            $charSet = 'utf8';
        }
        
        $connection = oci_pconnect($dsn_data['DB_USER'], $dsn_data['DB_PASS'], $connectionString, $charSet);
        return $connection;
    }

    public function query($sql, $parameters = array(), $ignoreErrors = false, $catchErrorCodes = array()){
        $this->lastError = null;
        Kurogo::log(LOG_INFO, $this->dbType . " query log: $sql", 'db');

        if (!$stmt = oci_parse($this->connection, $sql)) {
            return $this->errorHandler($sql, oci_error($this->connection), $ignoreErrors, $catchErrorCodes);
        }

        foreach($parameters as $key => $val) {
            // Important: the 3rd param is a reference, if we use $val, it will cause problem.
            oci_bind_by_name($stmt, $key, $parameters[$key]);
        }
        
        if (!$result = oci_execute($stmt)) {
            return $this->errorHandler($sql, oci_error($stmt), $ignoreErrors, $catchErrorCodes);
        }

        $statement = new OCI8Statement($stmt);
    
        return $statement;
    }

    /*
     * Handle query error
     */
    private function errorHandler($sql, $errorInfo, $ignoreErrors, $catchErrorCodes) {

        $e = new KurogoDataException (sprintf("Error with %s: %s", $sql, $errorInfo['message']), $errorInfo['code']);
    
        if ($ignoreErrors) {
            $this->lastError = KurogoError::errorFromException($e);
            return;
        }
    
        // prevent the default error handling mechanism
        // from triggerring in the rare case of expected
        // errors such as unique field violations
        if(in_array($errorInfo[0], $catchErrorCodes)) {
            return $errorInfo;
        }
    
        Kurogo::log(LOG_WARNING, sprintf("%s error with %s: %s", $this->dbType, $sql, $errorInfo['message']), 'db');
        if (Kurogo::getSiteVar('DB_DEBUG')) {
            throw $e;
        }
    }

    /*
     * Quotes a SQL string
     * @param string
     * @return string
     */
    public function quote($string) {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Pings a database connection
     */
    public function ping() {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Begin Transaction
     */
    public function beginTransaction() {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Commit Transaction
     */
    public function commit() {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Lock table
     * @param string $table table to lock
     */
    public static function lockTable($table) {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Unlock table
     */
    public static function unlockTable() {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
    
    /*
     * Returns the ID of the last inserted row or sequence value
     * @return string
     */
    public function lastInsertId() {
        throw new KurogoException("This function has not been implmented for Oracle yet.");
    }
}
