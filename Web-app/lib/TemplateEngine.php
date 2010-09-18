<?php

require_once realpath(LIB_DIR.'/smarty/Smarty.class.php');

class TemplateEngine extends Smarty {
  static $accessKey = 0;
  private $device = '';
    
  static function getTemplateForDevice($template, $device) {
    $path = dirname($template);
    $name = basename($template, '.tpl');

    if (strlen($path)) { $path .= '/'; }

    $test = $path.$name.'-'.$device.'.tpl';
    if (realpath(TEMPLATES_DIR.'/'.$test)) {
      $template = $test;  // Use platform-specific
    
    } else { 
      $classification = explode('-', $device);
      $test = $path.$name.'-'.$classification[0].'.tpl';
      
      if (realpath(TEMPLATES_DIR.'/'.$test)) {
        $template = $test;  // Use pagetype-specific
        
      } else {
        $template = $path.$name.'.tpl'; // Use generic
      }
    }
    
    error_log(__FUNCTION__.'('.$device.') choosing '.$template);
    return $template;
  }
  
  static function accessKey($params, &$smarty) {
    $needsKey = !isset($params['platform']) || $params['platform'] != 'bbplus';
    
    if (isset($params['reset'])) {
      self::$accessKey = $params['reset'];
    }
    
    if ($needsKey && self::$accessKey <= 10) {
      return ' accesskey="'.self::$accessKey++.'"';
    } else {
      return '';
    }
  }
  
  public function loadThemeConfigFile($name, $loadVarKeys=false) {
    $GLOBALS['siteConfig']->loadThemeFile($name);
    
    if ($loadVarKeys) {
      foreach($GLOBALS['siteConfig']->getThemeVar($name) as $key => $value) {
        $this->assign($key, $value);
      }
    } else {
      $this->assign($name, $GLOBALS['siteConfig']->getThemeVar($name));
    }
  }
   
  function __construct() {
    parent::__construct();
    
    $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    $platform = $GLOBALS['deviceClassifier']->getPlatform();
    
    $this->device = $pagetype.'-'.$platform;
    
    $this->setTemplateDir(TEMPLATES_DIR);
    $this->setCompileDir ($GLOBALS['siteConfig']->getVar('TEMPLATE_COMPILE_DIR'));
    $this->setCacheDir   ($GLOBALS['siteConfig']->getVar('TEMPLATE_CACHE_DIR'));
    $this->setCompileId  ($this->device);
    
    $this->register->modifier('for_device', 'TemplateEngine::getTemplateForDevice');
    $this->register->templateFunction('access_key', 'TemplateEngine::accessKey');
      
    // variables common to all modules
    $this->assign('device', $this->device);
    $this->assign('pagetype', $pagetype);
    $this->assign('platform', $platform);
    $this->assign('showDeviceDetection', $GLOBALS['siteConfig']->getVar('SHOW_DEVICE_DETECTION'));
            
    // Load site configuration
    $this->loadThemeConfigFile('site', true);
    
    date_default_timezone_set($GLOBALS['siteConfig']->getThemeVar('site', 'SITE_TIMEZONE'));

  }
  
  function displayForDevice($page, $cacheID = null, $compileID = null, $parent = null) {
    $template = self::getTemplateForDevice($page, $this->device);
    
    $this->display($template, $cacheID, $compileID, $parent);
  }
}
