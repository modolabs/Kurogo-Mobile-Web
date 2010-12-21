<?php

define('COOKIE_KEY', 'deviceClassification');

class DeviceClassifier {
  private $pagetype = 'unknown';
  private $platform = 'unknown';
  private $certs = false;

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
  
  function __construct($device = null) {
    
    if ($device && strlen($device)) {
      $this->setDevice($device); // user override of device detection
      //error_log(__FUNCTION__."(): device forced to '$device' <{$_SERVER['REQUEST_URI']}>");
      
    } else if (isset($_COOKIE[COOKIE_KEY])) {
      $this->setDevice($_COOKIE[COOKIE_KEY]);
      //error_log(__FUNCTION__."(): choosing device cookie '{$_COOKIE['layout']}' <{$_SERVER['REQUEST_URI']}>");
      
    } else {
      $query = http_build_query(array(
        'user-agent' => $_SERVER['HTTP_USER_AGENT'],
      ));
      
      $json = file_get_contents($GLOBALS['siteConfig']->getVar('MOBI_SERVICE_URL').'?'.$query);
      $data = json_decode($json, true);
      
      switch ($data['pagetype']) {
        case 'Basic':
          if ($data['platform'] == 'computer' || $data['platform'] == 'spider') {
            $this->pagetype = 'compliant';
          } else {
            $this->pagetype = 'basic';
          }
          break;
        
        case 'Compliant':
        case 'Webkit':
        case 'Touch':
        default:
          $this->pagetype = 'compliant';
          break;
      }
      $this->platform = $data['platform'];
      $this->certs = $data['supports_certificate'];
      
      setcookie(COOKIE_KEY, $this->getDevice(), 
        time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);

      //error_log(__FUNCTION__."(): choosing mobi service layout '".$this->getDevice()."' <{$_SERVER['REQUEST_URI']}>");
    }
    
    //error_log('DeviceClassifier chose: '.$this->getDevice());
    //error_log('User-agent is: '.$_SERVER['HTTP_USER_AGENT']);
  }
  
  public function isComputer() {
    return $this->platform == 'computer';
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
}
