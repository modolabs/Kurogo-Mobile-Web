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
  
  public function query($sql, $parameters=array(), $ignoreErrors=false)
  {
    if ($GLOBALS['siteConfig']->getVar('DB_DEBUG')) {
        error_log("Query Log: $sql");
    }

    if (!$result = $this->connection->prepare($sql)) {
        if ($ignoreErrors) {
            return;
        }
        $errorInfo = $this->connection->errorInfo();
        if ($GLOBALS['siteConfig']->getVar('DB_DEBUG')) {
            throw new Exception (sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        } else {
            error_log(sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        }
        return;
    }

    $result->setFetchMode(PDO::FETCH_ASSOC);
    
    if (!$result->execute($parameters)) {
        if ($ignoreErrors) {
            return;
        }
        $errorInfo = $result->errorInfo();
        if ($GLOBALS['siteConfig']->getVar('DB_DEBUG')) {
            throw new Exception (sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        } else {
            error_log(sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        }
    }

    return $result;
  }

  public function escape($string) {
    return $this->connection->real_escape_string($string);
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
}



