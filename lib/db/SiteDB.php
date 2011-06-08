<?php
/**
  * @package Database
  */

/**
  * @package Database
  */
class SiteDB
{
  public static $db = null;
  
  public static function connection()
  {
    if (!$connection = self::$db) {
        self::$db = new db();
        $connection = self::$db;
    }
    
    return $connection;
  }
}
