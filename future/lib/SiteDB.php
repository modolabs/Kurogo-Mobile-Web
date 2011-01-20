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
        self::$db = new db($GLOBALS['siteConfig']);
        $connection = self::$db;
    }
    
    return $connection;
  }
}
