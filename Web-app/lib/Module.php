<?php

require_once realpath(LIB_DIR.'/TemplateEngine.php');

abstract class Module {
  protected $id = 'none';
  
  private $title = 'No Title';
  private $pageTitle = 'No Page Title';
  
  private $inlineCSSBlocks = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  
  private $breadcrumbs = array();
  
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
      throw new PageNotFound('Module "'.$id.'" not found');
    }
  }
  
  function __construct() {
    $GLOBALS['siteConfig']->loadThemeFile('modules');
    
    $modules = $GLOBALS['siteConfig']->getThemeVar('modules');
    if (isset($modules[$this->id])) {
      $this->title = $modules[$this->id]['title'];
      $this->pageTitle = $this->title;
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
    $this->pageTitle = $title;
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
    
    // Set variables common to all pages for the module
    $this->assignByRef('moduleID', $this->id);
    $this->assignByRef('title', $this->title);
    $this->assignByRef('page', $page);
    $this->assignByRef('moduleHome', $page == 'index');
    
    $minifySuffix = $this->getMinifySuffix($page);
    $this->assignByRef('minify', array(
      'css' => "../min/g=css-$minifySuffix",
      'js'  => "../min/g=js-$minifySuffix",
    ));
    
    $this->assignByRef('inlineCSSBlocks', $this->inlineCSSBlocks);
    $this->assignByRef('inlineJavascriptBlocks', $this->inlineJavascriptBlocks);
    $this->assignByRef('onOrientationChangeBlocks', $this->onOrientationChangeBlocks);
    $this->assignByRef('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);

    $this->assignByRef('breadcrumbs', $this->breadcrumbs);
    $this->assignByRef('pageTitle', $this->pageTitle);


    // Set variables for each page
    $this->initializeForPage($page, $args);
    
    $this->templateEngine->displayForDevice('modules/'.$this->id.'/'.$page);    
  }
     
  // Subclass this function to set up variables for each template page
  abstract protected function initializeForPage($page, $args); 
}
