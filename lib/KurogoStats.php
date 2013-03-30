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
  * @package Core
  */

Kurogo::includePackage('db');

class KurogoStats { 

	private $conn;
	protected static $tableSharding = 'month';
	
	private static function connection() {
	    static $conn;
	    if (!$conn) {
	        $conn = SiteDB::connection();
	    }
	    
	    return $conn;
	}

	static $pagetypes = Array(
        'tablet'=> 'Tablet',
        'compliant' => 'Compliant',
        'basic' => 'Basic',
    );
	
	static $platforms = Array(
        'iphone' => 'iPhone',
        'android' => 'Android',
        'webos' => 'webOS',
        'winmo' => 'Windows Mobile',
        'blackberry' => 'BlackBerry',
        'bbplus' => 'Advanced BlackBerry',
        'symbian' => 'Symbian',
        'palmos' => 'Palm OS',
        'featurephone' => 'Other Phone',
        'computer' => 'Computer',
    );
    
	private static function setVisitCookie($visitID=null) {
        if (empty($visitID)) {
            $visitID = md5(uniqid(rand(), true));
        }
        setCookie('visitID', $visitID, time() + Kurogo::getOptionalSiteVar('KUROGO_VISIT_LIFESPAN', 1800), COOKIE_PATH);            
		return $visitID;
	}

	private static function isFromThisSite($url) {
	    if (empty($url)) {
	        return false;
	    }
	           
        return strncmp($url, FULL_URL_BASE, strlen(FULL_URL_BASE))===0;
	}

	private static function isFromModule($url, $module) {
	    if (empty($url)) {
	        return false;
	    }

        $moduleURL = FULL_URL_BASE . $module .'/';
        return strncmp($url, $moduleURL, strlen($moduleURL))===0;
	}
	
	private static function getVisitID($service) {
	    $visitID = isset($_COOKIE['visitID']) ? $_COOKIE['visitID'] : null;
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        //api requests can assume to use the same visit since they will get their cookie right away
	    if (empty($visitID) || ($service=='web' && !self::isFromThisSite($referrer))) {
    		$visitID = null;
        }
        
        $visitID = self::setVisitCookie($visitID);
		return $visitID;
	}

    private static function getStatsTable($time = 0) {
        $time = $time > 0 ? $time : time();
        $tableSharding = Kurogo::getOptionalSiteVar('KUROGO_STATS_SHARDING_TYPE', self::$tableSharding);
        $tableName = self::getStatsTablePrefix();
        
        if (!$tableSharding) {
            return $tableName;
        }

        $time = self::foramtTimePosition($time, $tableSharding);
        
        $tableName = $tableName . '_' . date('Y_m_d', $time);

        return $tableName;
    }
    
    private static function foramtTimePosition($time, $sharding) {
        switch ($sharding) {
            case 'week':
                $time = $time - (86400 * (date('w', $time)));
                $time = mktime(0,0,0,date('m', $time),date('d', $time),date('Y', $time));
                break;
            case 'day':
                $time = mktime(0,0,0,date('m', $time),date('d', $time),date('Y', $time));
                break;
            case 'month':
                $time = mktime(0,0,0,date('m', $time), 1, date('Y', $time));
                break;
            default:
                throw new KurogoConfigurationException("The KUROGO_STATS_SHARDING_TYPE config error in site.ini.");
                break;
        }
        
        return $time;
    }
    
    private static function getDBTables() {
        static $tables;
        
        if (!$tables) {
            $conn = self::connection();
            $tables = $conn->getTables();
        }
        return $tables;
    }

    private static function getStatsTablesForUpdating($startTimestamp) {
        $tableSharding = Kurogo::getOptionalSiteVar('KUROGO_STATS_SHARDING_TYPE', self::$tableSharding);
        $tableName = self::getStatsTablePrefix();

        $statsTables = array();

        //parse all tables of databases
        $timeForTables = array();
        //$conn = self::connection();
        if ($allTables = self::getDBTables()) {
            foreach ($allTables as $key => $table) {
                if (preg_match('/^'.preg_quote($tableName, '/').'_(.*)$/is', $table, $matches)) {
                    if (isset($matches[1]) && $matches[1]) {
                        list($year, $month, $day) = explode('_', $matches[1]);
                        $timeForTables[$table] = mktime(0, 0, 0, $month, $day, $year);
                    }
                }
            }
        }

        $startInterval = self::foramtTimePosition($startTimestamp, $tableSharding);

        //start filter the tables
        foreach ($timeForTables as $table => $time) {
            if ($time >= $startInterval) {
                $statsTables[] = $table;
            }
        }

        $statsTables = array_unique($statsTables);
        if (!$statsTables) {
            return null;
        } elseif (count($statsTables) == 1) {
            return current($statsTables);
        } else {
            $tablesString = array();
            foreach ($statsTables as $table) {
                $tablesString[] = "SELECT * FROM " . $table;
            }

            return '(' . implode(' UNION ALL ', $tablesString) . ') AS kurogo_stats';
        }
    }
    
    private static function getStatsTablePrefix() {
        return Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
    }
    
    private static function getStatsTables($chartData) {
        $tableSharding = Kurogo::getOptionalSiteVar('KUROGO_STATS_SHARDING_TYPE', self::$tableSharding);
        $tableName = self::getStatsTablePrefix();
        $summaryTable = Kurogo::getOptionalSiteVar("KUROGO_STATS_SUMMARY_TABLE", "kurogo_stats_module_v1");

        if (!$tableSharding) {
            return $tableName;
        }
        
        if (isset($chartData['summarytable']) && $chartData['summarytable']) {
            if (isset($chartData['usevisittable']) && $chartData['usevisittable']) {
                return $summaryTable.'_visits';
            }
            return $summaryTable;
        }

        // Not using summary table, this site is not using the default configs.
        // Show them a nice error explaining the problem:
        throw new KurogoStatsConfigurationException("Not using summary tables.");
        
        
        $statsTables = array();
        
        //parse all tables of databases
        $timeForTables = array();
        //$conn = self::connection();
        if ($allTables = self::getDBTables()) {
            foreach ($allTables as $key => $table) {
                if (preg_match('/^'.preg_quote($tableName, '/').'_(.*)$/is', $table, $matches)) {
                    if (isset($matches[1]) && $matches[1]) {
                        list($year, $month, $day) = explode('_', $matches[1]);
                        $timeForTables[$table] = mktime(0, 0, 0, $month, $day, $year);
                    }
                }
            }
        }
        
        $intervalForTable = array(
            'start' => 0,
            'end'   => 0
        );
        if (isset($chartData['start']) && $chartData['start'] > 0) {
            $intervalForTable['start'] = self::foramtTimePosition($chartData['start'], $tableSharding);
        }
        if (isset($chartData['end']) && $chartData['end'] > 0) {
            $intervalForTable['end'] = self::foramtTimePosition($chartData['end'], $tableSharding);
        }
        
        //start filter the tables
        foreach ($timeForTables as $table => $time) {
            if ($intervalForTable['start'] > 0 && $intervalForTable['end'] > 0) {
                if ($time >= $intervalForTable['start'] && $time <= $intervalForTable['end']) {
                    $statsTables[] = $table;
                }
            } elseif ($intervalForTable['start'] > 0) {
                if ($time >= $intervalForTable['start']) {
                    $statsTables[] = $table;
                }
            } elseif ($intervalForTable['end'] < 0) {
                if ($time <= $intervalForTable['end']) {
                    $statsTables[] = $table;
                }
            } else {
                $statsTables[] = $table;
            }
        }

        $statsTables = array_unique($statsTables);
        if (!$statsTables) {
            return null;
        } elseif (count($statsTables) == 1) {
            return current($statsTables);
        } else {
            $tablesString = array();
            foreach ($statsTables as $table) {
                $tablesString[] = "SELECT * FROM " . $table;
            }
            
            return '(' . implode(' UNION ALL ', $tablesString) . ') AS kurogo_stats';
        }
    }
    
    private static function createSummaryTables($table) {
        $visitsTable = $table.'_visits';
        $createSQL = array();
        $conn = self::connection();
        switch($conn->getDBType()) {
            case 'mysql':
                $createSQL[] = "CREATE TABLE $table (
                                    id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                    timestamp int(11),
                                    date datetime,
                                    service char(3),
                                    site char(32),
                                    moduleID varchar(32),
                                    pagetype varchar(16),
                                    platform varchar(16),
                                    viewCount int(11),
                                    sizeCount int(11),
                                    elapsedAvg float(12,6),
                                    PRIMARY KEY (`id`),
                                    KEY `service` (`service`),
                                    KEY `moduleID` (`moduleID`),
                                    KEY `pagetype` (`pagetype`),
                                    KEY `platform` (`platform`),
                                    KEY `timestamp` (`timestamp`)
                                )";
                $createSQL[] = "CREATE TABLE $visitsTable (
                                    id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                    timestamp int(11),
                                    date datetime,
                                    service char(3),
                                    site char(32),
                                    visitCount int(11),
                                    PRIMARY KEY (`id`),
                                    KEY `service` (`service`),
                                    KEY `timestamp` (`timestamp`)
                                )";
                break;
            case 'sqlite':
                //$createSQL = self::createSQLForMysql($table);
                $createSQL[] = "CREATE TABLE $table (
                                    id integer PRIMARY KEY autoincrement,
                                    timestamp int(11),
                                    date datetime,
                                    service char(3),
                                    site char(32),
                                    moduleID varchar(32),
                                    pagetype varchar(16),
                                    platform varchar(16),
                                    viewCount int(11),
                                    sizeCount int(11),
                                    elapsedAvg float
                                )";
                $createSQL[] = "CREATE INDEX key_service_summary ON $table (service)";
                $createSQL[] = "CREATE INDEX key_moduleID_summary ON $table (moduleID)";
                $createSQL[] = "CREATE INDEX key_pagetype_summary ON $table (pagetype)";
                $createSQL[] = "CREATE INDEX key_platform_summary ON $table (platform)";
                $createSQL[] = "CREATE INDEX key_timestamp_summary ON $table (timestamp)";
                $createSQL[] = "CREATE TABLE $visitsTable (
                                    id integer PRIMARY KEY autoincrement,
                                    timestamp int(11),
                                    date datetime,
                                    service char(3),
                                    site char(32),
                                    visitCount int(11)
                                )";
                $createSQL[] = "CREATE INDEX key_service_summary_visits ON $visitsTable (service)";
                $createSQL[] = "CREATE INDEX key_timestamp_summary_visits ON $visitsTable (timestamp)";
                break;
            default:
                throw new Exception("Stats module do not support " . $conn->getDBType());
        }

        $checkSql = "SELECT 1 FROM $table";
        if (!$result = $conn->query($checkSql, array(), db::IGNORE_ERRORS)) {
            foreach ($createSQL as $sql) {
                $conn->query($sql);
            }
        }        
        
        return true;
    }
 
    /**
     * summary the stats data. the data should be updated to the summary stats table
    */
    protected static function summaryStatsData(&$statsData, &$statsVisitsData, $data) {
        $day = date('Y-m-d', $data['timestamp']);
        $statsKey = $day.'@@'.$data['service'].'@@'.$data['site'].'@@'.$data['moduleID'].'@@'.$data['pagetype'].'@@'.$data['platform'];
        $statsVisitsKey = $day.'@@'.$data['service'].'@@'.$data['site'];

        // Main Summary Table
        if (!isset($statsData[$statsKey])) {
            $statsData[$statsKey] = array(
                'viewCount' => 1,
                'sizeCount' => $data['size'],
                'elapsed'   => $data['elapsed'],
                'summaryTimes' => 1,
            );
        } else {
            $statsData[$statsKey]['viewCount'] +=  1;
            $statsData[$statsKey]['sizeCount'] += $data['size'];
            $statsData[$statsKey]['elapsed'] += $data['elapsed'];
            $statsData[$statsKey]['summaryTimes'] += 1;
        }

        // Visits Summary Table
        if (!isset($statsVisitsData[$statsVisitsKey])) {
            $statsVisitsData[$statsVisitsKey] = array(
                'visitCount' => array($data['visitID']=>$data['visitID']),
            );
        } else {
            $statsVisitsData[$statsVisitsKey]['visitCount'][$data['visitID']] = $data['visitID'];
        }
    }

    private static function deleteFromSummaryTablesAfterTimestamp($timestamp){
        $summaryTable = Kurogo::getOptionalSiteVar("KUROGO_STATS_SUMMARY_TABLE","kurogo_stats_module_v1");
        $summaryVisitsTable = $summaryTable.'_visits';
        $conn = self::connection();
        $sql = "DELETE FROM $summaryTable WHERE timestamp >= $timestamp";
        $conn->query($sql, array(), db::IGNORE_ERRORS);
        $sql = "DELETE FROM $summaryVisitsTable WHERE timestamp >= $timestamp";
        $conn->query($sql, array(), db::IGNORE_ERRORS);
    }
    
    /**
     * export the stats data to the database
     */
    public static function exportStatsData($startTimestamp = null) {

        $site = Kurogo::sharedInstance()->getSite();
        $baseLogDir = $site->getBaseLogDir();
        $logDir = $site->getLogDir();
        $statsLogFileName = Kurogo::getOptionalSiteVar('KUROGO_STATS_LOG_FILENAME', 'kurogo_stats_log_v1');

        //if the base dir is different then the log dir then get all files with the log file name
        if ($baseLogDir != $logDir) {
            $logFiles = glob($baseLogDir . DIRECTORY_SEPARATOR . "*".  DIRECTORY_SEPARATOR . $statsLogFileName);
        } else {
            $logFiles = array($logDir . DIRECTORY_SEPARATOR . $statsLogFileName);
        }
        
        //cycle through all log files
        foreach ($logFiles as $statsLogFile) {
            if (!file_exists($statsLogFile)) {
                continue;
            }
            
            $today = date('Ymd', time());

            //copy the log file
            $tempLogFolder = Kurogo::tempDirectory();
            $statsLogFileCopy = rtrim($tempLogFolder,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "stats_log_copy.$today";

            if (!is_writable($tempLogFolder)) {
                throw new Exception("Unable to write to Temporary Directory $tempLogFolder");
            }

            if (!rename($statsLogFile, $statsLogFileCopy)) {
                Kurogo::log(LOG_DEBUG, "Failed to rename $statsLogFile to $statsLogFileCopy", 'kurogostats');
                return; 
            }

            $handle = fopen($statsLogFileCopy, 'r');
            $startDate = '';
            while (!feof($handle)) {
                $line = trim(fgets($handle, 1024));
                $value = explode("\t", $line);
                if ((count($value) != count(self::validFields())) || !isset($value[0]) || intval($value[0]) <= 0) {
                    continue;
                }

                if(!$startDate){
                    $startTimestamp = $value[0];
                    $startDate = date('Y-m-d', $startTimestamp);
                    // If startTimestamp is during today
                    // Set startTimestamp to the beginning of today
                    if($startDate == date('Y-m-d')){
                        list($year, $month, $day) = explode('-', $startDate);
                        $startTimestamp = mktime(0, 0, 0, $month, $day, $year);
                    }
                }

                //insert the raw data to the database
                $logData = array_combine(self::validFields(), $value);
                self::insertStatsToMainTable($logData);
            }
            fclose($handle);
            unlink($statsLogFileCopy);
        
            self::updateSummaryFromShards($startTimestamp);
        }
    }

    public static function updateSummaryFromShards($startTimestamp){
        // Delete summary visit data for today.
        self::deleteFromSummaryTablesAfterTimestamp($startTimestamp);

        // Get summary data from $startTimestamp until now.
        $conn = self::connection();
        if(!$tables = self::getStatsTablesForUpdating($startTimestamp)){
            return;
        }
        $sql = "SELECT * FROM " . $tables;
        $sql .= " WHERE timestamp >= $startTimestamp";

        if(!$data = $conn->query($sql)){
            return;
        }

        $statsData = array();
        $statsVisitsData = array();
        while ($row = $data->fetch()) {
            unset($row['id']);
            $logData = array_combine(self::validFields(), $row);
            // Build the summary visits data structure.
            self::summaryStatsData($statsData, $statsVisitsData, $logData);
        }
        // Unset resource to avoid memory problems.
        unset($data);

        if ($statsData) {
            foreach ($statsData as $statsKey => $statsValue) {
                self::updateStatsToSummaryTable($statsKey, $statsValue);
            }
        }

        if ($statsVisitsData) {
            foreach ($statsVisitsData as $statsKey => $statsValue) {
                self::updateStatsVisitsToSummaryTable($statsKey, $statsValue);
            }
        }
    }
    
    /**
     * save the raw data to sharding stats tables
     */
    public static function insertStatsToMainTable($logData) {
        if (!isset($logData['timestamp']) || $logData['timestamp'] <= 0) {
            return false;
        }
        
        try {
            $conn = self::connection();
        } catch (KurogoDataServerException $e) {
            throw new KurogoConfigurationException("Database not configured for statistics. To disable stats, set STATS_ENABLED=0 in site.ini");
        }
        
        $table = self::getStatsTable($logData['timestamp']);
        
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", 
            $table, 
            implode(",", array_keys($logData)), 
            implode(",", array_fill(0, count($logData), '?'))
        );
        if (!$result = $conn->query($sql, array_values($logData), db::IGNORE_ERRORS)) {
            self::createStatsTables($table);
            $result = $conn->query($sql, array_values($logData));
        }
        
        return $result;
    }
    
    protected static function updateStatsToSummaryTable($statsKey, $statsValue) {
        $statsArray = explode('@@', $statsKey);
        list($year, $month, $day) = explode('-', $statsArray[0]);
        $statsDate = $year . '-' . $month . '-' . $day . ' 00:00:00';
        $statsDateTime = mktime(0, 0, 0, $month, $day, $year);

        $insertData = array(
            'timestamp' => $statsDateTime,
            'date'      => $statsDate,
            'service'   => $statsArray[1],
            'site'      => $statsArray[2],
            'moduleID'  => $statsArray[3],
            'pagetype'  => $statsArray[4],
            'platform'  => $statsArray[5]
        );

        $filters = array();
        $params = array();
        foreach ($insertData as $field => $value) {
            $filters[] = "$field = ?";
            $params[] = $value;
        }

        try {
            $conn = self::connection();
        } catch (KurogoDataServerException $e) {
            throw new KurogoConfigurationException("Database not configured for statistics. To disable stats, set STATS_ENABLED=0 in site.ini");
        }
        
        $summaryTable = Kurogo::getOptionalSiteVar("KUROGO_STATS_SUMMARY_TABLE","kurogo_stats_module_v1");
        $sql = "SELECT * FROM " . $summaryTable;
        $sql .= $filters ? " WHERE " . implode(' AND ', $filters) : '';
        
        //check the database contain the summary data
        $result = array();
        if (!$data = $conn->limitQuery($sql, $params, true)) {
            self::createSummaryTables($summaryTable);
        } else {
            while ($row = $data->fetch()) {
                $result = $row;
            }
        }
        
        //if the database has the summary data, it should do update
        if ($result) {
            $summaryElapsedTime = $result['elapsedAvg'] * $result['viewCount'];
            $newElapsedTime = $statsValue['elapsed'];
            $totalElapsedTime = $summaryElapsedTime + $newElapsedTime;
            $totalViewCount = $statsValue['viewCount'] + $result['viewCount'];
            // If for some strange reason the total view count is 0, use the old elapsed average.
            $newAverageElapsedTime =  $totalViewCount ? ($totalElapsedTime/$totalViewCount) : $result['elapsedAvg'];

            $updateData = array(
                $result['viewCount'] + $statsValue['viewCount'],
                $result['sizeCount'] + $statsValue['sizeCount'],
                $newAverageElapsedTime,
                $result['id']
            );
            
            $updateSql = "UPDATE $summaryTable SET viewCount = ?, sizeCount = ?, elapsedAvg = ? WHERE id = ?";
            return $conn->query($updateSql, $updateData);
        //if the database don't have the summary data, it should do insert
        } else {
            $insertValue = array(
                'viewCount'  => $statsValue['viewCount'],
                'sizeCount'  => $statsValue['sizeCount'],
                'elapsedAvg' => $statsValue['elapsed'] / $statsValue['summaryTimes'],
            );
            $insertData = array_merge($insertData, $insertValue);
            $insertSql = sprintf("INSERT INTO %s (%s) VALUES (%s)", 
                $summaryTable, 
                implode(",", array_keys($insertData)), 
                implode(",", array_fill(0, count($insertData), '?'))
            );
            
            return $conn->query($insertSql, array_values($insertData));
        }
    }

    protected static function updateStatsVisitsToSummaryTable($statsKey, $statsValue) {
        $statsArray = explode('@@', $statsKey);
        list($year, $month, $day) = explode('-', $statsArray[0]);
        $statsDate = $year . '-' . $month . '-' . $day . ' 00:00:00';
        $statsDateTime = mktime(0, 0, 0, $month, $day, $year);

        $insertData = array(
            'timestamp' => $statsDateTime,
            'date'      => $statsDate,
            'service'   => $statsArray[1],
            'site'      => $statsArray[2],
        );
        
        $filters = array();
        $params = array();
        foreach ($insertData as $field => $value) {
            $filters[] = "$field = ?";
            $params[] = $value;
        }
        
        $conn = self::connection();
        $table = Kurogo::getOptionalSiteVar("KUROGO_STATS_SUMMARY_TABLE","kurogo_stats_module_v1");
        $summaryTable = $table.'_visits';
        $sql = "SELECT * FROM " . $summaryTable;
        $sql .= $filters ? " WHERE " . implode(' AND ', $filters) : '';
        
        //check the database contain the summary data
        $result = array();
        if (!$data = $conn->limitQuery($sql, $params, true)) {
            self::createSummaryTables($table);
        } else {
            while ($row = $data->fetch()) {
                $result = $row;
            }
        }
        
        //if the database has the summary data, it should do update
        if ($result) {
            $updateData = array(
                $result['visitCount'] + count($statsValue['visitCount']),
                $result['id']
            );
            
            $updateSql = "UPDATE $summaryTable SET visitCount = ? WHERE id = ?";
            return $conn->query($updateSql, $updateData);
        //if the database don't have the summary data, it should do insert
        } else {
            $insertValue = array(
                'visitCount' => count($statsValue['visitCount']),
            );
            $insertData = array_merge($insertData, $insertValue);
            $insertSql = sprintf("INSERT INTO %s (%s) VALUES (%s)", 
                $summaryTable, 
                implode(",", array_keys($insertData)), 
                implode(",", array_fill(0, count($insertData), '?'))
            );
            
            return $conn->query($insertSql, array_values($insertData));
        }
    }
    
    public static function logView($service, $id, $page, $data, $dataLabel, $size=0) {
        switch ($service)
        {
            case 'web':
            case 'api':
                break;
            default;
                throw new Exception("Invalid service $service");
                break;
        }
        
		$deviceClassifier = Kurogo::deviceClassifier();

        $ip = Kurogo::determineIP();
        $requestURI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $visitID = self::getVisitID($service);

        if (Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            $session = Kurogo::getSession();
            $user = $session->getUser();
        } else {
            $user = false;
        }

        $statsLogFilename = Kurogo::getOptionalSiteVar('KUROGO_STATS_LOG_FILENAME', "kurogo_stats_log_v1");
        $statsLogFile = LOG_DIR . DIRECTORY_SEPARATOR . $statsLogFilename;
        
        $current = time();
        $logData = array(
		    'timestamp' => time(),
		    'date'      => date('Y-m-d H:i:s', $current),
		    'service'   => $service,
		    'site'      => SITE_KEY,
		    'requestURI'=> $requestURI,
		    'referrer'  => $referrer,
		    'referredSite'   => intval(self::isFromThisSite($referrer)),
		    'referredModule' => intval(self::isFromModule($referrer, $id)),
		    'userAgent' => $userAgent,
		    'ip'        => $ip,
		    'user'      => $user ? $user->getUserID() : '',
		    'authority' => $user ? $user->getAuthenticationAuthorityIndex() : '',
            'visitID'   => $visitID,            		    
		    'pagetype'  => $deviceClassifier->getPageType(),
		    'platform'  => $deviceClassifier->getPlatform(),
		    'moduleID'  => $id,
		    'page'      => $page,
		    'data'      => $data,
		    'dataLabel' => $dataLabel,
		    'size'      => $size,
		    'elapsed'   => Kurogo::getElapsed()
		);
		
		$fields = array_fill(0, count($logData), '%s');
		$data = array_merge(array(implode("\t", $fields)), array_values($logData));
		
		$content = call_user_func_array('sprintf', $data) . PHP_EOL;
		self::fileAppend($statsLogFile, $content);
		return true;
    }
    
    private static function createSQLForMysql($table) {
        $createSQL = "CREATE TABLE $table (
                id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                timestamp int(11),
                date datetime,
                service char(3),
                site char(32),
                requestURI varchar(256),
                referrer varchar(512),
                referredSite bool,
                referredModule bool,
                userAgent varchar(256),
                ip varchar(39),
                user varchar(64),
                authority varchar(32),
                visitID char(32),
                pagetype varchar(16),
                platform varchar(16),
                moduleID varchar(32),
                page varchar(32),
                data varchar(256),
                dataLabel varchar(256),
                size int(11),
                elapsed float(12,6),
                PRIMARY KEY (`id`),
                KEY `service` (`service`),
                KEY `moduleID` (`moduleID`),
                KEY `pagetype` (`pagetype`),
                KEY `platform` (`platform`),
                KEY `visitID` (`visitID`),
                KEY `timestamp` (`timestamp`)
            ) ENGINE=MyISAM";
        return array($createSQL);
    }
    
    private static function createSQLForSqlite($table) {
        $createSQL = "CREATE TABLE $table (
                id integer PRIMARY KEY autoincrement,
                timestamp int(11),
                date datetime,
                service char(3),
                site char(32),
                requestURI varchar(256),
                referrer varchar(512),
                referredSite bool,
                referredModule bool,
                userAgent varchar(256),
                ip varchar(39),
                user varchar(64),
                authority varchar(32),
                visitID char(32),
                pagetype varchar(16),
                platform varchar(16),
                moduleID varchar(32),
                page varchar(32),
                data varchar(256),
                dataLabel varchar(256),
                size int(11),
                elapsed int(11)
            )";

        $prefix = self::getStatsTablePrefix();
        $indexSuffix = '';
		if (preg_match("/^($prefix)(_.+)/", $table, $bits)) {
			$indexSuffix = $bits[2];
		}
        $createIndex = array(
            "CREATE INDEX key_service{$indexSuffix} ON $table (service)",
            "CREATE INDEX key_moduleID{$indexSuffix} ON $table (moduleID)",
            "CREATE INDEX key_pagetype{$indexSuffix} ON $table (pagetype)",
            "CREATE INDEX key_platform{$indexSuffix} ON $table (platform)",
            "CREATE INDEX key_visitID{$indexSuffix} ON $table (visitID)",
            "CREATE INDEX key_timestamp{$indexSuffix} ON $table (timestamp)",
        );
        array_unshift($createIndex, $createSQL);
        return $createIndex;
    }
    
    private static function createStatsTables($table) {
        $createSQL = array();
        $conn = self::connection();
        switch($conn->getDBType()) {
            case 'sqlite':
                $createSQL = self::createSQLForSqlite($table);
                break;
            case 'mysql':
                $createSQL = self::createSQLForMysql($table);
                break;
            default:
                throw new Exception("Stats module do not support " . $conn->getDBType());
        }

        $checkSql = "SELECT 1 FROM $table";
        $conn = self::connection();
        if (!$result = $conn->query($checkSql, array(), db::IGNORE_ERRORS)) {
            foreach ($createSQL as $sql) {
                $conn->query($sql);
            }
            //return $conn->query($createSQL);
        }        
        
        return true;
    }

    public static function isValidGroup($field) {
        $groupFields = array_merge(array(
            'hour'
        ), self::validFields());
        return in_array($field, $groupFields);
    }
    
    public static function isValidField($field) {
        return in_array($field, self::validFields()) || in_array($field, self::validSummaryFields());
    }
    
    public static function validFields() {
        return array(
            'timestamp',
            'date',
            'service',
            'site',
            'requestURI',
            'referrer',
            'referredSite',
            'referredModule',
            'userAgent',
            'ip',
            'user',
            'authority',
            'visitID',
            'pagetype',
            'platform',
            'moduleID',
            'page',
            'data',
            'dataLabel',
            'size',
            'elapsed'
        );
    }

    public static function validSummaryFields() {
        return array(
            'timestamp',
            'date',
            'service',
            'site',
            'moduleID',
            'pagetype',
            'platform',
            'viewCount',
            'visitCount',
            'sizeCount',
            'elapsedAvg'
        );
    }
    
    protected static function getGroupFields($group) {
        $extraFields = array();
        foreach ($group as &$val) {
            switch ($val) 
            {
                case 'hour':
                    $conn = self::connection();
                    switch($conn->getDBType()) {
                        case 'sqlite':
                            $val = 'cast(strftime("%H", date) as INTEGER) as hour';
                            break;
                        case 'mysql':
                            $val = "hour(date) as hour";
                            break;
                        default:
                            throw new Exception("hour grouping not handled for " . $conn->getDBType());
                    }                    
                    break; 
                case 'data':
                    $extraFields[] = 'dataLabel';
                    break;           
            }
        }
        return array_merge($group, $extraFields);
    }
    
    public static function retrieveStats(KurogoStatsOption $OptionObject, $chartData = array()) {
        // get data type, group and fields
        $type = $OptionObject->getType();
        $group = $OptionObject->getGroup();
        $fields = $OptionObject->getFields();
        
        switch ($type) {
            case 'count':
                if (count($fields)==0) {
                    $fields[] = "COUNT(*) AS count";
                } elseif (count($fields)==1) {
                    $fields[] = "COUNT(DISTINCT " . current($fields) .") AS count";
                } else {
                    throw new Exception("Counting can only include 0 or 1 fields");
                }
                break;
            case 'sum':
                if (count($fields)>1) {
                    throw new Exception("Sum logging type can only contain 1 field");
                }
                $fields = array("SUM(".current($fields).") AS sum");
                break;
            case 'avg':
                if (count($fields)>1) {
                    throw new Exception("Average logging type can only contain 1 field");
                }
                $fields = array("AVG(".current($fields).") AS avg");
                break;
            
        }

        // get the group fields        
		if ($group) {
			$fields = array_unique(array_merge($fields, self::getGroupFields($group)));
		}
		
        // build a list of parameters
        $filters = array();
        $params = array();
        foreach ($OptionObject->getFilters() as $filter) {
            $filters[] = $filter->getDBString();
            $params[] = $filter->getValue();
        }

        //prime the results as necessary
        $result = self::initStatsResult($OptionObject);
        
        // build the query
        if (!$tables = self::getStatsTables($chartData)) {
            if ($result) {
                return $result;
            } else {
                return 0;
            }
        }

        $sql = "SELECT " . implode(',', $fields) . " FROM " . $tables;
        $sql .= $filters ? " WHERE " . implode(' AND ', $filters) : '';
        $sql .= $group ? " GROUP BY " . implode(', ', $group) : '';

		//what happens where there are more than 1 group?
		if (count($group)>1) {
		    throw new Exception("Multi groups not functioning");
		}
        $groupString = implode(',', $group);

        if ($type =='count' && $OptionObject->getSortField()=='count') {
            $dir = $OptionObject->getSortDir() == SORT_ASC ? "ASC" : "DESC";
            $sql .= " ORDER BY count $dir, $groupString";
        } elseif ($type =='sum' && $OptionObject->getSortField()=='sum') {
            $dir = $OptionObject->getSortDir() == SORT_ASC ? "ASC" : "DESC";
            $sql .= " ORDER BY sum $dir, $groupString";
        }
        
        if ($OptionObject->getLimit()) {
            $sql .= " LIMIT " . $OptionObject->getLimit();
        }
        
        //query 
		$conn = self::connection();
        $data = $conn->query($sql, $params);
        while ($row = $data->fetch()) {
            if ($groupString && isset($row[$groupString])) {
                if ($groupString=='data') {
                    $result[$row[$groupString]] = array('label'=>$row['dataLabel'] ? $row['dataLabel'] : $row[$groupString], $type=>$row[$type]);
                } else {
                    $result[$row[$groupString]] = $row[$type];
                }
            } else {
                return $row[$type] ? $row[$type] : 0;
            }
        }
        
        if (($type=='count' && $OptionObject->getSortField()=='count') || ($type=='sum' && $OptionObject->getSortField()=='sum')) {
            if ($OptionObject->getSortDir()==SORT_ASC) {
                asort($result);
            } else {
                arsort($result);
            }

            if ($OptionObject->getLimit()) {
                $result = array_slice($result, 0, $OptionObject->getLimit(), true);
            }
        }

        return $result;
    }
    
    public static function initStatsResult(KurogoStatsOption $OptionObject) {
        $result = array();
        foreach ($OptionObject->getGroup() as $group) {
            switch ($group)
            {
                case 'moduleID':
                    if ($OptionObject->getService() == 'web') {
                        $moduleData = WebModule::getAllModules();
                    } else {
                        $moduleData = APIModule::getAllModules();
                    }
                    
                    $result = array_combine(array_keys($moduleData), array_fill(0,count($moduleData),0));
                    break;
                case 'platform':
                    $platforms = self::$platforms;
                    $result = array_combine(array_keys($platforms), array_fill(0,count($platforms),0));
                    break;
                case 'pagetype':
                    $pagetypes = self::$pagetypes;
                    $result = array_combine(array_keys($pagetypes), array_fill(0,count($pagetypes),0));
                    break;

                case 'hour':
                    $result = array_combine(range(0,23), array_fill(0,24,0));
                    break;
            }
        }
        return $result;
    }
    
    public static function isValidModuleID($service, $moduleID) {
        static $allModuleData = array(
            'web' => array(),
            'api' => array()
        );
        $moduleData = array();
        
        if (isset($allModuleData[$service]) && !empty($allModuleData[$service])) {
            $moduleData = $allModuleData[$service];
        }
        if (!$moduleData) {
            if ($service == 'web') {
                $allModuleData['web'] = WebModule::getAllModules();
                $moduleData = $allModuleData['web'];
            } else {
                $allModuleData['api'] = APIModule::getAllModules();
                $moduleData = $allModuleData['api'];
            }
        }
        
        return isset($moduleData[$moduleID]) ? true : false;
    }

    public static function getStartTimestamp($day){
        return strtotime($day);
    }

    public static function getEndTimestamp($day){
        return strtotime("+1 day", strtotime($day)) - 1;
    }

    public static function getTotalRowsPerDay($table, $startTimestamp, $endTimestamp){
        $conn = self::connection();
        $sql = "SELECT count(*) AS count FROM $table WHERE `timestamp` >= $startTimestamp AND `timestamp` <= $endTimestamp";
        $data = $conn->query($sql);
        if($result = $data->fetch()){
            return $result['count'];
        }
        return false;
    }

    public static function migrateData($table, $day, $limit = 50000, $updateSummaryOnly = false){
        if(!is_numeric($limit)){
            throw new KurogoException('Limit must be numeric. Default is 50000.');
        }
        $startTimestamp = self::getStartTimestamp($day);
        $endTimestamp = self::getEndTimestamp($day);

        if($totalRows = self::getTotalRowsPerDay($table, $startTimestamp, $endTimestamp)){
            $conn = self::connection();
            $statsData = array();
            $statsVisitsData = array();
            $fields = self::validFields();
            $currentRowsProcessed = 0;
            while ($currentRowsProcessed < $totalRows) {
                $sql = "SELECT * FROM $table WHERE `timestamp` >= $startTimestamp AND `timestamp` <= $endTimestamp ORDER BY `timestamp` ASC LIMIT $currentRowsProcessed, $limit";

                $data = $conn->query($sql);
                while ($row = $data->fetch()) {
                    $logData = array_combine($fields, $row);
                    // Insert log data into sharded table.
                    if(!$updateSummaryOnly){
                        self::insertStatsToMainTable($logData);
                    }
                
                    // Build the summary data structure.
                    self::summaryStatsData($statsData, $statsVisitsData, $logData);
                    $currentRowsProcessed++;
                }
                // Unset resource to avoid memory problems.
                unset($data);
            }

            if ($statsData) {
                foreach ($statsData as $statsKey => $statsValue) {
                    self::updateStatsToSummaryTable($statsKey, $statsValue);
                }
            }
            if ($statsVisitsData) {
                foreach ($statsVisitsData as $statsKey => $statsValue) {
                    self::updateStatsVisitsToSummaryTable($statsKey, $statsValue);
                }
            }
            return $currentRowsProcessed;
        }
        return 0;
    }

    public static function getTotalRows($table) 
    {
        $conn = self::connection();
        $sql = "SELECT count(*) as count FROM `$table`";
        $data = $conn->query($sql);
        if(!$result = $data->fetch()){
            throw new Exception("Could not get total rows");
        }
        return $result['count'];
    }

    public static function getStartDate($table){
        $conn = self::connection();
        $sql = "SELECT date(`date`) AS date FROM `$table` GROUP BY 1 ORDER BY 1 ASC LIMIT 1";
        $data = $conn->query($sql);
        if(!$result = $data->fetch()){
            throw new Exception("Could not get start date");
        }
        return $result['date'];
    }

    public static function getLastDateFromSummary(){
        $summaryTable = Kurogo::getOptionalSiteVar("KUROGO_STATS_SUMMARY_TABLE", "kurogo_stats_module_v1");
        $conn = self::connection();
        $sql = "SELECT date(`date`) AS date FROM `$summaryTable` GROUP BY 1 ORDER BY 1 DESC LIMIT 1";
        if(!$data = $conn->query($sql, array(), db::IGNORE_ERRORS)){
            self::createSummaryTables($summaryTable);
            $data = $conn->query($sql);
        }
        if(!$result = $data->fetch()){
            return false;;
        }
        return $result['date'];
    }

    public static function getLastDate($table){
        $conn = self::connection();
        $sql = "SELECT date(`date`) AS date FROM `$table` GROUP BY 1 ORDER BY 1 DESC LIMIT 1";
        $data = $conn->query($sql);
        if(!$result = $data->fetch()){
            throw new Exception("Could not get end date");
        }
        return $result['date'];
    }

    private static function fileAppend($file, $data = '') {
        if ($file) {
            $dir = dirname($file);
            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    throw new KurogoConfigurationException("could not create ".$dir);
                    return false;
                }
            }
            if ($handle = @fopen($file, 'a+')) {
                fwrite($handle, $data);
                fclose($handle);
            } else {
                Kurogo::log(LOG_ALERT, "Unable to write to $file", 'kurogostats');
            }
        }
    }
}

class KurogoStatsConfigurationException extends KurogoException {

}
