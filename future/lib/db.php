<?php

define('DB_NOT_SUPPORTED', 1);

class db {
  public static $connection = NULL;
  
  public static function connection()
  {
    self::init();
    return self::$connection;
  }

  public static function init() {
    if(!self::$connection) {
        $db_type = $GLOBALS['siteConfig']->getVar('DB_TYPE');
        $dsn_data = array(
            'DB_HOST'=>$GLOBALS['siteConfig']->getVar('DB_HOST'),
            'DB_USER'=>$GLOBALS['siteConfig']->getVar('DB_USER'),
            'DB_PASS'=>$GLOBALS['siteConfig']->getVar('DB_PASS'),
            'DB_DBNAME'=>$GLOBALS['siteConfig']->getVar('DB_DBNAME'),
            'DB_FILE'=>$GLOBALS['siteConfig']->getVar('DB_FILE')
        );
    
        require_once(LIB_DIR . "/db/db_$db_type.php");
        
        self::$connection = call_user_func(array("db_$db_type", 'connection'), $dsn_data);
    }
  }
  
  public static function query($sql, $parameters=array())
  {
        $connection = self::connection();
        if ($GLOBALS['siteConfig']->getVar('DB_DEBUG')) {
            error_log("Query Log: $sql");
        }

        if (!$result = $connection->prepare($sql)) {
            $errorInfo = $connection->errorInfo();
            error_log(sprintf("Error with %s: %s", $sql, $errorInfo[2]));
            return;
        }

        $result->setFetchMode(PDO::FETCH_ASSOC);
        
        if (!$result->execute($parameters)) {
            $errorInfo = $result->errorInfo();
            error_log(sprintf("Error with %s: %s", $sql, $errorInfo[2]));
        }

        return $result;
  }

  public static function escape($string) {
    $connection = self::connection();
    return $connection->real_escape_string($string);
  }

  public static function ping() {
    $connection = self::connection();
    if(!$connection->ping()) {
      self::$connection->close();
      self::$connection = NULL;
      self::init();
    }
  }
}



