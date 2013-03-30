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
  * PostgreSQL database abstraction
  * @package Database
  */

/**
  * PostgreSQL database abstraction
  * @package Database
  */
class db_pgsql extends db
{
    public static function connection($dsn_data)
    {
        $dsn = sprintf("%s:host=%s;dbname=%s", 'pgsql', $dsn_data['DB_HOST'], $dsn_data['DB_DBNAME']);

        if (isset($dsn_data['DB_PORT']) && !empty($dsn_data['DB_PORT'])) {
            $dsn .= ';port='. $dsn_data['DB_PORT'];
        }

        $connection = new PDO($dsn, $dsn_data['DB_USER'], $dsn_data['DB_PASS']);
        return $connection;
    }

  public static function lockTable($table)
  {
     $this->query("LOCK TABLE $table");
  }

  public static function unlockTable()
  {
     $this->query("UNLOCK TABLE $table");
  }

  public static function pgVersion()
  {
    // pg_version is only defined in postgres 7.4 and later
    if (function_exists('pg_version')) {
      return pg_version(self::connection());
      
    } else {
      // 7.4 is pretty old so older versions probably
      // won't support what we're looking for
      return 0;
    }
  }
}


