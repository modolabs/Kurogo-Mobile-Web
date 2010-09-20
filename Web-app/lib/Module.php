<?php

require_once realpath(LIB_DIR.'/TemplateEngine.php');

abstract class Module {
  protected $id = 'none';
  
  private $moduleName = 'No Title';
  
  private $inlineCSSBlocks = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  
  private $breadcrumbs = array();

  private $fontsize = 'medium';
  private $fontsizes = array('small', 'medium', 'large', 'xlarge');
  
  private $templateEngine = null;
  private $siteVars = null;
  
  private function getMinifySuffix($page) {
    $minifySuffix = implode('-', array(
      $this->id, 
      $page
    ));
    
    if ($GLOBALS['siteConfig']->getVar('MINIFY_DEBUG')) {
      $minifySuffix .= '&debug=1';
    }

    return $minifySuffix;
  }
  
  
  private function getFontSizeCSS() {
    switch ($this->fontsize) {
      case 'small':
        return 'body { font-size: 89%; line-height: 1.33em }';
      case 'large':
        return 'body { font-size: 125%; line-height: 1.33em }';
      case 'xlarge':
        return 'body { font-size: 150%; line-height: 1.33em }';
      default:
        return 'body { font-size: 100%; line-height: 1.33em }';
    }
  }
   
  private function getFontSizeURL($page, $args) {
    unset($args['font']);
    $argString = http_build_query($args);
    if (strlen($argString)) {
      return "$page.php?$argString&font=";
    } else {
      return "$page.php?font=";
    }
  }

  private function loadTemplateEngineIfNeeded() {
    if (!isset($this->templateEngine)) {
      $this->templateEngine = new TemplateEngine($this->id);
    }
  }
  
  protected static function argVal($args, $key, $default=null) {
    if (isset($args[$key])) {
      return $args[$key];
    } else {
      return $default;
    }
  }
  
  // Factory function that instantiates objects for the different modules
  public static function factory($id) {
    $className = ucfirst($id).'Module';
    
    $moduleFile = realpath($GLOBALS['siteConfig']->getVar('MODULES_DIR')."/$id/$className.php");
    if ($moduleFile && include_once($moduleFile)) {
      return new $className;
      
    } else {
      throw new PageNotFound("Module '$id' not found while handling '{$_SERVER['REQUEST_URI']}'");
    }
  }
  
  function __construct() {
    $GLOBALS['siteConfig']->loadThemeFile('modules');
    
    $modules = $GLOBALS['siteConfig']->getThemeVar('modules');
    if (isset($modules[$this->id])) {
      $this->moduleName = $modules[$this->id]['title'];
    }
  }
  
  // Functions to add inline blocks of text
  // Call these from initializeForPage()
  protected function addInlineCSS($inlineCSS) {
    $this->inlineCSSBlocks[] = $inlineCSS;
  }
  protected function addInlineJavascript($inlineJavascript) {
    $this->inlineJavascriptBlocks[] = $inlineJavascript;
  }
  protected function addInlineJavascriptFooter($inlineJavascript) {
    $this->inlineJavascriptFooterBlocks[] = $inlineJavascript;
  }
  protected function addOnOrientationChange($onOrientationChange) {
    $this->onOrientationChangeBlocks[] = $onOrientationChange;
  }
  
  protected function addBreadcrumb($text, $url, $class='') {
    $breadcrumbs[] = array(
      'text'  => $text,
      'url'   => $url,
      'class' => $class,
    );
  }
  
  protected function setPageTitle($title) {
    $this->assign('pageTitle', $title);
  }

  protected function loadThemeConfigFile($name) {
    $this->loadTemplateEngineIfNeeded();
    
    $this->templateEngine->loadThemeConfigFile($name);
  }

  // convenience functions
  public function assignByRef($var, $value) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assignByRef($var, $value);
  }
  
  public function assign($var, $value) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assign($var, $value);
  }
  
  public function getTemplateVars($key) {
    $this->loadTemplateEngineIfNeeded();
    
    return $this->templateEngine->getTemplateVars($key);
  }
  
  public function displayPage($page='index', $args=array()) {
    $this->loadTemplateEngineIfNeeded();
    
    // Font size for template
    if (isset($_REQUEST['font'])) {
      $this->fontsize = $_REQUEST['font'];
      setcookie('fontsize', $this->fontsize, time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), '/');      
    
    } else if (isset($_COOKIE['fontsize'])) { 
      $this->fontSize = $_COOKIE['fontsize'];
    }
    
    // Set variables common to all pages
    $this->assign('moduleID', $this->id);
    $this->assign('moduleName', $this->moduleName);
    $this->assign('page', $page);
    $this->assign('moduleHome', $page == 'index');
    
    $this->assign('fontsizes', $this->fontsizes);
    $this->assign('fontsize', $this->fontsize);
    $this->assign('fontsizeCSS', $this->getFontSizeCSS());
    $this->assign('fontSizeURL', $this->getFontSizeURL($page, $args));

    
    $minifySuffix = $this->getMinifySuffix($page);
    $this->assign('minify', array(
      'css' => "../min/g=css-$minifySuffix",
      'js'  => "../min/g=js-$minifySuffix",
    ));
    
    $this->assign('inlineCSSBlocks', $this->inlineCSSBlocks);
    $this->assign('inlineJavascriptBlocks', $this->inlineJavascriptBlocks);
    $this->assign('onOrientationChangeBlocks', $this->onOrientationChangeBlocks);
    $this->assign('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);

    $this->assign('breadcrumbs', $this->breadcrumbs);
    $this->assign('pageTitle', $this->moduleName);

    // Set variables for each page
    $this->initializeForPage($page, $args);
    
    $this->templateEngine->displayForDevice('modules/'.$this->id.'/'.$page);    
  }
     
  // Subclass this function to set up variables for each template page
  abstract protected function initializeForPage($page, $args); 
}
