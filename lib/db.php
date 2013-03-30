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
 * @package Database
 */

/** Not supported */
define('DB_NOT_SUPPORTED', 1);

/**
 * Handles connections to a database
 * @package Database
 */
class db {
    protected $dbType;
    protected $connection;
    protected $lastError;
    const IGNORE_ERRORS=true;
    
    const CONSTRAINT_VIOLATION_ERROR=23000;

    /*
     * Returns the last error that occurred
     * @return KurogoError object
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getDBType() {
        return $this->dbType;
    }
  
    /*
     * Constructor
     * @param array an associative array of connection parameters
     */
    public function __construct($config=null) {
        if (!is_array($config) || empty($config)) {
			$config = Kurogo::getSiteSection('database');
        }
    
        $this->init($config);
    }
    
    protected function init($config=null) {
        $this->lastError = null;
        $this->dbType = isset($config['DB_TYPE']) ? $config['DB_TYPE'] : null;
        Kurogo::log(LOG_DEBUG, "Initializing $this->dbType", 'db');
        if (!file_exists(LIB_DIR . "/db/db_$this->dbType.php")) {
            $e = new KurogoConfigurationException("Database type $this->dbType not found");
            $this->lastError = KurogoError::errorFromException($e);
            throw $e;
        }
    
        require_once(LIB_DIR . "/db/db_$this->dbType.php");
        try {
            $this->connection = call_user_func(array("db_$this->dbType", 'connection'), $config);
        } catch (Exception $e) {
            $this->lastError = KurogoError::errorFromException($e);
            Kurogo::log(LOG_ALERT, "Error connecting to $this->dbType database: " . $e->getMessage(), 'db');
            throw new KurogoDataServerException("Error connecting to database");
        }
    }
    
    /*
     * Execute a query
     * @param string $sql the SQL query to execute
     * @param array $parameters. Optional parameters that are used as bound parameters
     * @param boolean $ignoreErrors. If true then execution will continue on errors
     * @param array $catchErrorCodes
     * @return PDOStatement 
     */
    public function query($sql, $parameters=array(), $ignoreErrors=false, $catchErrorCodes=array()) {
        $this->lastError = null;
        Kurogo::log(LOG_INFO, $this->dbType . " query log: $sql", 'db');
    
        if (!$result = $this->connection->prepare($sql)) {
            return $this->errorHandler($sql, $this->connection->errorInfo(), $ignoreErrors, $catchErrorCodes);
        }
    
        $result->setFetchMode(PDO::FETCH_ASSOC);
    
        if (!$result->execute($parameters)) {
            return $this->errorHandler($sql, $result->errorInfo(), $ignoreErrors, $catchErrorCodes);
        }
    
        return $result;
    }
    
  // http://en.wikipedia.org/wiki/Select_%28SQL%29#Limiting_result_rows
    public function limitQuery($sql, $parameters=array(), $ignoreErrors=false, 
        $catchErrorCodes=array(), $limit=1)
    {
        if (intval($limit) && (
                $this->dbType == 'sqlite' ||
                $this->dbType == 'mysql' ||
                $this->dbType == 'pgsql' // TODO: this doens't work for pg < v8.4
                ))
        {
            $sql .= " LIMIT $limit";
        }
        return $this->query($sql, $parameters, $ignoreErrors, $catchErrorCodes);
    }
  
    /*
     * Returns an array of sources (tables) in the database. 
     * @return array Array of tablenames in the database
     */
    public function getTables() {
        $sql = '';
        switch ($this->dbType) {
            case 'mysql':
                $sql = "SHOW TABLES";
                break;
            case 'sqlite':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;";
                break;
            default:
                throw new KurogoException("db->getTables() not supported for $this->dbType");
        }
        
        $tables = array();
        $result = $this->query($sql);
        while ($row = $result->fetch()) {
            $tables[] = current($row);
        }

        return $tables;
    }
    
    /*
     * Handle query error
     */
    private function errorHandler($sql, $errorInfo, $ignoreErrors, $catchErrorCodes) {
    
        $e = new KurogoDataException (sprintf("Error with %s: %s", $sql, $errorInfo[2]), $errorInfo[1]);
    
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
    
        Kurogo::log(LOG_WARNING, sprintf("%s error with %s: %s", $this->dbType, $sql, $errorInfo[2]), 'db');
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
        return $this->connection->quote($string);
    }
    
    /*
     * Pings a database connection
     */
    public function ping() {
        if(!$this->connection->ping()) {
            $this->connection->close();
            $this->connection = NULL;
            $this->init();
        }
    }
    
    /*
     * Begin Transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /*
     * Commit Transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /*
     * Lock table
     * @param string $table table to lock
     */
    public static function lockTable($table) {
    }
    
    /*
     * Unlock table
     */
    public static function unlockTable() {
    }
    
    /*
     * Returns the ID of the last inserted row or sequence value
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public static function getDBTypes() {
        return array(
            'mysql'=>'MySQL',
            'sqlite'=>'SQLite',
            'pgsql'=>'PostgreSQL',
            'mssql'=>'MS SQL Server'
        );
    }
}
