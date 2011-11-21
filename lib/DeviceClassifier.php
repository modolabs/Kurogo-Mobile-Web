<?php
/**
  * @package Core
  */

/**
  * Contacts the Device Classification Server and sets the the appropriate properties
  * @package Core
  */
class DeviceClassifier {
  private $userAgent = '';
  private $pagetype = 'unknown';
  private $platform = 'unknown';
  private $certs = false;
  protected $version = 1;
  
  private function cookieKey() {
    return KUROGO_IS_API ? 'apiDeviceClassification': 'deviceClassification';
  }
  
  public static function getDeviceDetectionTypes() {
    return array(
        0=>Kurogo::getLocalizedString('DEVICE_DETECTION_INTERNAL'),
        1=>Kurogo::getLocalizedString('DEVICE_DETECTION_EXTERNAL')
    );
  }

  public function getDevice() {
    return implode('-', array(
      $this->pagetype, 
      $this->platform, 
      $this->certs ? '1' : '0')
    );
  }
  private function setDevice($device) {
    $parts = explode('-', $device);
    $this->pagetype = $parts[0];
    $this->platform = count($parts) > 1 && strlen($parts[1]) ? $parts[1] : 'unknown';
    $this->certs    = count($parts) > 2 && strlen($parts[2]) ? $parts[2] : false;
  }
  
  private function cacheFolder() {
    return CACHE_DIR . "/DeviceDetection";
  }

  private function cacheLifetime() {
    return Kurogo::getSiteVar('MOBI_SERVICE_CACHE_LIFETIME');
  }
  
  function __construct($device = null) {
  
    $this->version = intval(Kurogo::getSiteVar('MOBI_SERVICE_VERSION'));
    $this->userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    if ($device && strlen($device)) {
      Kurogo::log(LOG_DEBUG, "Setting device to $device (override)", "deviceDetection");
      $this->setDevice($device); // user override of device detection
      
    } elseif (isset($_COOKIE[$this->cookieKey()])) {
      Kurogo::log(LOG_DEBUG, "Setting device to " . $_COOKIE[$this->cookieKey()] . " (cookie)", "deviceDetection");
      $this->setDevice($_COOKIE[$this->cookieKey()]);
      
    } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
      if ($cache = Kurogo::getCache($this->cacheKey($this->userAgent))) {
        $this->setDevice($cache);
        return $cache;
      }
      if ($data = Kurogo::getSiteVar('MOBI_SERVICE_USE_EXTERNAL') ? 
        $this->detectDeviceExternal($this->userAgent) : $this->detectDeviceInternal($this->userAgent) ) {
        

        if ($data['pagetype'] == 'tablet' && !Kurogo::getOptionalSiteVar('TABLET_ENABLED', 1)) {
            $data['pagetype'] = 'compliant';
            
            if ($data['platform'] == 'ipad') {
                $data['platform'] = 'iphone'; // currently not used but just in case
            }
        }        
        
        $this->pagetype = $data['pagetype'];
        $this->platform = $data['platform'];
        $this->certs = $data['supports_certificate'];
        Kurogo::log(LOG_DEBUG, "Setting device to " . $this->getDevice(), "deviceDetection");
        $this->setDeviceCookie();
        Kurogo::setCache($this->cacheKey($this->userAgent), $this->getDevice());
      }
    }
  }
  
  private function cacheKey($userAgent) {
    return 'deviceDectection-' . md5($userAgent);
  }
  
  public function getUserAgent() {
    return $this->userAgent;
  }
    
  private function setDeviceCookie() {
    setcookie($this->cookieKey(), $this->getDevice(), 
      time() + Kurogo::getSiteVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);
  }
  
    private function detectDeviceInternal($user_agent) {
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

        if (!( $site_file && $site_file_format)) {
            // We don't have a site-specific file.  This means we can only
            // detect on the master file.

            $site_file = "";
        }

        if (!empty($site_file) && $site_file = realpath_exists($site_file))
        {
            switch ($site_file_format)
            {
                case 'json':
                    $site_devices = json_decode(file_get_contents($site_file), true);
                    $site_devices = $site_devices['devices'];
                    if(($error_code = json_last_error()) !== JSON_ERROR_NONE) 
                    {
                        throw new KurogoConfigurationException("Problem decoding Custom Device Detection File. Error code returned was ".$error_code);
                    }


                    if((($device = $this->checkDevices($site_devices, $user_agent)) !== false)) {
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
        
        if(!empty($master_file) && $master_file = realpath_exists($master_file))
        {
            $master_devices = json_decode(file_get_contents($master_file), true);
            $master_devices = $master_devices['devices'];
            if(function_exists('json_last_error') && ($error_code = json_last_error()) !== JSON_ERROR_NONE)
            {
                Kurogo::log(LOG_ALERT, "Error with JSON internal device detection: " . $error_code, 'deviceDetection');
                die("Problem decoding Device Detection Master File. Error code returned was ".$error_code);
            }

            if(($device = $this->checkDevices($master_devices, $user_agent)) !== false) {
                return $this->translateDevice($device);
            }
        }
        
        Kurogo::log(LOG_WARNING, "Could not find a match in the internal device detection database for: $user_agent", 'deviceDetection');
    }

    private function checkDevices($devices, $user_agent)
    {
        foreach($devices as $device)
        {
            foreach($device['match'] as $match)
            {
                if(isset($match['regex']))
                {
                    $mods = "";
                    if(isset($match['options']))
                    {
                        if(isset($match['options']['DOT_ALL']) && $match['options']['DOT_ALL'] === true)
                        {
                            $mods .= "s";
                        }
                        if(isset($match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true)
                        {
                            $mods .= "i";
                        }

                    }
                    if(preg_match('/'.str_replace('/', '\\/'.$mods, $match['regex']).'/', $user_agent))
                    {
                        return $device;
                    }
                }
                elseif(isset($match['partial']))
                {
                    if(isset($match['options']) && isset($match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true)
                    {
                        if(stripos($user_agent, $match['partial']) !== false)
                        {
                            return $device;
                        }
                    }

                    // Case insensitive either isn't set, or is set to false.
                    if(strpos($user_agent, $match['partial']) !== false)
                    {
                        return $device;
                    }
                }
                elseif(isset($match['prefix']))
                {
                    if(isset($match['options']) && isset($match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true)
                    {
                        if(stripos($user_agent, $match['partial']) === 0)
                        {
                            return $device;
                        }
                    }

                    // Case insensitive either isn't set, or is set to false.
                    if(strpos($user_agent, $match['prefix']) === 0)
                    {
                        return $device;
                    }
                }
                elseif (isset($match['suffix']))
                {
                    if(isset($match['options']) && isset($match['options']['CASE_INSENSITIVE']) && $match['options']['CASE_INSENSITIVE'] === true)
                        $case_insens = true;
                    else
                        $case_insens = false;
                    // Because substr_compare is supposedly designed for this purpose...
                    if(substr_compare($user_agent, $match['partial'], -(strlen($match['partial'])), strlen($match['partial']), $case_insens) === 0)
                    {
                        return $device;
                    }
                }
            }

        }
        return false;
    }
    private function translateDevice($device)
    {
        $newDevice = array();
        $newDevice['pagetype'] = $device['classification'][strval($this->version)]['pagetype'];
        $newDevice['platform'] = $device['classification'][strval($this->version)]['platform'];
        $newDevice['supports_certificate'] = $device['classification'][strval($this->version)]['supports_certificate'];
        return $newDevice;
    }
  
  private function detectDeviceExternal($user_agent) {
    if (!$user_agent) {
      return;
    }
            
    // see if the server has cached the results from the the device detection server
    $cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime(), TRUE);
    $cacheFilename = md5($user_agent);

    if ($cache->isFresh($cacheFilename)) {
      $json = $cache->read($cacheFilename);
      Kurogo::log(LOG_INFO, "Using cached data for external device detection" , 'deviceDetection');

    } else {
      $query = http_build_query(array(
        'user-agent' => $user_agent,
        'version'=> $this->version
      ));
      
      $url = Kurogo::getSiteVar('MOBI_SERVICE_URL').'?'.$query;
      Kurogo::log(LOG_INFO, "Detecting device using external device detection: $url", 'deviceDetection');
      $json = file_get_contents($url);

      $test = json_decode($json, true); // make sure the response is valid
      
      if ($json && isset($test['pagetype'], $test['platform'], $test['supports_certificate'])) {
        $cache->write($json, $cacheFilename);
        
      } else {
        Kurogo::log(LOG_WARNING, "Error receiving device detection data from $url.  Reading expired cache.", 'deviceDetection');
        $json = $cache->read($cacheFilename);
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
    return $this->platform == 'computer';
  }

  public function isTablet() {
    return $this->pagetype == 'tablet';
  }

  public function isSpider() {
    return $this->platform == 'spider';
  }
 
  public function getPagetype() {
    return $this->pagetype;
  }
  
  public function getPlatform() {
    return $this->platform;
  }
  
  public function getSupportsCerts() {
    return $this->certs;
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
}
