<?php

require_once realpath(LIB_DIR.'/TemplateEngine.php');
require_once realpath(LIB_DIR.'/HTMLPager.php');

abstract class Module {
  protected $id = 'none';
  
  protected $page = 'index';
  protected $args = array();
  
  private $moduleName = 'No Title';
  
  private $inlineCSSBlocks = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  private $onLoadBlocks = array('scrollTo(0,1);');
  
  private $breadcrumbTitle = null;
  private $breadcrumbLongTitle = null;
  private $breadcrumbs = array();

  private $fontsize = 'medium';
  private $fontsizes = array('small', 'medium', 'large', 'xlarge');
  
  private $templateEngine = null;
  private $siteVars = null;
  
  private $htmlPager = null;
  private $inPagedMode = true;
  
  private $tabbedView = null;
  
  //
  // Tabbed View support
  //
  
  protected function enableTabs($tabs, $defaultTab=null) {
    $currentTab = $tabs[0];
    if (isset($this->args['tab']) && in_array($this->args['tab'], $tabs)) {
      $currentTab = $this->args['tab'];
      
    } else if (isset($defaultTab) && in_array($defaultTab, $tabs)) {
      $currentTab = $defaultTab;
    }
    
    $args = $this->args;
    unset($args['tab']);
    
    $this->tabbedView = array(
      'tabs'    => $tabs,
      'current' => $currentTab,
      'url'     => $this->buildBreadcrumbURL($this->page, $args, false),
    );

    $this->addInlineJavascriptFooter("showTab('{$currentTab}Tab');");
  }
  
  //
  // Pager support
  // Note: the first page is 0 (0 ... pageCount-1)
  //
  protected function enablePager($html, $pageNumber) {
    $this->htmlPager = new HTMLPager($html, $pageNumber);
  }
  
  protected function urlForPage($pageNumber) {
    return '';
  }
    
  private function getPager() {
    $pager = array(
      'pageNumber'   => $this->htmlPager->getPageNumber(),
      'pageCount'    => $this->htmlPager->getPageCount(),
      'inPagedMode'  => $this->htmlPager->getPageNumber() != ALL_PAGES,
      'html' => array(
        'all'  => $this->htmlPager->getAllPagesHTML(),
        'page' => $this->htmlPager->getPageHTML(),
      ),
      'url' => array(
        'prev'  => null,
        'next'  => null,
        'all'   => $this->urlForPage(ALL_PAGES),
        'pages' => array(),
      ),
    );

    for ($i = 0; $i < $pager['pageCount']; $i++) {
      $pager['url']['pages'][] = $this->urlForPage($i).
        $this->getBreadcrumbArgString('&', false);
    }
        
    if ($pager['pageNumber'] > 0) {
      $pager['url']['prev'] = $pager['url']['pages'][$pager['pageNumber']-1];
    }
    
    if ($pager['pageNumber'] < ($pager['pageCount']-1)) {
      $pager['url']['next'] = $pager['url']['pages'][$pager['pageNumber']+1];
    }
    
    return $pager;
  }
  
  //
  // Font size controls
  //
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

  //
  // Minify URLs
  //
  private function getMinifyUrls() {
    $minKey = $this->id.'-'.$this->page.'-'.$GLOBALS['deviceClassifier']->getPagetype().'-'.
      $GLOBALS['deviceClassifier']->getPlatform().'-'.md5(ROOT_DIR);
    $minDebug = $GLOBALS['siteConfig']->getVar('MINIFY_DEBUG') ? '&debug=1' : '';
    
    return array(
      'css' => "/min/g=css-$minKey$minDebug",
      'js'  => "/min/g=js-$minKey$minDebug",
    );
  }

  //
  // Lazy load
  //
  private function loadTemplateEngineIfNeeded() {
    if (!isset($this->templateEngine)) {
      $this->templateEngine = new TemplateEngine($this->id);
    }
  }
  
  //
  // URL helper functions
  //
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
  
  //
  // Factory function
  // instantiates objects for the different modules
  //
  public static function factory($id, $page='index', $args=array()) {
    $className = ucfirst($id).'Module';
    
    $moduleFile = realpath_exists(MODULES_DIR."/$id/$className.php");
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

    // Pull in fontsize
    if (isset($args['font'])) {
      $this->fontsize = $args['font'];
      setcookie('fontsize', $this->fontsize, time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
    
    } else if (isset($_COOKIE['fontsize'])) { 
      $this->fontsize = $_COOKIE['fontsize'];
    }
  }
  
  //
  // Module control functions
  //
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

  //
  // Functions to add inline blocks of text
  // Call these from initializeForPage()
  //
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
  protected function addOnLoad($onLoad) {
    $this->onLoadBlocks[] = $onLoad;
  }
  
  //
  // Breadcrumbs
  //
  private function loadBreadcrumbs() {
    if (isset($this->args['breadcrumbs'])) {
      $breadcrumbs = unserialize(rawurldecode($this->args['breadcrumbs']));
      if (is_array($breadcrumbs)) {
        $this->breadcrumbs = $breadcrumbs;
      }
    }
    //error_log(__FUNCTION__."(): loaded breadcrumbs ".print_r($this->breadcrumbs, true));
  }
  
  private function getBreadcrumbString($addBreadcrumb=true) {
    $breadcrumbs = $this->breadcrumbs;
    
    if ($addBreadcrumb && $this->page != 'index') {
      $pageTitle = $this->getTemplateVars('pageTitle');
      $title     = isset($this->breadcrumbTitle)     ? $this->breadcrumbTitle     : $pageTitle;
      $longTitle = isset($this->breadcrumbLongTitle) ? $this->breadcrumbLongTitle : $pageTitle;
      
      $breadcrumbs[] = array(
        'title'     => $title,
        'longTitle' => $longTitle,
        'url'       => self::buildURL($this->page, $this->args),
      );
    }
    //error_log(__FUNCTION__."(): saving breadcrumbs ".print_r($breadcrumbs, true));
    return rawurlencode(serialize($breadcrumbs));
  }
  
  private function getBreadcrumbArgs($addBreadcrumb=true) {
    return array(
      'breadcrumbs' => $this->getBreadcrumbString($addBreadcrumb),
    );
  }

  protected function buildBreadcrumbURL($page, $args, $addBreadcrumb=true) {
    return "$page.php?".http_build_query(array_merge($args, $this->getBreadcrumbArgs($addBreadcrumb)));
  }
  
  protected function getBreadcrumbArgString($prefix='?', $addBreadcrumb=true) {
    return $prefix.http_build_query($this->getBreadcrumbArgs($addBreadcrumb));
  }
  
  protected function setBreadcrumbTitle($title) {
    $this->breadcrumbTitle = $title;
  }

  protected function setBreadcrumbLongTitle($title) {
    $this->breadcrumbLongTitle = $title;
  }

  //
  // Page title
  //
  protected function setPageTitle($title) {
    $this->assign('pageTitle', $title);
  }

  //
  // Config files
  //
  protected function loadThemeConfigFile($name, $loadVarKeys=false) {
    $this->loadTemplateEngineIfNeeded();
    
    $this->templateEngine->loadThemeConfigFile($name, $loadVarKeys);
  }

  //
  // Convenience functions
  //
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
  
  //
  // Display page
  //
  public function displayPage() {
    $this->loadTemplateEngineIfNeeded();
    
    // Load site configuration and help text
    $this->loadThemeConfigFile('site', true);
    $this->loadThemeConfigFile('help');
    
    date_default_timezone_set($GLOBALS['siteConfig']->getThemeVar('site', 'SITE_TIMEZONE'));

    // Set variables common to all modules
    $this->assign('moduleID', $this->id);
    $this->assign('moduleName', $this->moduleName);
    $this->assign('page', $this->page);
    $this->assign('moduleHome', $this->page == 'index');
    $this->assign('pageTitle', $this->moduleName);
    
    // Font size for template
    $this->assign('fontsizes',   $this->fontsizes);
    $this->assign('fontsize',    $this->fontsize);
    $this->assign('fontsizeCSS', $this->getFontSizeCSS());
    $this->assign('fontSizeURL', $this->getFontSizeURL());

    // Minify URLs
    $this->assign('minify', $this->getMinifyUrls());
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
        
    // Set variables for each page
    $this->initializeForPage();

    // Variables which may have been modified by the module subclass
    $this->assign('inlineCSSBlocks', $this->inlineCSSBlocks);
    $this->assign('inlineJavascriptBlocks', $this->inlineJavascriptBlocks);
    $this->assign('onOrientationChangeBlocks', $this->onOrientationChangeBlocks);
    $this->assign('onLoadBlocks', $this->onLoadBlocks);
    $this->assign('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);

    $this->assign('breadcrumbs', $this->breadcrumbs);
    $this->assign('breadcrumbArgs', $this->getBreadcrumbArgs());

    // Module Help
    if ($this->page == 'help') {
      $this->setPageTitle('Help');
      $this->assign('hasHelp', false);
      
      $template = 'common/'.$this->page;
    } else {
      $helpConfig = $this->getTemplateVars('help');
      $this->assign('hasHelp', isset($helpConfig[$this->id]));
    
      $template = 'modules/'.$this->id.'/'.$this->page;
    }
    
    // Pager support
    if (isset($this->htmlPager)) {
      $this->assign('pager', $this->getPager());
    }
    
    // Tab support
    if (isset($this->tabbedView)) {
      $this->assign('tabbedView', $this->tabbedView);
    }

    // Load template for page
    $this->templateEngine->displayForDevice($template);    
  }
     
  //
  // Subclass this function to set up variables for each template page
  //
  abstract protected function initializeForPage(); 
}
