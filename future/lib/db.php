<?php


define('DB_NOT_SUPPORTED', 1);

class db {
  public static $connection = NULL;

  public static function init() {
    if(!self::$connection) {
      if (extension_loaded('mysql')) {
	      self::$connection = new mysqli(
	        $GLOBALS['siteConfig']->getVar('MYSQL_HOST'), 
	        $GLOBALS['siteConfig']->getVar('MYSQL_USER'), 
	        $GLOBALS['siteConfig']->getVar('MYSQL_PASS'), 
	        $GLOBALS['siteConfig']->getVar('MYSQL_DBNAME'));
      } else {
	      self::$connection = DB_NOT_SUPPORTED;
      }
    }
  }

  public static function escape($string) {
    return self::$connection->real_escape_string($string);
  }

  public static function ping() {
    if(!self::$connection->ping()) {
      self::$connection->close();
      self::$connection = NULL;
      self::init();
    }
  }
}

db::init();

?>
