<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";

class DrupalDB {
  public static $connection = NULL;

  private static $host = DRUPAL_MYSQL_HOST;
  private static $username = DRUPAL_MYSQL_USER;
  private static $passwd = DRUPAL_MYSQL_PASS;
  private static $db = DRUPAL_MYSQL_DBNAME;

  public static function init() {
    if(!self::$connection) {
      self::$connection = new mysqli(self::$host, self::$username, self::$passwd, self::$db);
    }
  }
}

DrupalDB::init();

?>
