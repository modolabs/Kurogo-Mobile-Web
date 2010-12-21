<?php

class db_mysql extends db
{
    public static function connection($dsn_data)
    {
        $dsn = sprintf("%s:host=%s;dbname=%s", 'mysql', $dsn_data['DB_HOST'], $dsn_data['DB_DBNAME']);
        $connection = new PDO($dsn, $dsn_data['DB_USER'], $dsn_data['DB_PASS']);
        return $connection;
    }
}


