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
  protected $connection;
  protected $lastError;
  const IGNORE_ERRORS=true;

  const CONSTRAINT_VIOLATION_ERROR=23000;
  
  public function getLastError() {
    return $this->lastError;
  }
  
  public function __construct($config=null)
  {
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
            'DB_FILE'=>$config->getVar('DB_FILE')
        );
    }

    $this->init($config);
  }
  
  public function init($config=null) 
  {
    $this->lastError = null;
    $db_type = isset($config['DB_TYPE']) ? $config['DB_TYPE'] : null;
    if (!file_exists(LIB_DIR . "/db/db_$db_type.php")) {
        $e = new Exception("Database type $db_type not found");
        $this->lastError = KurogoError::errorFromException($e);
        throw $e;
    }
    
    require_once(LIB_DIR . "/db/db_$db_type.php");
    try {
        $this->connection = call_user_func(array("db_$db_type", 'connection'), $config);
    } catch (Exception $e) {
        $this->lastError = KurogoError::errorFromException($e);
        if (Kurogo::getSiteVar('DB_DEBUG')) {
            throw new Exception("Error connecting to database: " . $e->getMessage(), 0);
        } else {
            throw new Exception("Error connecting to database");
        }
    }
  }
  
  public function query($sql, $parameters=array(), $ignoreErrors=false, $catchErrorCodes=array())
  {
    $this->lastError = null;
    if (Kurogo::getSiteVar('DB_DEBUG')) {
        error_log("Query Log: $sql");
    }

    if (!$result = $this->connection->prepare($sql)) {
        return $this->errorHandler($sql, $this->connection->errorInfo(), $ignoreErrors, $catchErrorCodes);
    }

    $result->setFetchMode(PDO::FETCH_ASSOC);
    
    if (!$result->execute($parameters)) {
        return $this->errorHandler($sql, $result->errorInfo(), $ignoreErrors, $catchErrorCodes);
    }

    return $result;
  }

  private function errorHandler($sql, $errorInfo, $ignoreErrors, $catchErrorCodes) {

        if (Kurogo::getSiteVar('DB_DEBUG')) {
           $e = new Exception (sprintf("Error with %s: %s", $sql, $errorInfo[2]), $errorInfo[1]);
        }
        
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

        if (Kurogo::getSiteVar('DB_DEBUG')) {
            throw $e;
        } else {
            error_log(sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        }
  }

  public function quote($string) {
    return $this->connection->quote($string);
  }

  public function ping() {
    if(!$this->connection->ping()) {
      $this->connection->close();
      $this->connection = NULL;
      $this->init();
    }
  }
  
  public function beginTransaction()
  {
    return $this->connection->beginTransaction();
  }

  public function commit()
  {
    return $this->connection->commit();
  }
  
  public static function lockTable($table)
  {
  }

  public static function unlockTable()
  {
  }

  public function lastInsertId()
  {
      return $this->connection->lastInsertId();
  }
  
  public static function getDBTypes() {
    return array(
        'mysql'=>'MySQL',
        'sqlite'=>'SQLite'
    );
  }
}

