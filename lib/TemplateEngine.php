<?php
/**
  * @package Core
  */

/**
  */
require_once realpath(LIB_DIR.'/smarty/Smarty.class.php');

/**
  * @package Core
  */
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
      'THEME_DIR'    => THEME_DIR,
      'SITE_APP_DIR' => SITE_APP_DIR,
      'APP_DIR'      => APP_DIR
    );
    $checkFiles = array(
      "$subDir$page-$pagetype-$platform.tpl", // platform-specific
      "$subDir$page-$pagetype.tpl",           // pagetype-specific
      "$subDir$page.tpl"                      // default
    );
    
    foreach ($checkDirs as $type => $dir) {
      foreach ($checkFiles as $file) {
        $test = realpath_exists("$dir/$file");
        if ($test) {
          //error_log(__FUNCTION__."($pagetype-$platform) choosing '$type/$file'");
          return $test;
        }
      }
    }
    return $name;
  }
  
  public static function smartyResourceIncludeGetSource($name, &$source, $smarty) {
    $file = self::getIncludeFile($name);
    if ($file !== false) {
      $source = file_get_contents($file);
      return true;
    }
    return false;
  }

  public static function smartyResourceIncludeGetTimestamp($name, &$timestamp, $smarty) {
    $file = self::getIncludeFile($name);
    if ($file !== false) {
      $timestamp = filemtime($file);
      return true;
    }
    return false;
  }

  public static function smartyResourceIncludeGetSecure($name, $smarty) {
    return true;
  }

  public static function smartyResourceIncludeGetTrusted($name, $smarty) {
    return true;
  }
  
  //
  // Extends file resource plugin
  //
  
  static private function getExtendsFile($name) {
    $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
    $platform = $GLOBALS['deviceClassifier']->getPlatform();
    
    $checkDirs = array(
      'APP_DIR'      => APP_DIR,
      'SITE_APP_DIR' => SITE_APP_DIR,
      'THEME_DIR'    => THEME_DIR
    );
    
    foreach ($checkDirs as $type => $dir) {
        $test = realpath_exists("$dir/$name");
        if ($test) {
          //error_log(__FUNCTION__."($pagetype-$platform) choosing     '$type/$name'");
          return $test;
        }
    }
    return false;
  }
  
  public static function smartyResourceExtendsGetSource($name, &$source, $smarty) {
    $file = self::getExtendsFile($name);
    if ($file !== false) {
      $source = file_get_contents($file);
      return true;
    }
    return false;
  }

  public static function smartyResourceExtendsGetTimestamp($name, &$timestamp, $smarty) {
    $file = self::getExtendsFile($name);
    if ($file !== false) {
      $timestamp = filemtime($file);
      return true;
    }
    return false;
  }

  public static function smartyResourceExtendsGetSecure($name, $smarty) {
    return true;
  }

  public static function smartyResourceExtendsGetTrusted($name, $smarty) {
    return true;
  }
  
  private static function stripWhitespaceReplace($search, $replace, &$subject) {
    $len = strlen($search);
    $pos = 0;
    for ($i = 0, $count = count($replace); $i < $count; $i++) {
      if (($pos = strpos($subject, $search, $pos)) !== false) {
        $subject = substr_replace($subject, $replace[$i], $pos, $len);
      } else {
        break;
      }
    }
  }
  
  public static function smartyOutputfilterAddURLPrefixAndStripWhitespace($source, $smarty) {
    // rewrite urls for the device classifier in case  our root is not / 
    // also handles debugging mode for paths without hostnames
    $source = preg_replace(
      ';(url\("?\'?|href\s*=\s*"|src\s*=\s*")('.URL_PREFIX.'|'.URL_DEVICE_DEBUG_PREFIX.'|/);', '\1'.URL_PREFIX, $source);
    
    if ($GLOBALS['siteConfig']->getVar('DEVICE_DEBUG')) {
      // if we are in debugging mode we need to also rewrite full paths with hostnames
      $source = preg_replace(
        ';(url\("?\'?|href\s*=\s*"|src\s*=\s*")('.FULL_URL_PREFIX.'|'.FULL_URL_BASE.');', '\1'.FULL_URL_PREFIX, $source);
    }
    
    // Most of the following code comes from the stripwhitespace filter:
    
    // Pull out the script blocks
    preg_match_all("!<script[^>]*?>.*?</script>!is", $source, $match);
    $scriptBlocks = $match[0];
    $source = preg_replace("!<script[^>]*?>.*?</script>!is", '@@@SMARTY:TRIM:SCRIPT@@@', $source);
    
    // Pull out the pre blocks
    preg_match_all("!<pre[^>]*?>.*?</pre>!is", $source, $match);
    $preBlocks = $match[0];
    $source = preg_replace("!<pre[^>]*?>.*?</pre>!is", '@@@SMARTY:TRIM:PRE@@@', $source);
    
    // Pull out the textarea blocks
    preg_match_all("!<textarea[^>]*?>.*?</textarea>!is", $source, $match);
    $textareaBlocks = $match[0];
    $source = preg_replace("!<textarea[^>]*?>.*?</textarea>!is", '@@@SMARTY:TRIM:TEXTAREA@@@', $source);
    
    // remove all leading spaces, tabs and carriage returns NOT
    // preceeded by a php close tag.
    $source = trim(preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $source));
    
    // remove all newlines before and after tags.
    $source = preg_replace('/\n*(<[^>]+>)\n*/m', '\1', $source);

    // strip spaces around non-breaking spaces
    $source = preg_replace('/\s*&nbsp;\s*/m', '&nbsp;', $source);
    
    // replace runs of spaces with a single space.
    $source = preg_replace('/\s+/m', ' ', $source);

    // restore textarea, pre and script blocks
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:TEXTAREA@@@", $textareaBlocks, $source);
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:PRE@@@", $preBlocks, $source);
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:SCRIPT@@@", $scriptBlocks, $source);
    
    return $source;
  }
  
  //
  // Access key block and template plugins
  //
  
  public static function smartyBlockAccessKeyLink($params, $content, &$smarty, &$repeat) {
    if (empty($params['href'])) {
      trigger_error("assign: missing 'href' parameter");
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
  
  public static function smartyTemplateAccessKeyReset($params, &$smarty) {
    if (!isset($params['index'])) {
        trigger_error("assign: missing 'index' parameter");
        return;
    }
    if (self::$accessKey == 0 || (isset($params['force']) && $params['force'])) {
      self::$accessKey = $params['index'];
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
    $this->setCompileDir (CACHE_DIR.'/smarty/templates');
    $this->setCacheDir   (CACHE_DIR.'/smarty/html');
    $this->setCompileId  ("$pagetype-$platform");
    
    // Theme and device detection for includes and extends
    $this->registerResource('findExtends', array(
      array('TemplateEngine','smartyResourceExtendsGetSource'),
      array('TemplateEngine','smartyResourceExtendsGetTimestamp'),
      array('TemplateEngine','smartyResourceExtendsGetSecure'),
      array('TemplateEngine','smartyResourceExtendsGetTrusted')
    ));
    $this->registerResource('findInclude', array(
      array('TemplateEngine','smartyResourceIncludeGetSource'),
      array('TemplateEngine','smartyResourceIncludeGetTimestamp'),
      array('TemplateEngine','smartyResourceIncludeGetSecure'),
      array('TemplateEngine','smartyResourceIncludeGetTrusted')
    ));
    
    // Postfilter to add url prefix to absolute urls and
    // strip unnecessary whitespace (ignores <pre>, <script>, etc)
    $this->registerFilter('output', array('TemplateEngine', 
      'smartyOutputfilterAddURLPrefixAndStripWhitespace'));
    
    $this->registerPlugin('block', 'html_access_key_link',  
      'TemplateEngine::smartyBlockAccessKeyLink');
    $this->registerPlugin('function', 'html_access_key_reset', 
      'TemplateEngine::smartyTemplateAccessKeyReset');
      
    // variables common to all modules
    $this->assign('pagetype', $pagetype);
    $this->assign('platform', $platform);
    $this->assign('supportsCerts', $supportsCerts ? 1 : 0);
    $this->assign('showDeviceDetection', $GLOBALS['siteConfig']->getVar('DEVICE_DETECTION_DEBUG'));
    $this->assign('moduleDebug', $GLOBALS['siteConfig']->getVar('MODULE_DEBUG'));
  }
  
  //
  // Display template for device and theme
  //
  
  function displayForDevice($page, $cacheID = null, $compileID = null) {
    $this->display(self::getIncludeFile($page), $cacheID, $compileID);
  }
  
  //
  // Fetch template contents for device and theme
  //
  
  function fetchForDevice($page, $cacheID = null, $compileID = null) {
    return $this->fetch(self::getIncludeFile($page), $cacheID, $compileID);
  }
}
