<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once($docRoot . "/mobi-config/mobi_lib_constants.php");

class db {
  public static $connection = NULL;

  private static $host = MYSQL_HOST;
  private static $username = MYSQL_USER;
  private static $passwd = MYSQL_PASS;
  private static $db = MYSQL_DBNAME;

  public static function init() {
    if(!self::$connection) {
      self::$connection = new mysqli(self::$host, self::$username, self::$passwd, self::$db);
    }
  }
}

db::init();

?>
