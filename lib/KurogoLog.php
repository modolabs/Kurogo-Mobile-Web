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

class KurogoLog { 

    const areaDefault = '';
	protected $areaLevel = array();
	protected $defaultLevel = LOG_WARNING;
	protected $logFile;

    public function setLogFile($logFile) {
        $this->logFile = $logFile;
    }
    
    public function setDefaultLogLevel($level) {
        if (!self::isValidPriority($level)) {
            throw new Exception("Invalid default logging priority $level");
        }

        $this->defaultLevel = $level;
        return true;
    }

    public function setLogLevel($area, $level) {
        if (!self::isValidPriority($level)) {
            throw new Exception("Invalid logging priority $priority for area $area");
        }
        $this->areaLevel[$area] = $level;
        return true;
	}

    public static function getPriorities() {
	    return array(
	        LOG_EMERG   =>'Emergency',  // system is unusable
	        LOG_ALERT   =>'Alert',      // action must be taken immediately
	        LOG_CRIT    =>'Critical',   // critical conditions
	        LOG_ERR     =>'Error',      // error conditions
	        LOG_WARNING =>'Warning',    // warning conditions
	        LOG_NOTICE  =>'Notice',     // normal, but significant, condition
	        LOG_INFO    =>'Info',       // informational message
	        LOG_DEBUG   =>'Debug'       // debug-level message
	    );
    }
    
	private static function priorityToString($priority) {
	    $priorities = self::getPriorities();
	    return isset($priorities[$priority]) ? $priorities[$priority] : null;
	}

	private static function isValidPriority($priority) {
	    if (!is_numeric($priority)) {
	        return false;
	    }
	    $result = in_array($priority, array(
	        LOG_EMERG,   // system is unusable
	        LOG_ALERT,   // action must be taken immediately
	        LOG_CRIT,    // critical conditions
	        LOG_ERR,     // error conditions
	        LOG_WARNING, // warning conditions
	        LOG_NOTICE,  // normal, but significant, condition
	        LOG_INFO,    // informational message
	        LOG_DEBUG   // debug-level message
	    ));
        return $result;
	}
	
	public function log($priority, $message, $area, $backTrace=null) {
        
        if (!self::isValidPriority($priority)) {
            throw new Exception("Invalid logging priority $priority");
        }

        if (!preg_match("/^[a-z0-9_-]+$/i", $area)) {
            throw new Exception("Invalid area $area");
        }

        //don't log items above the current logging level
        $loggingLevel = isset($this->areaLevel[$area]) ? $this->areaLevel[$area] : $this->defaultLevel;
        if ($priority > $loggingLevel) {
            return;
        }
        
        if (!$backTrace) {
            $backTrace = debug_backtrace();
        }
        
        $compactTrace = self::compactTrace($backTrace);
        if (isset($_SERVER['REQUEST_URI'])) {
            $request = $_SERVER['REQUEST_URI'];
        } elseif (defined('KUROGO_SHELL')) {
            $request = json_encode(Kurogo::getArrayForRequest());
        } else {
            $request = null;
        }
        
		$content = sprintf(
			"%s\t%s:%s\t%s\t%s\t%s",
			date(Kurogo::getSiteVar('LOG_DATE_FORMAT')),
			$area,
			self::priorityToString($priority),
			$compactTrace,
			$request,
			$message
		) . PHP_EOL;

        self::fileAppend($this->logFile, $content);
	}
	
    // provides a simplified view of the backtrace
	private static function compactTrace($trace) {
	    $calledAt = $trace[0];
	    $calledFrom = $trace[1];
	    $compactTrace = '';
	    if (isset($calledFrom['class'])) {
	        $compactTrace .= $calledFrom['class'] . $calledFrom['type'] . $calledFrom['function'];
	        if ($calledFrom['class']=='Kurogo' && $calledFrom['function']=='log') {
	            array_shift($trace);
	            return self::compactTrace($trace);
	        }
	    } elseif (isset($calledFrom['function'])) {
	        $compactTrace .= $calledFrom['function'] ;
	    }
	    
	    if (isset($calledAt['line'])) {
	        $compactTrace .= "@" . $calledAt['line'];
	    }
	    return $compactTrace;
	}

    private static function fileAppend($file, $data = '') {
        if ($file) {
            $dir = dirname($file);
            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    return false;
                }
            }
            if ($handle = @fopen($file, 'a+')) {
                fwrite($handle, $data);
                fclose($handle);
            } else {
                error_log("Unable to write to kurogo log file $file");
            }
        }
    }
    
}
