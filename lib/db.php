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
  const IGNORE_ERRORS=true;

  const CONSTRAINT_VIOLATION_ERROR=23000;
  
  public function __construct($config=null)
  {
    if (!is_array($config)) {
        if (!$config instanceOf Config) {
           $config = $GLOBALS['siteConfig'];
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
    $db_type = isset($config['DB_TYPE']) ? $config['DB_TYPE'] : null;
    if (!file_exists(LIB_DIR . "/db/db_$db_type.php")) {
        throw new Exception("Database type $db_type not found");
    }
    
    require_once(LIB_DIR . "/db/db_$db_type.php");
    $this->connection = call_user_func(array("db_$db_type", 'connection'), $config);
  }
  
  public function query($sql, $parameters=array(), $ignoreErrors=false, $catchErrorCodes=array())
  {
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
        if ($ignoreErrors) {
            return;
        }

        // prevent the default error handling mechanism
        // from triggerring in the rare case of expected
        // errors such as unique field violations
        if(in_array($errorInfo[0], $catchErrorCodes)) {
            return $errorInfo;
        }

        if (Kurogo::getSiteVar('DB_DEBUG')) {
            throw new Exception (sprintf("Error with %s: %s", $sql, $errorInfo[2]));
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
}

