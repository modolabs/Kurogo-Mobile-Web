<?php

class SiteDB
{
  public static $db = null;

  public function query($sql, $parameters=array())
  {
    if (!$connection = self::$db) {
        self::$db = new db($GLOBALS['siteConfig']);
        $connection = self::$db;
    }

    return $connection->query($sql, $parameters);  
  }        
}
