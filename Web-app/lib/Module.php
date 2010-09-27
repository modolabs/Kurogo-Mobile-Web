<?php

require_once realpath(LIB_DIR.'/TemplateEngine.php');

abstract class Module {
  protected $id = 'none';
  
  protected $page = 'index';
  protected $args = array();
  
  private $moduleName = 'No Title';
  
  private $inlineCSSBlocks = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  
  private $breadcrumbTitle = null;
  private $breadcrumbs = array();

  private $fontsize = 'medium';
  private $fontsizes = array('small', 'medium', 'large', 'xlarge');
  
  private $templateEngine = null;
  private $siteVars = null;
  
  
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
   
  private function getFontSizeURL() {
    unset($this->args['font']);
    $argString = http_build_query($this->args);
    if (strlen($argString)) {
      return "/{$this->id}/{$this->page}.php?$argString&font=";
    } else {
      return "/{$this->id}/{$this->page}.php?font=";
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

  private static function buildURL($page, $args) {
    $argString = '';
    if (isset($args) && count($args)) {
      $argString = http_build_query($args);
    }
    
    return "$page.php".(strlen($argString) ? "?$argString" : "");
  }

  protected function redirectTo($page, $args=null) {
    if (!isset($args)) { $args = $this->args; }
  
    header("Location: ./".self::buildURL($page, $args));
    exit;
  }
  
  // Factory function that instantiates objects for the different modules
  public static function factory($id, $page='index', $args=array()) {
    $className = ucfirst($id).'Module';
    
    $moduleFile = realpath($GLOBALS['siteConfig']->getVar('MODULES_DIR')."/$id/$className.php");
    if ($moduleFile && include_once($moduleFile)) {
      return new $className($page, $args);
      
    } else {
      throw new PageNotFound("Module '$id' not found while handling '{$_SERVER['REQUEST_URI']}'");
    }
  }
  
  function __construct($page='index', $args=array()) {
    $GLOBALS['siteConfig']->loadThemeFile('modules');
    
    $modules = $GLOBALS['siteConfig']->getThemeVar('modules');
    if (isset($modules[$this->id])) {
      $this->moduleName = $modules[$this->id]['title'];
    }
    
    $this->page = $page;
    $this->args = $args;
  }
  
  // Module control functions
  protected function getHomeScreenModules() {
    $modules = $GLOBALS['siteConfig']->getThemeVar('modules');
    
    foreach ($modules as $id => $info) {
      if (!$info['homescreen']) {
        unset($modules[$id]);
      }
    }
    
    if (isset($_COOKIE["activemodules"])) {
      if ($_COOKIE["activemodules"] == "NONE") {
        $activeModuleIDs = array();
      } else {
        $activeModuleIDs = array_flip(explode(",", $_COOKIE["activemodules"]));
      }
      foreach ($modules as $moduleID => &$info) {
         $info['disabled'] = !isset($activeModuleIDs[$moduleID]) && $info['disableable'];
      }
    }

    if (isset($_COOKIE["moduleorder"])) {
      $sortedModuleIDs = explode(",", $_COOKIE["moduleorder"]);
      $unsortedModuleIDs = array_diff(array_keys($modules), $sortedModuleIDs);
            
      $sortedModules = array();
      foreach (array_merge($sortedModuleIDs, $unsortedModuleIDs) as $moduleID) {
        if (isset($modules[$moduleID])) {
          $sortedModules[$moduleID] = $modules[$moduleID];
        }
      }
      $modules = $sortedModules;
    }    
    //error_log('$modules(): '.print_r(array_keys($modules), true));
    return $modules;
  }
  
  protected function setHomeScreenModuleOrder($moduleIDs) {
    $lifespan = $GLOBALS['siteConfig']->getVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = implode(",", $moduleIDs);
    
    setcookie("moduleorder", $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE["moduleorder"] = $value;
    error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setHomeScreenActiveModules($moduleIDs) {
    $lifespan = $GLOBALS['siteConfig']->getVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = count($moduleIDs) ? implode(",", $moduleIDs) : 'NONE';
    
    setcookie("activemodules", $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE["activemodules"] = $value;
    error_log(__FUNCTION__.'(): '.print_r($value, true));
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
  
  // Breadcrumbs
  private function loadBreadcrumbs() {
    if (isset($this->args['breadcrumbs'])) {
      $breadcrumbs = unserialize(rawurldecode($this->args['breadcrumbs']));
      if (is_array($breadcrumbs)) {
        $this->breadcrumbs = $breadcrumbs;
      }
    }
    //error_log(__FUNCTION__."(): loaded breadcrumbs ".print_r($this->breadcrumbs, true));
  }
  
  private function getBreadcrumbString() {
    $breadcrumbs = $this->breadcrumbs;
    
    if ($this->page != 'index') {
      $title = isset($this->breadcrumbTitle) ? 
        $this->breadcrumbTitle : $this->getTemplateVars('pageTitle');
    
      $breadcrumbs[] = array(
        'title' => $title,
        'url'   => self::buildURL($this->page, $this->args),
      );
    }
    //error_log(__FUNCTION__."(): saving breadcrumbs ".print_r($breadcrumbs, true));
    return rawurlencode(serialize($breadcrumbs));
  }
  
  private function getBreadcrumbInputs() {
    return '<input type="hidden" name="breadcrumbs" value="'.$this->getBreadcrumbString().'" />';
  }
  
  protected function buildBreadcrumbURL($page, $args) {
    return "$page.php?".http_build_query(array_merge($args, array(
      'breadcrumbs' => $this->getBreadcrumbString(),
    )));
  }
  
  protected function getBreadcrumbArgString($prefix='?') {
    return $prefix.http_build_query(array(
      'breadcrumbs' => $this->getBreadcrumbString(),
    ));
  }
  
  protected function setBreadcrumbTitle($title) {
    $this->breadcrumbTitle = $title;
  }

  // Page title
  protected function setPageTitle($title) {
    $this->assign('pageTitle', $title);
  }

  // Config files
  protected function loadThemeConfigFile($name) {
    $this->loadTemplateEngineIfNeeded();
    
    $this->templateEngine->loadThemeConfigFile($name);
  }

  // Convenience functions
  public function assignByRef($var, &$value) {
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
  
  public function displayPage() {
    $this->loadTemplateEngineIfNeeded();
    
    // Set variables common to all pages
    $this->assign('moduleID', $this->id);
    $this->assign('moduleName', $this->moduleName);
    $this->assign('page', $this->page);
    $this->assign('moduleHome', $this->page == 'index');
    
    // Font size for template
    if (isset($args['font'])) {
      $this->fontsize = $args['font'];
      setcookie('fontsize', $this->fontsize, time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
    
    } else if (isset($_COOKIE['fontsize'])) { 
      $this->fontsize = $_COOKIE['fontsize'];
    }
    $this->assign('fontsizes',   $this->fontsizes);
    $this->assign('fontsize',    $this->fontsize);
    $this->assign('fontsizeCSS', $this->getFontSizeCSS());
    $this->assign('fontSizeURL', $this->getFontSizeURL());

    // Minify URLs
    $minifyDebug = $GLOBALS['siteConfig']->getVar('MINIFY_DEBUG') ? '&debug=1' : '';
    $this->assign('minify', array(
      'css' => "/min/g=css-{$this->id}-{$this->page}$minifyDebug",
      'js'  => "/min/g=js-{$this->id}-{$this->page}$minifyDebug",
    ));
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Set variables for each page
    $this->initializeForPage();

    // variables which may have been modified by the module subclass
    $this->assign('inlineCSSBlocks', $this->inlineCSSBlocks);
    $this->assign('inlineJavascriptBlocks', $this->inlineJavascriptBlocks);
    $this->assign('onOrientationChangeBlocks', $this->onOrientationChangeBlocks);
    $this->assign('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);

    $this->assign('breadcrumbs', $this->breadcrumbs);
    $this->assign('breadcrumbInputs', $this->getBreadcrumbInputs());
    $this->assign('pageTitle', $this->moduleName);
    
    $this->templateEngine->displayForDevice('modules/'.$this->id.'/'.$this->page);    
  }
     
  // Subclass this function to set up variables for each template page
  abstract protected function initializeForPage(); 
}
