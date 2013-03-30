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

        if (!isset($dsn_data['DB_PASS'])) {
        	$dsn_data['DB_PASS'] = '';
        }

        if (!isset($dsn_data['DB_DBNAME']) || empty($dsn_data['DB_DBNAME'])) {
            throw new KurogoConfigurationException("MySQL database not specified");
        }

        $dsn = sprintf("%s:host=%s;dbname=%s", 'mysql', $dsn_data['DB_HOST'], $dsn_data['DB_DBNAME']);
        
        if (isset($dsn_data['DB_PORT']) && !empty($dsn_data['DB_PORT'])) {
            $dsn .= ';port='. $dsn_data['DB_PORT'];
        }
        
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


