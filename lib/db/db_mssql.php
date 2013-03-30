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
  * MS SQL database abstraction
  * @package Database
  */

/**
  * MS SQL database abstraction
  * @package Database
  */
  
/*
    This driver should function on Windows machines using the PDO driver found:
    http://msdn.microsoft.com/en-us/sqlserver/ff657782.aspx
*/
class db_mssql extends db
{
    public static function connection($dsn_data)
    {
        if (!isset($dsn_data['DB_HOST']) || empty($dsn_data['DB_HOST'])) {
            throw new KurogoConfigurationException("MS SQL host not specified");
        }

        $db_user = isset($dsn_data['DB_USER']) ? $dsn_data['DB_USER'] : '';
        $db_pass = isset($dsn_data['DB_PASS']) ? $dsn_data['DB_PASS'] : '';

        if (!isset($dsn_data['DB_DBNAME']) || empty($dsn_data['DB_DBNAME'])) {
            throw new KurogoConfigurationException("MS SQL database not specified");
        }

        $dsn = sprintf("%s:server=%s;Database=%s", 'sqlsrv', $dsn_data['DB_HOST'], $dsn_data['DB_DBNAME']);
        $connection = new PDO($dsn, $db_user, $db_pass);
        return $connection;
    }

    public static function lockTable($table)
    {
    }
    
    public static function unlockTable()
    {
    }
}


