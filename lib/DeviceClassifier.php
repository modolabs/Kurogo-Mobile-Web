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

/**
  * Contacts the Device Classification Server and sets the the appropriate properties
  * @package Core
  */
  
class DeviceClassifier {
    protected $userAgent = '';
    protected $classification = array(
        'pagetype' => 'unknown',
        'platform' => 'unknown',
        'browser'  => 'unknown',
    );
    protected $version = 1;
    protected $override = false; //whether the device detection has been overridden via setdevice
    
    protected static function cookieKey() {
        return KUROGO_IS_API ? 'apiDeviceClassification': 'deviceClassification';
    }
    
    protected function unknownClassification() {
        return array(
            'pagetype' => 'unknown',
            'platform' => 'unknown',
            'browser'  => 'unknown',
        );
    }
    
    public function getOverride() {
        return $this->override;
    }
    
    protected function classificationForString($string, &$stringIsJSON=null) {
        $classification = $this->unknownClassification();
        
        $stringIsJSON = false;
        $json = false;
        if (substr($string, 0, 1) == '{') {
            $json = json_decode($string, true);
            $stringIsJSON = $json && isset($json['pagetype'], $json['platform'], $json['browser']);
        }
        
        if ($stringIsJSON) {
            // JSON format used by new style cookies
            $classification['pagetype'] = $json['pagetype'];
            $classification['platform'] = $json['platform'];
            $classification['browser'] = $json['browser'];
            
        } else {
            // Hyphen-separated format used by debugging device override and old cookies
            $parts = explode('-', $string);
            if (count($parts) && strlen($parts[0])) {
                $classification['pagetype'] = $parts[0];
                
                if (count($parts) > 1 && strlen($parts[1])) {
                    $classification['platform'] = $parts[1];
                    
                    if (count($parts) > 2 && strlen($parts[2]) && $parts[2] != '0' && $parts[2] != '1') {
                        $classification['browser'] = $parts[2];
                    }
                }
            }
        }
        
        return $classification;
    }
    
    protected function stringForClassification($classification, $computerReadable=true) {
        if ($computerReadable) {
            return json_encode($classification);
            
        } else {
            return implode('-', array(
                $classification['pagetype'], 
                $classification['platform'],
                $classification['browser'],
            ));
        }
    }
    
    public static function getDeviceDetectionTypes() {
        return array(
            0 => Kurogo::getLocalizedString('DEVICE_DETECTION_INTERNAL'),
            1 => Kurogo::getLocalizedString('DEVICE_DETECTION_EXTERNAL')
        );
    }
  
    protected function getDeviceCookieString() {
        return $this->stringForClassification($this->classification);
    }
    
    protected function setDeviceFromCookieString($string, $deviceCacheTimeout) {
        $this->classification = $this->classificationForString($string, $stringIsJSON);
        if (!$stringIsJSON) {
            // old cookie format, overwrite
            $this->setDeviceCookie($deviceCacheTimeout);
        }
    }
    
    public function getDevice() {
        return $this->stringForClassification($this->classification, false);
    }
    
    public function setDevice($device) {
        $this->classification = $this->classificationForString($device);
    }
    
    protected function cacheFolder() {
        return CACHE_DIR . "/DeviceDetection";
    }
  
    protected function cacheLifetime() {
        return Kurogo::getSiteVar('MOBI_SERVICE_CACHE_LIFETIME');
    }
    
    function __construct($device = null, $deviceCacheTimeout = null, $override = null) {
        $this->version = intval(Kurogo::getSiteVar('MOBI_SERVICE_VERSION'));
        if (isset($override)) {
            $this->override = $override;
            $this->setOverrideCookie($override);
        } elseif (isset($_COOKIE['deviceClassificationOverride'])) {
            $this->override = $_COOKIE['deviceClassificationOverride'];
        }
        
        $this->userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        if (KurogoWebBridge::forceNativePlatform($pagetype, $platform, $browser)) {
            $this->setDevice("$pagetype-$platform-$browser");
      
        } else if ($device && strlen($device)) {
            Kurogo::log(LOG_DEBUG, "Setting device to $device (override)", "deviceDetection");
            $this->setDevice($device); // user override of device detection
            $this->setDeviceCookie($deviceCacheTimeout);
          
        } else if (isset($_COOKIE[self::cookieKey()])) {
            $cookie = $_COOKIE[self::cookieKey()];
            if (get_magic_quotes_gpc()) {
                $cookie = stripslashes($cookie);
            } 
            
            $this->setDeviceFromCookieString($cookie, $deviceCacheTimeout);
            Kurogo::log(LOG_DEBUG, "Setting device to ".$this->getDevice()." (cookie)", "deviceDetection");
          
        } else if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->classification = $this->detectDevice($this->userAgent);
            $this->setDeviceCookie($deviceCacheTimeout);
        }
        
        // Do this after caching and setting cookies or the values
        // of TABLET_ENABLED and COMPUTER_TABLET_ENABLED would be effectively cached
        if (Kurogo::getOptionalSiteVar('TABLET_ENABLED', 1)) {
            if ($this->classification['platform'] == 'computer') {
                if (Kurogo::getOptionalSiteVar('COMPUTER_TABLET_ENABLED', 1)) {
                    $this->classification['pagetype'] = 'tablet';
                } else {
                    $this->classification['pagetype'] = 'compliant';
                }
            }
        } else {
            if ($this->classification['pagetype'] == 'tablet') {
                $this->classification['pagetype'] = 'compliant';
                
                // platform ipad is currently not used but just in case:
                if ($this->classification['platform'] == 'ipad') {
                    $this->classification['platform'] = 'iphone';
                }
            }
        }
        // Touch pagetype is no longer supported.  Remap to basic:
        if ($this->classification['pagetype'] == 'touch') {
            $this->classification['pagetype'] = 'basic';
        }
    }
    
    // This function generates the response for the classification core api
    // In this class so that all device detection logic is in one place
    public function classifyUserAgent($userAgent) {
        $classification = $this->detectDevice($userAgent);
        
        $isMobile = false;
        switch ($classification['pagetype']) {
            case 'basic':
            case 'touch':
                $isMobile = true;
                break;
            
            case 'compliant':
                switch ($classification['platform']) {
                    case 'featurephone':
                    case 'palmos':
                    case 'symbian':
                    case 'android':
                    case 'bbplus':
                    case 'blackberry':
                    case 'blackberry6':
                    case 'iphone':
                    case 'winphone7':
                    case 'winphone8':
                    case 'winmo':
                    case 'webos':
                        $isMobile = true;
                        break;
                }
                break;
            
            case 'tablet':
                if (Kurogo::getOptionalSiteVar('TABLET_ENABLED', 1)) {
                    $isMobile = true;
                }
                break;
        }
        if ($classification['platform'] == "computer") {
            $isMobile = false; // except computers
        }
        
        return array(
            'mobile'   => $isMobile,
            'pagetype' => $classification['pagetype'],
            'platform' => $classification['platform'],
        );
    }
    
    protected function cacheKey($userAgent) {
      return 'deviceDectection-' . md5($userAgent);
    }
    
    public function getUserAgent() {
      return $this->userAgent;
    }
    
    public static function clearDeviceCookie() {
        setCookie(self::cookieKey(), false, 1225344860, COOKIE_PATH);
        setCookie('deviceClassificationOverride', false, 1225344860, COOKIE_PATH);
    }
    
    protected function setOverrideCookie($value) {
        setCookie('deviceClassificationOverride', $value, 0, COOKIE_PATH);
    }
      
    protected function setDeviceCookie($life=null) {
        if (!isset($life)) {
            return;
        }
        //if life = 0 then use that as the expiration (session based) otherwise use life as an offset from the current time
        $time = $life !== 0 ? time() + $life : $life;
      setcookie(self::cookieKey(), $this->getDeviceCookieString(), 
        $time, COOKIE_PATH);
    }
    
    protected function detectDevice($userAgent) {
        $classification = $this->unknownClassification();
        
        if ($cachedClassificationString = Kurogo::getCache($this->cacheKey($userAgent))) {
            // Kurogo cache has device string
            $classification = $this->classificationForString($cachedClassificationString);
            
        } else if ($data = Kurogo::getSiteVar('MOBI_SERVICE_USE_EXTERNAL') ? 
                       $this->detectDeviceExternal($userAgent) : $this->detectDeviceInternal($userAgent)) {
            // Looked up device data with configured device detection method
            $classification['pagetype'] = $data['pagetype'];
            $classification['platform'] = $data['platform'];
            if (isset($data['browser'])) {
                $classification['browser'] = $data['browser'];
            }
            Kurogo::setCache($this->cacheKey($userAgent), $this->stringForClassification($classification));
            
        }
        
        return $classification;
    }
  
    protected function detectDeviceInternal($user_agent) {
        Kurogo::log(LOG_INFO, "Detecting device using internal device detection", 'deviceDetection');
        if (!$user_agent) {
            return;
        }

        /*
         * Two things here:
         * First off, we now have two files which can be used to classify devices,
         * the master file, usually at LIB_DIR/deviceData.json, and the custom file,
         * usually located at DATA_DIR/deviceData.json.
         *
         * Second, we're still allowing the use of sqlite databases (despite it
         * being slower and more difficult to update).  So, if you specify the
         * format of the custom file as sqlite, it should still work.
         */

        $master_file = LIB_DIR."/deviceData.json";

        $site_file = Kurogo::getOptionalSiteVar('MOBI_SERVICE_SITE_FILE');
        $site_file_format = Kurogo::getOptionalSiteVar('MOBI_SERVICE_SITE_FORMAT', 'json');

        if (!($site_file && $site_file_format)) {
            // We don't have a site-specific file.  This means we can only
            // detect on the master file.

            $site_file = "";
        }

        if (!empty($site_file) && $site_file = realpath_exists($site_file)) {
            switch ($site_file_format) {
                case 'json':
                    $site_devices = json_decode(file_get_contents($site_file), true);
                    $site_devices = $site_devices['devices'];
                    if (($error_code = json_last_error()) !== JSON_ERROR_NONE) {
                        throw new KurogoConfigurationException("Problem decoding Custom Device Detection File. Error code returned was ".$error_code);
                    }


                    if ((($device = $this->checkDevices($site_devices, $user_agent)) !== false)) {
                        return $this->translateDevice($device);
                    }
                    break;
                    
                case 'sqlite':
                    Kurogo::includePackage('db');
                    try {
                        $db = new db(array('DB_TYPE'=>'sqlite', 'DB_FILE'=>$site_file));
                        $result = $db->query('SELECT * FROM userAgentPatterns WHERE version<=? ORDER BY patternorder,version DESC', array($this->version));
                    } catch (Exception $e) {
                        Kurogo::log(LOG_ALERT, "Error with internal device detection: " . $e->getMessage(), 'deviceDetection');
                        if (!in_array('sqlite', PDO::getAvailableDrivers())) {
                            die("SQLite PDO drivers not available. You should switch to external device detection by changing MOBI_SERVICE_USE_EXTERNAL to 1 in " . SITE_CONFIG_DIR . "/site.ini");
                        }
                        return false;
                    }

                    while ($row = $result->fetch()) {
                        if (preg_match("#" . $row['pattern'] . "#i", $user_agent)) {
                            return $row;
                        }
                    }
                    break;
                    
                default:
                    throw new KurogoConfigurationException('Unknown format specified for Custom Device Detection File: '.$site_file_format);
            }

        }
        
        if (!empty($master_file) && $master_file = realpath_exists($master_file)) {
            $master_devices = json_decode(file_get_contents($master_file), true);
            $master_devices = $master_devices['devices'];
            
            if (function_exists('json_last_error') && ($error_code = json_last_error()) !== JSON_ERROR_NONE) {
                Kurogo::log(LOG_ALERT, "Error with JSON internal device detection: " . $error_code, 'deviceDetection');
                die("Problem decoding Device Detection Master File. Error code returned was ".$error_code);
            }

            if (($device = $this->checkDevices($master_devices, $user_agent)) !== false) {
                return $this->translateDevice($device);
            }
        }
        
        Kurogo::log(LOG_WARNING, "Could not find a match in the internal device detection database for: $user_agent", 'deviceDetection');
    }

    protected function checkDevices($devices, $user_agent) {
        foreach ($devices as $device) {
            foreach ($device['match'] as $match) {
                if (isset($match['regex'])) {
                    $mods = "";
                    if (isset($match['options'])) {
                        if (isset($match['options']['DOT_ALL']) && $match['options']['DOT_ALL'] === true) {
                            $mods .= "s";
                        }
                        if(isset($match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true) {
                            $mods .= "i";
                        }

                    }
                    if (preg_match('/'.str_replace('/', '\\/'.$mods, $match['regex']).'/', $user_agent)) {
                        return $device;
                    }
                } else if (isset($match['partial'])) {
                    if (isset($match['options'], $match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true) {
                        if (stripos($user_agent, $match['partial']) !== false) {
                            return $device;
                        }
                    }

                    // Case insensitive either isn't set, or is set to false.
                    if (strpos($user_agent, $match['partial']) !== false) {
                        return $device;
                    }
                } else if (isset($match['prefix'])) {
                    if(isset($match['options'], $match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true) {
                        if (stripos($user_agent, $match['partial']) === 0) {
                            return $device;
                        }
                    }

                    // Case insensitive either isn't set, or is set to false.
                    if (strpos($user_agent, $match['prefix']) === 0) {
                        return $device;
                    }
                    
                } else if (isset($match['suffix'])) {
                    if (isset($match['options'], $match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true) {
                        $case_insens = true;
                    } else {
                        $case_insens = false;
                    }
                    // Because substr_compare is supposedly designed for this purpose...
                    if (substr_compare($user_agent, $match['partial'], -(strlen($match['partial'])), strlen($match['partial']), $case_insens) === 0) {
                        return $device;
                    }
                }
            }

        }
        return false;
    }
    
    protected function translateDevice($device) {
        $classificationFilter = array_flip(array('pagetype', 'platform', 'browser'));
        for ($i = $this->version; $i > 0; $i--) {
            if (isset($device['classification'][strval($i)],
                      $device['classification'][strval($i)]['pagetype'],
                      $device['classification'][strval($i)]['platform'])) {
                return array_intersect_key($device['classification'][strval($i)], $classificationFilter);
            }
        }
        throw new KurogoConfigurationException("Invalid internal device classification for '{$device['description']}'");
    }
  
    protected function detectDeviceExternal($user_agent) {
      if (!$user_agent) {
          return;
      }
              
      // see if the server has cached the results from the the device detection server
      try {
        $cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime(), TRUE);
      } catch (KurogoDataException $e) {
        $cache = null;
      }
          $cacheFilename = md5($user_agent);
  
      if ($cache && $cache->isFresh($cacheFilename)) {
          $json = $cache->read($cacheFilename);
          Kurogo::log(LOG_INFO, "Using cached data for external device detection" , 'deviceDetection');
  
      } else {
          $query = http_build_query(array(
            'user-agent' => $user_agent,
            'version'    => $this->version
          ));
          
          $url = Kurogo::getSiteVar('MOBI_SERVICE_URL').'?'.$query;
          Kurogo::log(LOG_INFO, "Detecting device using external device detection: $url", 'deviceDetection');
          $timeout = Kurogo::getOptionalSiteVar('MOBI_SERVICE_EXTERNAL_TIMEOUT',5);
          $context = stream_context_create(array(
          	'http'=>array(
          	  'timeout'=>$timeout,
          	  'user_agent'=>Kurogo::KurogoUserAgent(),
          	)
          ));
          $json = @file_get_contents($url, false, $context);
          if(false === $json) {
              return $this->detectDeviceInternal($user_agent);
          }

          $test = json_decode($json, true); // make sure the response is valid
          
          if ($cache) {
              if ($json && isset($test['pagetype'], $test['platform'])) {
                  $cache->write($json, $cacheFilename);
            
              } else {
                  Kurogo::log(LOG_WARNING, "Error receiving device detection data from $url.  Reading expired cache.", 'deviceDetection');
                  $json = $cache->read($cacheFilename);
              }
            }
      }            
  
      $data = json_decode($json, true);
  
      // fix values when using old version
      if ($this->version == 1) {
          switch (strtolower($data['pagetype'])) {
              case 'basic':
                  if ($data['platform'] == 'computer' || $data['platform'] == 'spider') {
                      $data['pagetype'] = 'compliant';
                    
                  } else if ($data['platform'] == 'bbplus') {
                      $data['pagetype'] = 'compliant';
                    
                  } else {
                      $data['pagetype'] = 'basic';
                  }
                  break;
              
              case 'touch':
                  if ($data['platform'] == 'blackberry') {
                      $data['pagetype'] = 'compliant'; // Storm, Storm 2
                    
                  } else if ($data['platform'] == 'winphone7') {
                      $data['pagetype'] = 'compliant'; // Windows Phone 7
                    
                  } else {
                      $data['pagetype'] = 'touch';
                  }
                  break;
              
              case 'compliant':
              case 'webkit':
              default:
                  $data['pagetype'] = 'compliant';
                  break;
          }
      }
      
      return $data;          
    }
  
    public function isComputer() {
        return $this->classification['platform'] == 'computer';
    }
  
    public function isTablet() {
        return $this->classification['pagetype'] == 'tablet';
    }
  
    public function isSpider() {
        return $this->classification['platform'] == 'spider';
    }
   
    public function getPagetype() {
        return $this->classification['pagetype'];
    }
    
    public function getPlatform() {
        return $this->classification['platform'];
    }
    
    public function getBrowser() {
        return $this->classification['browser'];
    }

    public function setPagetype($pagetype) {
        $this->classification['pagetype'] = $pagetype;
    }
    
    public function setPlatform($platform) {
        $this->classification['platform'] = $platform;
    }
    
    public function setBrowser($browser) {
        $this->classification['browser'] = $browser;
    }
    
    public function mailToLinkNeedsAtInToField() {
        // Some old BlackBerries will give you an error about unsupported protocol
        // if you have a mailto: link that doesn't have a "@" in the recipient 
        // field. So we can't leave this field blank for these models. It's not
        // a matter of being <= 9000 either, since there are Curves that are fine.
        $modelsNeedingToField = array("8100", "8220", "8230", "9000");
        
        foreach ($modelsNeedingToField as $model) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], "BlackBerry".$model) !== FALSE) {
                return true;
            }
        }
        return false;
    }
    
    public static function buildFileSearchList($pagetype, $platform, $browser, $page, $ext, $prefix='') {
        $base = '';
        if ($ext == 'js' || $ext == 'css') {
            $base = 'common';
        }
        
        $searchOrder = array(
            array($page,  $pagetype,  $platform,  $browser),
            array($page,  'common',   $platform,  $browser),
            array($page,  $pagetype,  'common',   $browser),
            array($page,  'common',   'common',   $browser),
            array($page,  $pagetype,  $platform),
            array($page,  'common',   $platform),
            array($page,  $pagetype),
            array($page,  $base),
        );
        
        $searchPath = array();
        foreach ($searchOrder as $nameComponents) {
            $searchPath[] = $prefix.implode('-', array_filter($nameComponents)).'.'.$ext;
        }
        
        return $searchPath;
    }
}
