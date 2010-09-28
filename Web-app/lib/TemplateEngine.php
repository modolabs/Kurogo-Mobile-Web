<?php

require_once realpath(LIB_DIR.'/smarty/Smarty.class.php');

class TemplateEngine extends Smarty {
  static $accessKey = 0;
  
  //
  // Include file resource plugin
  //
  
  static private function getIncludeFile($name) {
    $subDir = dirname($name);
    $page = basename($name, '.tpl');
    
    $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    $platform = $GLOBALS['deviceClassifier']->getPlatform();

    if (strlen($subDir)) { $subDir .= '/'; }
  
    $checkDirs = array(
      'THEME_DIR'     => $GLOBALS['siteConfig']->getVar('THEME_DIR'),
      'TEMPLATES_DIR' => TEMPLATES_DIR,
    );
    $checkFiles = array(
      "$subDir$page-$pagetype-$platform.tpl", // platform-specific
      "$subDir$page-$pagetype.tpl",           // pagetype-specific
      "$subDir$page.tpl",                     // default
    );
    
    foreach ($checkDirs as $type => $dir) {
      foreach ($checkFiles as $file) {
        if (realpath_exists("$dir/$file")) {
          error_log(__FUNCTION__."($pagetype-$platform) choosing '$type/$file'");
          return "$dir/$file";
        }
      }
    }
    return false;
  }
  
  static function smartyResourceIncludeGetSource($name, &$source, $smarty) {
    $file = self::getIncludeFile($name);
    if ($file !== false) {
      $source = file_get_contents($file);
      return true;
    }
    return false;
  }

  static function smartyResourceIncludeGetTimestamp($name, &$timestamp, $smarty) {
    $file = self::getIncludeFile($name);
    if ($file !== false) {
      $timestamp = filemtime($file);
      return true;
    }
    return false;
  }

  static function smartyResourceIncludeGetSecure($name, $smarty) {
    return true;
  }

  static function smartyResourceIncludeGetTrusted($name, $smarty) {
    return true;
  }
  
  //
  // Extends file resource plugin
  //
  
  static private function getExtendsFile($name) {
    $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    $platform = $GLOBALS['deviceClassifier']->getPlatform();

    $checkDirs = array(
      'TEMPLATES_DIR' => TEMPLATES_DIR,
      'THEME_DIR'     => $GLOBALS['siteConfig']->getVar('THEME_DIR'),
    );
    
    foreach ($checkDirs as $type => $dir) {
        if (realpath_exists("$dir/$name")) {
          error_log(__FUNCTION__."($pagetype-$platform) choosing '$type/$name'");
          return "$dir/$name";
        }
    }
    return false;
  }
  
  static function smartyResourceExtendsGetSource($name, &$source, $smarty) {
    $file = self::getExtendsFile($name);
    if ($file !== false) {
      $source = file_get_contents($file);
      return true;
    }
    return false;
  }

  static function smartyResourceExtendsGetTimestamp($name, &$timestamp, $smarty) {
    $file = self::getExtendsFile($name);
    if ($file !== false) {
      $timestamp = filemtime($file);
      return true;
    }
    return false;
  }

  static function smartyResourceExtendsGetSecure($name, $smarty) {
    return true;
  }

  static function smartyResourceExtendsGetTrusted($name, $smarty) {
    return true;
  }
  
  static function smartyOutputfilterAddURLPrefix($output, $smarty) {
    $output = preg_replace(
      ';(url\("?\'?|href\s*=\s*"|src\s*=\s*")('.URL_PREFIX.'|/);', 
      '\1'.URL_PREFIX, $output);  
    return $output;
  }
  
  //
  // Access key block and template plugins
  //
  
  static function smartyBlockAccessKeyLink($params, $content, &$smarty, &$repeat) {
    if (empty($params['href'])) {
        $smarty->trigger_error("assign: missing 'href' parameter");
    }
    
    $html = '';
    
    if (!$repeat) {
      $html = '<a href="'.$params['href'].'"';
      
      if (isset($params['class'])) {
        $html .= " class=\"{$params['class']}\"";
      }
      if (isset($params['id'])) {
        $html .= " id=\"{$params['id']}\"";
      }
      if (self::$accessKey < 10) {
        $html .= ' accesskey="'.self::$accessKey.'">'.self::$accessKey.': ';
        self::$accessKey++;
      } else {
        $html .= '>';
      }
      $html .= $content.'</a>';
    }
    return $html;
  }
  
  static function smartyTemplateAccessKeyReset($params, &$smarty) {
    if (!isset($params['index'])) {
        $smarty->trigger_error("assign: missing 'index' parameter");
        return;
    }
    self::$accessKey = $params['index'];
  }
  
  //
  // Theme config files
  //
  
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
  
  
  //
  // Constructor
  //
  
  function __construct() {
    parent::__construct();

    // Device info
    $pagetype      = $GLOBALS['deviceClassifier']->getPagetype();
    $platform      = $GLOBALS['deviceClassifier']->getPlatform();
    $supportsCerts = $GLOBALS['deviceClassifier']->getSupportsCerts();
    
    // Smarty configuration
    $this->setCompileDir ($GLOBALS['siteConfig']->getVar('TEMPLATE_COMPILE_DIR'));
    $this->setCacheDir   ($GLOBALS['siteConfig']->getVar('TEMPLATE_CACHE_DIR'));
    $this->setCompileId  ("$pagetype-$platform");
    
    // Theme and device detection for includes and extends
    $this->register->resource('findExtends', array(
      'TemplateEngine::smartyResourceExtendsGetSource',
      'TemplateEngine::smartyResourceExtendsGetTimestamp',
      'TemplateEngine::smartyResourceExtendsGetSecure',
      'TemplateEngine::smartyResourceExtendsGetTrusted',
    ));
    $this->register->resource('findInclude', array(
      'TemplateEngine::smartyResourceIncludeGetSource',
      'TemplateEngine::smartyResourceIncludeGetTimestamp',
      'TemplateEngine::smartyResourceIncludeGetSecure',
      'TemplateEngine::smartyResourceIncludeGetTrusted',
    ));
    
    // Postfilter to add url prefix to absolute urls
    $this->register->outputfilter(array('TemplateEngine', 'smartyOutputfilterAddURLPrefix'));
    
    $this->register->block('html_access_key_link',  
      'TemplateEngine::smartyBlockAccessKeyLink');
    $this->register->templateFunction('html_access_key_reset', 
      'TemplateEngine::smartyTemplateAccessKeyReset');
      
    // variables common to all modules
    $this->assign('pagetype', $pagetype);
    $this->assign('platform', $platform);
    $this->assign('supportsCerts', $supportsCerts);
    $this->assign('showDeviceDetection', $GLOBALS['siteConfig']->getVar('SHOW_DEVICE_DETECTION'));
    
  }
  
  //
  // Display template for device and theme
  //
  
  function displayForDevice($page, $cacheID = null, $compileID = null, $parent = null) {
    $this->display(self::getIncludeFile($page), $cacheID, $compileID, $parent);
  }
}
