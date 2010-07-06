<?php

require_once "lib_constants.inc";

define('MYSQL_NOT_SUPPORTED', 1);

class db {
  public static $connection = NULL;

  private static $host = MYSQL_HOST;
  private static $username = MYSQL_USER;
  private static $passwd = MYSQL_PASS;
  private static $db = MYSQL_DBNAME;

  public static function init() {
    if(!self::$connection) {
      if (extension_loaded('mysql')) {
	self::$connection = new mysqli(self::$host, self::$username, self::$passwd, self::$db);
      } else {
	self::$connection = MYSQL_NOT_SUPPORTED;
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
