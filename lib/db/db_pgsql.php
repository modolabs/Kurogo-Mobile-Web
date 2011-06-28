<?php
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


