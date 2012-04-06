<?php
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
        'touch' => 'Touch',
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
        $tableName = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
        
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
    
    private static function listSources() {
        static $tables;
        
        if (!$tables) {
            $conn = self::connection();
            $tables = $conn->listSources();
        }
        return $tables;
    }
    
    private static function getStatsTables($chartData) {
        $tableSharding = Kurogo::getOptionalSiteVar('KUROGO_STATS_SHARDING_TYPE', self::$tableSharding);
        $tableName = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
        
        if (!$tableSharding) {
            return $table;
        }
        
        $statsTables = array();
        
        //parse all tables of databases
        $timeForTables = array();
        //$conn = self::connection();
        if ($allTables = self::listSources()) {
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
    
    private static function summaryTables() {
        return array(
            'mysql' => array(
                'kurogo_stats_module' => "
                    CREATE TABLE $table (
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
                        elapsedAvg int(11),
                        PRIMARY KEY (`id`),
                        KEY `service` (`service`),
                        KEY `moduleID` (`moduleID`),
                        KEY `pagetype` (`pagetype`),
                        KEY `platform` (`platform`),
                        KEY `timestamp` (`timestamp`)
                    )",
            ),
            'sqlite' => array(
            
            )
        );
    }
    
    private static function createSummaryTables($table) {
        //$table = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
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
                                    elapsedAvg int(11),
                                    PRIMARY KEY (`id`),
                                    KEY `service` (`service`),
                                    KEY `moduleID` (`moduleID`),
                                    KEY `pagetype` (`pagetype`),
                                    KEY `platform` (`platform`),
                                    KEY `timestamp` (`timestamp`)
                                )";
                break;
            case 'sqlite':
                //$createSQL = self::createSQLForMysql($table);
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
        }        
        
        return true;
    }
    /*
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

        $current = time();
		$logData = array(
		    'timestamp' => time(),
		    'date'      => date('Y-m-d H:i:s', $current),
		    'site'      => SITE_KEY,
		    'service'   => $service,
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
	
	    try {
            $conn = self::connection();
        } catch (KurogoDataServerException $e) {
            throw new KurogoConfigurationException("Database not configured for statistics. To disable stats, set STATS_ENABLED=0 in site.ini");
        }
        
        $table = self::getStatsTable($current);
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
    */
    
    protected static function summaryStatsData(&$statsData, $data) {
        $day = date('Y-m-d', $data['timestamp']);
        $statsKey = $day.'-'.$data['service'].'-'.$data['site'].'-'.$data['moduleID'].'-'.$data['pagetype'].'-'.$data['platform'];
        if (!isset($statsData[$statsKey])) {
            $statsData[$statsKey] = array(
                'viewCount' => 1,
                'sizeCount' => $data['size'],
                'elapsed'   => $data['elapsed'],
                'summaryTimes' => 1,
            );
        } else {
            $statsData[$statsKey]['viewCount'] = $statsData[$statsKey]['viewCount'] + 1;
            $statsData[$statsKey]['sizeCount'] = $statsData[$statsKey]['sizeCount'] + $data['size'];
            $statsData[$statsKey]['elapsed'] = $statsData[$statsKey]['elapsed'] + $data['elapsed'];
            $statsData[$statsKey]['summaryTimes'] = $statsData[$statsKey]['summaryTimes'] + 1;
        }
    }
    /**
     * export the stats data to the database
     */
    public static function exportStatsData() {
        $statsLogFile = Kurogo::getSiteVar('KUROGO_STATS_LOG_FILE');
        
        if (!file_exists($statsLogFile)) {
            Kurogo::log(LOG_DEBUG, "the stats log file not exists", 'kurogostats');
            return false;
        }
        /* need an condition for export the stats data */
        $today = date('Ymd', time());

        //copy the log file
		$tempLogFolder = Kurogo::tempDirectory();
        $statsLogFileCopy = $tempLogFolder . "stats_log_copy.$today";

        if (!is_writable($tempLogFolder)) {
            throw new Exception("Unable to write to Temporary Directory $tempLogFolder");
        }
        
        /*
        if (!rename($statsLogFile, $statsLogFileCopy)) {
            Kurogo::log(LOG_DEBUG, "failed to rename $statsLogFile to $statsLogFileCopy", 'kurogostats');
            return; 
        }
        */
        $statsData = array();
        $handle = fopen($statsLogFileCopy, 'r');
        while (!feof($handle)) {
            $line = fgets($handle, 1024);
            $value = explode("\t", $line);
            if ((count($value) != count(self::validFields())) || !isset($value[0]) || intval($value[0]) <= 0) {
                continue;
            }
            //insert the raw data to the database
            $logData = array_combine(self::validFields(), $value);
            self::insertStatsToMainTable($logData);
            
            //summary the stats data
            self::summaryStatsData($statsData, $logData);
            
        }
        fclose($handle);
        //unlink($statsLogFileCopy);
        
        if ($statsData) {
            foreach ($statsData as $statsKey => $statsValue) {
                self::updateStatsToSummaryTable($statsKey, $statsValue);
            }
        }
        
        exit;
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
        $statsArray = explode('-', $statsKey);
        $statsDate = $statsArray[0] . '-' . $statsArray[1] . '-' . $statsArray[2] . ' 00:00:00';
        $statsDateTime = mktime(0, 0, 0, $statsArray[1], $statsArray[2], $statsArray[0]);

        $insertData = array(
            'timestamp' => $statsDateTime,
            'date'      => $statsDate,
            'service'   => $statsArray[3],
            'site'      => $statsArray[4],
            'moduleID'  => $statsArray[5],
            'pagetype'  => $statsArray[6],
            'platform'  => $statsArray[7]
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
            $elapsedAvg = ($statsValue['elapsed'] / $statsValue['summaryTimes'] + $result['elapsedAvg']) / 2;
            $updateData = array(
                $result['viewCount'] + $statsValue['viewCount'],
                $result['sizeCount'] + $statsValue['sizeCount'],
                $elapsedAvg,
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

        $statsLogFile = Kurogo::getSiteVar('KUROGO_STATS_LOG_FILE');
        if (empty($statsLogFile)) {
            //Kurogo::log(LOG_DEBUG, "Stats log file not configured for statistics", 'stats');
            throw new KurogoConfigurationException("Stats log file not configured for statistics. To disable stats, set STATS_ENABLED=0 in site.ini");
        }
        
        $current = time();
        $logData = array(
		    'timestamp' => time(),
		    'date'      => date('Y-m-d H:i:s', $current),
		    'site'      => SITE_KEY,
		    'service'   => $service,
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
                ip varchar(16),
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
                elapsed int(11),
                PRIMARY KEY (`id`),
                KEY `service` (`service`),
                KEY `moduleID` (`moduleID`),
                KEY `pagetype` (`pagetype`),
                KEY `platform` (`platform`),
                KEY `visitID` (`visitID`),
                KEY `timestamp` (`timestamp`)
            )";
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
                ip varchar(16),
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
        $createIndex = array(
            "CREATE INDEX key_service ON $table (service)",
            "CREATE INDEX key_moduleID ON $table (moduleID)",
            "CREATE INDEX key_pagetype ON $table (pagetype)",
            "CREATE INDEX key_platform ON $table (platform)",
            "CREATE INDEX key_visitID ON $table (visitID)",
            "CREATE INDEX key_timestamp ON $table (timestamp)",
        );
        array_unshift($createIndex, $createSQL);
        return $createIndex;
    }
    
    private static function createStatsTables($table) {
        //$table = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
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
        return in_array($field, self::validFields());
    }
    
    public static function validFields() {
        return array(
            'timestamp',
            'date',
            'site',
            'service',
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
        //$table = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE","kurogo_stats_v1");
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
    
            if ($OptionObject->getLimit()) {
                $sql .= " LIMIT " . $OptionObject->getLimit();
            }
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
        
        if ($type=='count' && $OptionObject->getSortField()=='count') {
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
    
    public static function migratingData($table, $start = 0, $limit = 0) {
    
        $fields = self::validFields();
        
        $conn = self::connection();
        $oldSql = "SELECT * FROM $table";
        $oldSql .= $limit ? " LIMIT $start, $limit" : '';

        $isHaveData = false;
        $data = $conn->query($oldSql, array());
        while ($row = $data->fetch()) {
            $isHaveData = true;
            if ($row['timestamp'] > 0) {
                $newTable = self::getStatsTable($row['timestamp']);
                
                $insertData = array_combine($fields, $row);
                $insertSql = sprintf("INSERT INTO %s (%s) VALUES (%s)", 
                    $newTable, 
                    implode(",", array_keys($insertData)), 
                    implode(",", array_fill(0, count($insertData), '?'))
                );
                
                if (!$result = $conn->query($insertSql, array_values($insertData), db::IGNORE_ERRORS)) {
                    self::createStatsTables($newTable);
                    $result = $conn->query($insertSql, array_values($insertData));
                }
            }
        }
        
        return $isHaveData;
    }
    
    private static function fileAppend($file, $data = '') {
        if ($file) {
            $dir = dirname($file);
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new KurogoConfigurationException("could not create ".$dir);
                    return false;
                }
            }
            $handle = fopen($file, 'a+');
            fwrite($handle, $data);
            fclose($handle);
        }
    }
}
