<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
