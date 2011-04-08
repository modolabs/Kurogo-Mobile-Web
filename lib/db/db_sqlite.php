<?php
/**
  * SQLite database abstraction
  * @package Database
  */

/**
  * SQLite database abstraction
  * @package Database
  */

class db_sqlite extends db
{
    public static function connection($dsn_data)
    {
        if (!isset($dsn_data['DB_FILE']) || empty($dsn_data['DB_FILE'])) {
            throw new Exception("SQLite file not specified");
        }
        
        $dsn = sprintf("%s:%s", 'sqlite', $dsn_data['DB_FILE']);
        $connection = new PDO($dsn);
        return $connection;
    }
}

