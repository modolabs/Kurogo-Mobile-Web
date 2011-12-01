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
  public $extendsTrackerCurrentInclude = '';
  public $extendsTrackerSeenFiles = array();

  //
  // Extends file tracking
  //
  
  // The following functions track {extends} files used for device templates.
  // The purpose is to allow us to have extends relationships between files 
  // with the same name in different directories, but to be able to know
  // which directories have already been used so we don't loop.
  //
  // Fortunately for us, Smarty handles each extends chain as a unit
  // keeping the template resource_name the name of the include file 
  // throughout the entire process.
  //
  // So our basic technique is to track all the files used for a given resource_name
  // and to toss the array either when the resource_name file changes or when we
  // leave the template via the postfilter below.

  
  private function extendsTrackerReset($templateToMatch=null) {
    if (!$templateToMatch || $this->extendsTrackerUsingTemplate($templateToMatch)) {
      Kurogo::log(LOG_DEBUG, 'RESETTING TRACKER'.($this->extendsTrackerCurrentInclude ? " (old include {$this->extendsTrackerCurrentInclude})" : ''), 'template');
      $this->extendsTrackerCurrentInclude = '';
      $this->extendsTrackerSeenFiles = array();
    }
  }
  
  private function extendsTrackerCheckTemplate($template) {
    if ($template->resource_type == 'file' && !$this->extendsTrackerUsingTemplate($template)) {
      Kurogo::log(LOG_DEBUG, "RESETTING TRACKER (new include {$template->resource_name})", 'template');
      $this->extendsTrackerCurrentInclude = $template->resource_name;
      $this->extendsTrackerSeenFiles = array();
      
      $this->extendsTrackerAddFile($template->resource_name);
    }
  }
  
  private function extendsTrackerUsingTemplate($template) {
    return 
      ($template->resource_type == 'file') && 
      ($template->resource_name == $this->extendsTrackerCurrentInclude);
  }
  
  private function extendsTrackerSeenFile($file) {
    return isset($this->extendsTrackerSeenFiles[$file]);
  }
  
  private function extendsTrackerAddFile($file) {
    Kurogo::log(LOG_DEBUG, "ADDING TO TRACKER -- {$file}", 'template');
    $this->extendsTrackerSeenFiles[$file] = true;
  }
  
  //
  // Finding include files
  //
  
  static private function getIncludeFile($name) {
    $subDir = dirname($name);
    $page = basename($name, '.tpl');
    
    $pagetype = Kurogo::deviceClassifier()->getPagetype();
    $platform = Kurogo::deviceClassifier()->getPlatform();

    if (strlen($subDir)) { $subDir .= '/'; }
  
    $checkDirs = array(
      'THEME_DIR'    => THEME_DIR,
      'SITE_APP_DIR' => SITE_APP_DIR,
      'APP_DIR'      => APP_DIR,
    );
    $checkFiles = array(
      "$subDir$page-$pagetype-$platform.tpl", // platform-specific
      "$subDir$page-$pagetype.tpl",           // pagetype-specific
      "$subDir$page.tpl"                      // default
    );
    
    foreach ($checkFiles as $file) {
      foreach ($checkDirs as $type => $dir) {
        $test = realpath_exists("$dir/$file");
        if ($test) {
          Kurogo::log(LOG_DEBUG, __FUNCTION__."($pagetype-$platform) choosing '$type/$file' for '$name'", 'template');
          return addslashes($test);
        }
      }
    }
    return $name;
  }
  
  //
  // Finding extends files
  //
  
  static private function getExtendsFile($name, $template) {
    $pagetype = Kurogo::deviceClassifier()->getPagetype();
    $platform = Kurogo::deviceClassifier()->getPlatform();
    
    $checkDirs = array(
      'THEME_DIR'    => THEME_DIR,
      'SITE_APP_DIR' => SITE_APP_DIR,
      'APP_DIR'      => APP_DIR,
    );
        
    foreach ($checkDirs as $type => $dir) {
      $test = realpath_exists("$dir/$name");
      if ($test && !$template->smarty->extendsTrackerSeenFile($test)) {
        Kurogo::log(LOG_DEBUG, __FUNCTION__."($pagetype-$platform) choosing     '$type/$name' for '$name'", 'template');
        $template->smarty->extendsTrackerAddFile($test);
        return addslashes($test);
      }
    }
    return false;
  }
  
  //
  // Prefilter to map include and extend directives to real files
  //
  
  private static function replaceVariables($string, $variables) {
    $search = array();
    $replace = array();

    // TODO: fix this so it doesn't match on single { or }
    if (preg_match_all(';{?\$([A-za-z]\w*)}?;', $string, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $i => $variable) {
        if (isset($variables[$variable]) && is_string($variables[$variable])) {
          $search[] = $matches[0][$i];
          $replace[] = $variables[$variable];
        }
      }
    }
    return $search ? str_replace($search, $replace, $string) : $string;
  }
  
  public static function smartyPrefilterHandleIncludeAndExtends($source, $template) {
    $template->smarty->extendsTrackerCheckTemplate($template);
    
    $variables = $template->smarty->getTemplateVars();
    
    // findIncludes
    $search = array();
    $replace = array();
    if (preg_match_all(';=\s*"findInclude:([^"]+)";', $source, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $i => $name) {
        $path = self::getIncludeFile(self::replaceVariables($name, $variables));
        if ($path) {
          $search[] = $matches[0][$i];
          $replace[] = '="file:'.$path.'"';
          Kurogo::log(LOG_DEBUG, __FUNCTION__." replacing include $name with $path", 'template');
        } else {
          Kurogo::log(LOG_WARNING, __FUNCTION__." FAILED to find INCLUDE for $name", 'template');
        }
      }
    }
    if (preg_match_all(';=\s*"findExtends:([^"]+)";', $source, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $i => $name) {
        $path = self::getExtendsFile(self::replaceVariables($name, $variables), $template);
        if ($path) {
          $search[] = $matches[0][$i];
          $replace[] = '="file:'.$path.'"';
          Kurogo::log(LOG_DEBUG, __FUNCTION__." replacing extends $name with $path", 'template');
        } else {
          Kurogo::log(LOG_WARNING, __FUNCTION__." FAILED to find EXTENDS for $name", 'template');
          throw new SmartyException("Unable to load template \"findExtends : $name\"");
        }
      }
    }
    
    return $search ? str_replace($search, $replace, $source) : $source;
  }
  
  //
  // Filter to attempt to remove XSS injection attacks without removing HTML
  //
  public static function smartyModifierSanitizeHTML($string, $allowedTags='editor') {
    return Sanitizer::sanitizeHTML($string, $allowedTags);
  }
  
  //
  // Filter to remove javascript from urls
  // Assumes URL is dumped into href or src attr as-is
  //
  public static function smartyModifierSanitizeURL($string) {
    return Sanitizer::sanitizeURL($string);
  }
  
  //
  // Postfilter to detect when we are leaving an extends dependency chain
  //

  public static function smartyPostfilterHandleIncludeAndExtends($source, $template) {
    $template->smarty->extendsTrackerReset($template);
    return $source;
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
      ';(url\("?\'?|href\s*=\s*"|src\s*=\s*"|action\s*=\s*")('.URL_PREFIX.'|'.URL_DEVICE_DEBUG_PREFIX.'|/);', '\1'.URL_PREFIX, $source);
    
    if (Kurogo::getSiteVar('DEVICE_DEBUG')) {
      // if we are in debugging mode we need to also rewrite full paths with hostnames
      $source = preg_replace(
        ';(url\("?\'?|href\s*=\s*"|src\s*=\s*")('.FULL_URL_PREFIX.'|'.FULL_URL_BASE.');', '\1'.FULL_URL_PREFIX, $source);
    }
    
    // Most of the following code comes from the stripwhitespace filter:
    
    // Pull out the style blocks
    preg_match_all("!<style[^>]*?>.*?</style>!is", $source, $match);
    $styleBlocks = $match[0];
    $source = preg_replace("!<style[^>]*?>.*?</style>!is", '@@@SMARTY:TRIM:STYLE@@@', $source);
    
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

    // restore textarea, pre, script and style blocks
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:TEXTAREA@@@", $textareaBlocks, $source);
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:PRE@@@", $preBlocks, $source);
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:SCRIPT@@@", $scriptBlocks, $source);
    self::stripWhitespaceReplace("@@@SMARTY:TRIM:STYLE@@@", $styleBlocks, $source);
    
    return $source;
  }
  
  //
  // Access key block and template plugins
  //
  
  public static function smartyBlockAccessKeyLink($params, $content, &$smarty, &$repeat) {
    if (empty($params['href'])) {
      Kurogo::log(LOG_WARNING, "assign: missing 'href' parameter", 'template');
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
      if (self::$accessKey < 10 && Kurogo::deviceClassifier()->getPlatform() != "blackberry") {
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
        Kurogo::log(LOG_WARNING, "assign: missing 'index' parameter", 'template');
        return;
    }
    if (self::$accessKey == 0 || (isset($params['force']) && $params['force'])) {
      self::$accessKey = $params['index'];
    }
  }
  
  public function nosecure($string) {
    return str_replace('https://','http://',$string);
  }
  
  
  //
  // Constructor
  //
  
  function __construct() {
    parent::__construct();

    // Fix this in a later release -- currently generates lots of warnings
    $this->error_reporting = E_ALL & ~E_NOTICE;

    // Device info
    $pagetype      = Kurogo::deviceClassifier()->getPagetype();
    $platform      = Kurogo::deviceClassifier()->getPlatform();
    $supportsCerts = Kurogo::deviceClassifier()->getSupportsCerts();
    
    // Smarty configuration
    $this->setCompileDir (CACHE_DIR.'/smarty/templates');
    $this->setCacheDir   (CACHE_DIR.'/smarty/html');
    $this->setCompileId  ("$pagetype-$platform");
    
    $this->registerPlugin('modifier', 'sanitize_html', array('TemplateEngine',
      'smartyModifierSanitizeHTML'));
    $this->registerPlugin('modifier', 'sanitize_url', array('TemplateEngine',
      'smartyModifierSanitizeURL'));
    
    $this->registerFilter('pre', array('TemplateEngine', 
      'smartyPrefilterHandleIncludeAndExtends'));
    $this->registerFilter('post', array('TemplateEngine', 
      'smartyPostfilterHandleIncludeAndExtends'));

    // Postfilter to add url prefix to absolute urls and
    // strip unnecessary whitespace (ignores <pre>, <script>, etc)
    $this->registerFilter('output', array('TemplateEngine', 
      'smartyOutputfilterAddURLPrefixAndStripWhitespace'));
    
    $this->registerPlugin('block', 'html_access_key_link',  
      'TemplateEngine::smartyBlockAccessKeyLink');
    $this->registerPlugin('function', 'html_access_key_reset', 
      'TemplateEngine::smartyTemplateAccessKeyReset');

    $this->registerPlugin('modifier', 'nosecure', 
      array($this,'nosecure'));
      
    // variables common to all modules
    $this->assign('pagetype', $pagetype);
    $this->assign('platform', $platform);
    $this->assign('supportsCerts', $supportsCerts ? 1 : 0);
    $this->assign('showDeviceDetection', Kurogo::getSiteVar('DEVICE_DETECTION_DEBUG'));
    $this->assign('moduleDebug', Kurogo::getSiteVar('MODULE_DEBUG'));
  }
  
  //
  // Display template for device and theme
  //
  
  function displayForDevice($page, $cacheID = null, $compileID = null) {
    $this->extendsTrackerReset();
  
    $this->display(self::getIncludeFile($page), $cacheID, $compileID);
  }
  
  //
  // Fetch template contents for device and theme
  //
  
  function fetchForDevice($page, $cacheID = null, $compileID = null) {
    $this->extendsTrackerReset();

    return $this->fetch(self::getIncludeFile($page), $cacheID, $compileID);
  }
}
