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
  
  /**
   * TODO: cache the device database. Perhaps need a perfect logic to check the cache expire
   * when the database is updated.
  */
  
  private function detectDeviceInternal($user_agent) {
    Kurogo::log(LOG_INFO, "Detecting device using internal device detection", 'deviceDetection');
    Kurogo::includePackage('db');
    if (!$user_agent) {
      return;
    }
     
     if (!$db_file =  Kurogo::getSiteVar('MOBI_SERVICE_FILE')) {
        Kurogo::log(LOG_EMERG, "MOBI_SERVICE_FILE not specified in site config.", 'deviceDetection');
        die("MOBI_SERVICE_FILE not specified in site config.");
     }
     
     try {
         $db = new db(array('DB_TYPE'=>'sqlite', 'DB_FILE'=>$db_file));
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
     
     Kurogo::log(LOG_NOTICE, "Could not find a match in the internal device detection database for: $user_agent", 'deviceDetection');
     return false;
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
