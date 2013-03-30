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
            throw new KurogoConfigurationException("SQLite file not specified");
        }
        
        if (!file_exists($dsn_data['DB_FILE'])) {
            $create = isset($dsn_data['DB_CREATE']) && $dsn_data['DB_CREATE'];
            if (!$create) {
                throw new KurogoConfigurationException("SQLite file does not exist.");
            }
        }
        
        $dsn = sprintf("%s:%s", 'sqlite', $dsn_data['DB_FILE']);
        $connection = new PDO($dsn);
        return $connection;
    }
}

