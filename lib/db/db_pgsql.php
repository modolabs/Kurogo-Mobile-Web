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
}


