<?php
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
            if (!$config instanceOf Config) {
                $config = Kurogo::siteConfig();
            }
    
            $config = array(
                'DB_TYPE'=>$config->getVar('DB_TYPE'),
                'DB_HOST'=>$config->getVar('DB_HOST'),
                'DB_USER'=>$config->getVar('DB_USER'),
                'DB_PASS'=>$config->getVar('DB_PASS'),
                'DB_DBNAME'=>$config->getVar('DB_DBNAME'),   
                'DB_FILE'=>$config->getVar('DB_FILE'),
                'DB_CREATE'=>$config->getOptionalVar('DB_CREATE')
            );
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
