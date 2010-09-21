<?php

class DeviceClassifier {
  private $pagetype = 'unknown';
  private $platform = 'unknown';
  private $certs = false;
  private $forcedLayout = null;

  public function getLayout() {
    return implode('-', array(
      $this->pagetype, 
      $this->platform, 
      $this->certs ? '1' : '0')
    );
  }
  private function setLayout($layout) {
    $parts = explode('-', $layout);
    $this->pagetype = $parts[0];
    $this->platform = count($parts) > 1 && strlen($parts[1]) ? $parts[1] : 'unknown';
    $this->certs    = count($parts) > 2 && strlen($parts[2]) ? $parts[2] : false;
  }
  
  function __construct($layout = null) {
    
    if ($layout && strlen($layout)) {
      $this->setLayout($layout); // user override of device detection
      $this->forcedLayout = $layout;
      //error_log(__FUNCTION__."(): layout forced to '$layout' <{$_SERVER['REQUEST_URI']}>");
      
    } else if (isset($_COOKIE['layout'])) {
      $this->setLayout($_COOKIE['layout']);
      //error_log(__FUNCTION__."(): choosing cookie layout '{$_COOKIE['layout']}' <{$_SERVER['REQUEST_URI']}>");
      
    } else {
      $query = http_build_query(array(
        'user-agent' => $_SERVER['HTTP_USER_AGENT'],
      ));
      
      $json = file_get_contents($GLOBALS['siteConfig']->getVar('MOBI_SERVICE_URL').'?'.$query);
      $data = json_decode($json, true);
      
      switch ($data['pagetype']) {
        case 'Basic':
          $this->pagetype = 'basic';
          break;
        
        case 'Mobile':
        case 'Webkit':
        case 'Touch':
          $this->pagetype = 'mobile';
          break;
          
        default:
          if ($data['platform'] == 'computer' || $data['platform'] == 'spider') {
            $this->pagetype = 'desktop';
          } else {
            $this->pagetype = 'mobile';
          }
          break;
      }
      $this->platform = $data['platform'];
      $this->certs = $data['supports_certificate'];
      
      setcookie('layout', $this->getLayout(), 
        time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);

      //error_log(__FUNCTION__."(): choosing mobi service layout '".$this->getLayout()."' <{$_SERVER['REQUEST_URI']}>");
    }
    
    //error_log('DeviceClassifier chose: '.$this->getLayout());
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

  public function layoutForced() {
    return isset($this->forcedLayout);
  }
  public function getForcedLayout() {
    return $this->forcedLayout;
  }
}
