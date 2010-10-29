<?php

class db_sqlite extends db
{
    public static function connection($dsn_data)
    {
        $dsn = sprintf("%s:%s", 'sqlite', $dsn_data['DB_FILE']);
        $connection = new PDO($dsn);
        return $connection;
    }
}

