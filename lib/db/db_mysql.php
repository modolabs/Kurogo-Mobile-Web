<?php
/**
  * MySQL database abstraction
  * @package Database
  */

/**
  * MySQL database abstraction
  * @package Database
  */
class db_mysql extends db
{
    public static function connection($dsn_data)
    {
        if (!isset($dsn_data['DB_HOST']) || empty($dsn_data['DB_HOST'])) {
            throw new KurogoConfigurationException("MySQL host not specified");
        }

        if (!isset($dsn_data['DB_USER']) || empty($dsn_data['DB_USER'])) {
            throw new KurogoConfigurationException("MySQL user not specified");
        }

        if (!isset($dsn_data['DB_PASS']) || empty($dsn_data['DB_PASS'])) {
            throw new KurogoConfigurationException("MySQL password not specified");
        }

        if (!isset($dsn_data['DB_DBNAME']) || empty($dsn_data['DB_DBNAME'])) {
            throw new KurogoConfigurationException("MySQL database not specified");
        }

        $dsn = sprintf("%s:host=%s;dbname=%s", 'mysql', $dsn_data['DB_HOST'], $dsn_data['DB_DBNAME']);
        $connection = new PDO($dsn, $dsn_data['DB_USER'], $dsn_data['DB_PASS']);
        return $connection;
    }

    public static function lockTable($table)
    {
        $this->query("LOCK TABLE $table WRITE");
    }
    
    public static function unlockTable()
    {
        $this->query("UNLOCK TABLES");
    }
}


