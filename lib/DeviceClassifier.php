<?php
/**
  * @package Core
  */

/**
  * Name of the cookie used for device classification
  */
define('COOKIE_KEY', 'deviceClassification');

/**
  * Contacts the Device Classification Server and sets the the appropriate properties
  * @package Core
  */
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
      
    } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
      $query = http_build_query(array(
        'user-agent' => $_SERVER['HTTP_USER_AGENT'],
      ));
      
      $json = file_get_contents($GLOBALS['siteConfig']->getVar('MOBI_SERVICE_URL').'?'.$query);
      $data = json_decode($json, true);
      
      switch (strtolower($data['pagetype'])) {
        case 'basic':
          if ($data['platform'] == 'computer' || $data['platform'] == 'spider') {
            $this->pagetype = 'compliant';
            
          } else if ($data['platform'] == 'bbplus') {
            $this->pagetype = 'compliant';
            
          } else {
            $this->pagetype = 'basic';
          }
          break;
        
        case 'touch':
          if ($data['platform'] == 'blackberry') {
            $this->pagetype = 'compliant'; // Storm, Storm 2
            
          } else if ($data['platform'] == 'winphone7') {
            $this->pagetype = 'compliant'; // Windows Phone 7
            
          } else {
            $this->pagetype = 'touch';
          }
          break;
          
        case 'compliant':
        case 'webkit':
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
