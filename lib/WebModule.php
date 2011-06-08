<?php
/**
  * @package Module
  */

/**
  * Breadcrumb Parameter
  */
define('MODULE_BREADCRUMB_PARAM', '_b');
define('DISABLED_MODULES_COOKIE', 'disabledmodules');
define('MODULE_ORDER_COOKIE', 'moduleorder');
define('BOOKMARK_COOKIE_DELIMITER', '@@');

abstract class WebModule extends Module {

  protected $moduleName = '';
      
  protected $page = 'index';

  protected $templateModule = 'none'; 
  protected $templatePage = 'index';
  
  protected $pagetype = 'unknown';
  protected $platform = 'unknown';
  protected $supportsCerts = false;
  
  protected $imageExt = '.png';
  
  private $pageConfig = null;
  
  private $pageTitle           = 'No Title';
  private $breadcrumbTitle     = 'No Title';
  private $breadcrumbLongTitle = 'No Title';
  
  private $inlineCSSBlocks = array();
  private $cssURLs = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  private $onLoadBlocks = array('scrollTo(0,1);');
  private $javascriptURLs = array();

  private $moduleDebugStrings = array();
  
  private $breadcrumbs = array();

  private $fontsize = 'medium';
  private $fontsizes = array('small', 'medium', 'large', 'xlarge');
  
  protected $bookmarkLinkTitle = 'Bookmarks';
  
  private $templateEngine = null;
  
  private $htmlPager = null;
  private $inPagedMode = true;
  
  private $tabbedView = null;
  
  protected $refreshTime = 0;
  protected $cacheMaxAge = 0;
  
  protected $autoPhoneNumberDetection = true;
  protected $canBeAddedToHomeScreen = true;
  
  //
  // Tabbed View support
  //
  
  protected function enableTabs($tabKeys, $defaultTab=null, $javascripts=array()) {
    $currentTab = $tabKeys[0];
    if (isset($this->args['tab']) && in_array($this->args['tab'], $tabKeys)) {
      $currentTab = $this->args['tab'];
      
    } else if (isset($defaultTab) && in_array($defaultTab, $tabKeys)) {
      $currentTab = $defaultTab;
    }
    
    $tabs = array();
    foreach ($tabKeys as $tabKey) {
      $title = ucwords($tabKey);
      $configKey = "tab_{$tabKey}";
      if (isset($this->pageConfig, $this->pageConfig[$configKey]) && 
          strlen($this->pageConfig[$configKey])) {
        $title = $this->pageConfig[$configKey];
      }
      
      $tabArgs = $this->args;
      $tabArgs['tab'] = $tabKey;
      
      $tabs[$tabKey] = array(
        'title' => $title,
        'url'   => $this->buildBreadcrumbURL($this->page, $tabArgs, false),
        'javascript' => isset($javascripts[$tabKey]) ? $javascripts[$tabKey] : '',
      );
    }
    
    $this->tabbedView = array(
      'tabs'       => $tabs,
      'current'    => $currentTab,
    );

    $currentJS = $tabs[$currentTab]['javascript'];
    $this->addInlineJavascriptFooter("showTab('{$currentTab}Tab');{$currentJS}");
  }
  
  //
  // Pager support
  // Note: the first page is 0 (0 ... pageCount-1)
  //
  protected function enablePager($html, $encoding, $pageNumber) {
    $this->htmlPager = new HTMLPager($html, $encoding, $pageNumber);
  }
  
  // Override in subclass if you are using the pager
  protected function urlForPage($pageNumber) {
    return '';
  }
    
  private function getPager() {
    $pager = array(
      'pageNumber'   => $this->htmlPager->getPageNumber(),
      'pageCount'    => $this->htmlPager->getPageCount(),
      'inPagedMode'  => $this->htmlPager->getPageNumber() != HTMLPager::ALL_PAGES,
      'html' => array(
        'all'  => $this->htmlPager->getAllPagesHTML(),
        'page' => $this->htmlPager->getPageHTML(),
      ),
      'url' => array(
        'prev'  => null,
        'next'  => null,
        'all'   => $this->urlForPage(HTMLPager::ALL_PAGES),
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
   
  private function getFontSizeURLs() {
    $urls = array();
    
    $args = $this->args;
    foreach ($this->fontsizes as $fontsize) {
      $args['font'] = $fontsize;
      $urls[$fontsize] = $this->buildURL($this->page, $args);
    }
    return $urls;
  }

  //
  // Minify URLs
  //
  private function getMinifyArgString($pageOnly=false) {
    $minifyArgs = array();
    if (Kurogo::getSiteVar('MINIFY_DEBUG')) {
      $minifyArgs['debug'] = 1;
    }
    if ($pageOnly) {
      $minifyArgs['pageOnly'] = 'true';
    }
    
    if ($this->id != $this->configModule) {
      $minifyArgs['config'] = $this->configModule;
    }
    
    $minifyArgString = http_build_query($minifyArgs);
    
    return ($minifyArgString ? "&$minifyArgString" : '');
  }
  
  private function getMinifyUrls($pageOnly=false) {
    $page = preg_replace('/[\s-]+/', '+', $this->page);
    $minKey = "{$this->id}-{$page}-{$this->pagetype}-{$this->platform}-".md5(THEME_DIR);
    
    return array(
      'css' => "/min/g=css-$minKey".$this->getMinifyArgString($pageOnly),
      'js'  => "/min/g=js-$minKey".$this->getMinifyArgString($pageOnly),
    );
  }
  
  public static function getListItemClasses() {
    return array(
        'email'=>'Email',
        'phone'=>'Phone',
        'map'=>'Map'
    );
  }

  //
  // Google Analytics for non-Javascript devices
  //
  private function googleAnalyticsGetImageUrl($gaID) {
    if (isset($gaID) && strlen($gaID)) {
      $url = '/ga.php?';
      $url .= "utmac=$gaID";
      $url .= '&utmn=' . rand(0, 0x7fffffff);
  
      $referer = $this->argVal($_SERVER, 'HTTP_REFERER');
      $path    = $this->argVal($_SERVER, 'REQUEST_URI');
  
      if (!isset($referer)) {
        $referer = '-';
      }
      $url .= '&utmr=' . urlencode($referer);
  
      if (isset($path)) {
        $url .= '&utmp=' . urlencode($path);
      }
  
      $url .= '&guid=ON';

      return $url;
      
    } else {
      return '';
    }
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
  protected function buildURL($page, $args=array()) {
    return self::buildURLForModule($this->configModule, $page, $args);
  }

  public static function buildURLForModule($id, $page, $args=array()) {
    $argString = '';
    if (isset($args) && count($args)) {
      $argString = http_build_query($args);
    }
  
    return "/$id/$page".(strlen($argString) ? "?$argString" : "");
  }
  
  protected function buildMailToLink($to, $subject, $body) {
    $to = trim($to);
    
    if ($to == '' && Kurogo::deviceClassifier()->mailToLinkNeedsAtInToField()) {
      $to = '@';
    }

    $url = "mailto:{$to}?".http_build_query(array("subject" => $subject, 
                                                  "body"    => $body));
    
    // mailto url's do not respect '+' (as space) so we convert to %20
    $url = str_replace('+', '%20', $url); 
    
    return $url;
  }

  public function redirectToModule($id, $page, $args=array()) {
    $url = self::buildURLForModule($id, $page, $args);
    //error_log('Redirecting to: '.$url);
    
    header("Location: ". URL_PREFIX . ltrim($url, '/'));
    exit;
  }

  protected function redirectTo($page, $args=null, $preserveBreadcrumbs=false) {
    if (!isset($args)) { $args = $this->args; }
    
    $url = '';
    if ($preserveBreadcrumbs) {
      $url = $this->buildBreadcrumbURL($page, $args, false);
    } else {
      $url = $this->buildURL($page, $args);
    }
    
    //error_log('Redirecting to: '.$url);
    header("Location: ". URL_PREFIX . ltrim($url, '/'));
    exit;
  }
    
  protected function unauthorizedAccess() {
        if ($this->isLoggedIn()) {  
            $this->redirectToModule('error', '', array('url'=>$_SERVER['REQUEST_URI'], 'code'=>'protected'));
        } else {
            $this->redirectToModule('login', '', array('url'=>$_SERVER['REQUEST_URI']));
        }
  }
  
  //
  // Factory function
  // instantiates objects for the different modules
  //
  public static function factory($id, $page='', $args=array()) {
  
    if (!$module = parent::factory($id, 'web')) {
        return false;
    }
    $module->init($page, $args);
    $module->initialize();

      return $module;
    }
    
    protected function init($page='', $args=array()) {
      
        if ($page) {
            parent::init();
        }

        $this->moduleName = $this->getModuleVar('title','module');

        $this->setArgs($args);

        $this->pagetype      = Kurogo::deviceClassifier()->getPagetype();
        $this->platform      = Kurogo::deviceClassifier()->getPlatform();
        $this->supportsCerts = Kurogo::deviceClassifier()->getSupportsCerts();

        // Pull in fontsize
        if (isset($args['font'])) {
          $this->fontsize = $args['font'];
          setcookie('fontsize', $this->fontsize, time() + Kurogo::getSiteVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
        
        } else if (isset($_COOKIE['fontsize'])) { 
          $this->fontsize = $_COOKIE['fontsize'];
        }

        switch ($this->pagetype) {
          case 'compliant':
            $this->imageExt = '.png';
            break;
            
          case 'touch':
          case 'basic':
            $this->imageExt = '.gif';
            break;
        }
        
        if ($page) {
          $this->setPage($page);
          $this->setTemplatePage($this->page, $this->id);
          $this->setAutoPhoneNumberDetection(Kurogo::getSiteVar('AUTODETECT_PHONE_NUMBERS'));
        }
    }
  
    protected function initialize() {
    
    }
  protected function setAutoPhoneNumberDetection($bool) {
    $this->autoPhoneNumberDetection = $bool ? true : false;
    $this->assign('autoPhoneNumberDetection', $this->autoPhoneNumberDetection);
  }
    
  public function getModuleName() {
    return $this->moduleName;
  }
    
  protected function moduleDisabled() {
    $this->redirectToModule('error', '', array('code'=>'disabled', 'url'=>$_SERVER['REQUEST_URI']));
  }
  
    public static function getAllThemes() {
        $themes = array();
        $d = dir(SITE_DIR . "/themes");
        while (false !== ($entry = $d->read())) {
            if ($entry[0]!='.' && is_dir(SITE_DIR . "/themes/$entry")) {
                $themes[$entry] = $entry;
            }
        }
        $d->close();
        return $themes;
    }
    
    protected function secureModule() {
        $secure_host = Kurogo::getOptionalSiteVar('SECURE_HOST', $_SERVER['SERVER_NAME']);
        if (empty($secure_host)) {
            $secure_host = $_SERVER['SERVER_NAME'];
        }
        $secure_port = Kurogo::getOptionalSiteVar('SECURE_PORT', 443);
        if (empty($secure_port)) {
            $secure_port = 443;
        }

        $redirect= sprintf("https://%s%s%s", $secure_host, $secure_port == 443 ? '': ":$secure_port", $_SERVER['REQUEST_URI']);
        header("Location: $redirect");          
        exit();
    }

  //
  // Module control functions
  //
    public static function getAllModuleNames() {
        $modules = array();
        foreach (self::getAllModules() as $module) {
            $modules[$module->getConfigModule()] = $module->getModuleName();
        }
        
        return $modules;
    }
    
    public function canBeAddedToHomeScreen() {
        return $this->canBeAddedToHomeScreen;
    }
  
  protected function getAllModules() {
    $dirs = array(MODULES_DIR, SITE_MODULES_DIR);
    $modules = array();
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $d = dir($dir);
            while (false !== ($entry = $d->read())) {
                if ($entry[0]!='.' && is_dir(sprintf("%s/%s", $dir, $entry))) {
                    try {
                        if ($module = WebModule::factory($entry)) {
                           $modules[$entry] = $module;
                        }
                    } catch (Exception $e) {
                    }
                }
            }
            $d->close();
        }
    }
    ksort($modules);    
    return $modules;        
  }


  //
  // Module list control functions
  //
  protected function getModuleNavigationConfig() {
    static $moduleNavConfig;
    if (!$moduleNavConfig) {
        $moduleNavConfig = ModuleConfigFile::factory('home', 'module');
    }
    
    return $moduleNavConfig;
  }

  protected function getModuleNavlist() {
    $navModules = $this->getNavigationModules(false);
    if (count($navModules['primary']) && count($navModules['secondary'])) {
      $separator = array('separator' => array('separator' => true));
    } else {
      $separator = array();
    }
        
    return array_merge($navModules['home'], $navModules['primary'], $separator, $navModules['secondary']);
  }
  
  protected function isOnHomeScreen($includeDisabled=true) {
    $navModules = $this->getNavigationModules($includeDisabled);
    $allModules = array_merge(array_keys($navModules['primary']), array_keys($navModules['secondary']));
    return in_array($this->configModule, $allModules);    
  }
  
  protected function getModuleCustomizeList() {    
    $navModules = $this->getNavigationModules(true);
    return $navModules['primary'];
  }

  protected function getNavigationModuleAttributes($id, $title, $type, $disabled){
    $selected  = $this->configModule == $id;
    $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '-selected' : '';
    $primary   = $type == 'primary';
    $classes   = array();
    
    if ($selected) { $classes[] = 'selected'; }
    if (!$primary) { $classes[] = 'utility'; }
    
    return array(
      'title'       => $title,
      'shortTitle'  => $title,
      'url'         => "/$id/",
      'primary'     => $primary,
      'disableable' => true,
      'disabled'    => $disabled,
      'img'         => "/modules/home/images/{$id}{$imgSuffix}".$this->imageExt,
      'class'       => implode(' ', $classes),
    );
  }

  protected function getNavigationModules($includeDisabled=true) {
    $moduleNavConfig = $this->getModuleNavigationConfig();
    
    $moduleConfig = array();
    $moduleConfig['home'] = $this->pagetype == 'tablet' ? array('home'=>'Home') : array();
    $moduleConfig['primary'] = $moduleNavConfig->getOptionalSection('primary_modules');
    $moduleConfig['secondary'] = $moduleNavConfig->getOptionalSection('secondary_modules');

    $disabledIDs = array();
    if (isset($_COOKIE[DISABLED_MODULES_COOKIE]) && $_COOKIE[DISABLED_MODULES_COOKIE] != "NONE") {
      $disabledIDs = explode(",", $_COOKIE[DISABLED_MODULES_COOKIE]);
    }
    
    $modules = array(
      'home'    => array(),
      'primary' => array(),
      'secondary' => array()
    );
    
    foreach ($moduleConfig as $type => $modulesOfType) {
      foreach ($modulesOfType as $id => $title) {
        $disabled = in_array($id, $disabledIDs);
        
        if ($includeDisabled || !$disabled) {
          $modules[$type][$id] = $this->getNavigationModuleAttributes(
            $id,
            $title,
            $type,
            $disabled
          );
        }
      }
    }
    
    // sort primary modules if sort cookie is set
    if (isset($_COOKIE[MODULE_ORDER_COOKIE])) {
      $sortedIDs = array_merge(array('home'), explode(",", $_COOKIE[MODULE_ORDER_COOKIE]));
      $unsortedIDs = array_diff(array_keys($modules['primary']), $sortedIDs);
            
      $sortedModules = array();
      foreach (array_merge($sortedIDs, $unsortedIDs) as $id) {
        if (isset($modules['primary'][$id])) {
          $sortedModules[$id] = $modules['primary'][$id];
        }
      }
      $modules['primary'] = $sortedModules;
    }
    //error_log('$modules(): '.print_r(array_keys($modules), true));
    return $modules;
  }
  
  protected function setNavigationModuleOrder($moduleIDs) {
    $lifespan = Kurogo::getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = implode(",", $moduleIDs);
    
    setcookie(MODULE_ORDER_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[MODULE_ORDER_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setNavigationHiddenModules($moduleIDs) {
    $lifespan = Kurogo::getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = count($moduleIDs) ? implode(",", $moduleIDs) : 'NONE';
    
    setcookie(DISABLED_MODULES_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[DISABLED_MODULES_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  //
  // Functions to add inline blocks of text
  // Call these from initializeForPage()
  //
  protected function addInlineCSS($inlineCSS) {
    $this->inlineCSSBlocks[] = $inlineCSS;
  }
  protected function addInternalCSS($path) {
    $this->cssURLs[] = '/min/g='.MIN_FILE_PREFIX.$path.$this->getMinifyArgString();
  }
  protected function addExternalCSS($url) {
    $this->cssURLs[] = $url;
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
  protected function addInternalJavascript($path) {
    $path = '/min/g='.MIN_FILE_PREFIX.$path.$this->getMinifyArgString();
    if (!in_array($path, $this->javascriptURLs)) {
        $this->javascriptURLs[] = $path;
    }
  }
  protected function addExternalJavascript($url) {
    if (!in_array($url, $this->javascriptURLs)) {
        $this->javascriptURLs[] = $url;
    }
  }
  
  public function exportCSSAndJavascript() {
    $memberArrays = array(
      'inlineCSSBlocks',
      'cssURLs',
      'inlineJavascriptBlocks',
      'inlineJavascriptFooterBlocks',
      'onOrientationChangeBlocks',
      'onLoadBlocks',
      'javascriptURLs',
    );
    $data = array();
    foreach ($memberArrays as $memberName) {
      $data[$memberName] = $this->$memberName;
    }

    // Add page Javascript and CSS if any
    $minifyURLs = $this->getMinifyUrls(true);
    
    $javascript = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['js'], '/'));
    if ($javascript) {
      array_unshift($data['inlineJavascriptBlocks'], $javascript);
    }

    $css = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['css'], '/'));
    if ($css) {
      array_unshift($data['inlineCSSBlocks'], $css);
    }
    
    return $data;
  }
  protected function importCSSAndJavascript($data) {
    foreach ($data as $memberName => $arrays) {
      $this->$memberName = array_unique(array_merge($this->$memberName, $arrays));
    }
  }
  protected function addJQuery($version='1.5.1') {
    $this->addInternalJavascript("/common/javascript/lib/jquery-{$version}.js");
  }

  protected function addJQueryUI($version='1.8.11') {
    $this->addJQuery();
    $this->addInternalJavascript("/common/javascript/lib/jquery-ui-{$version}.js");
  }
  
  //
  // Bookmarks 
  //
    
    protected function getBookmarkCookie() {
        return $this->configModule . 'bookmarks';
    }

    protected function getBookmarkLifespan() {
        return Kurogo::getOptionalSiteVar('BOOKMARK_COOKIE_LIFESPAN', 3600);
    }

    protected function hasBookmark($aBookmark) {
        return in_array($aBookmark, $this->getBookmarks());
    }

    private function bookmarkToggleURL($toggle) {
        $args = $this->args;
        $args['bookmark'] = $toggle;
        return $this->buildBreadcrumbURL($this->page, $args, false);
    }

    protected function setBookmarks($bookmarks) {
        $values = implode(BOOKMARK_COOKIE_DELIMITER, $bookmarks);
        $expireTime = time() + $this->getBookmarkLifespan();
        setcookie($this->getBookmarkCookie(), $values, $expireTime, COOKIE_PATH);
    }

    protected function addBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        if (!in_array($aBookmark, $bookmarks)) {
            $bookmarks[] = $aBookmark;
            $this->setBookmarks($bookmarks);
        }
    }

    protected function removeBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        $index = array_search($aBookmark, $bookmarks);
        if ($index !== false) {
            array_splice($bookmarks, $index, 1);
            $this->setBookmarks($bookmarks);
        }
    }
    
    protected function generateBookmarkOptions($cookieID) {
        $cookieID = urldecode($cookieID);
        $bookmarkCookie = $this->getBookmarkCookie();

        // compliant branch
        $this->addOnLoad("setBookmarkStates('{$bookmarkCookie}', '{$cookieID}')");
        $this->assign('cookieName', $bookmarkCookie);
        $this->assign('expireDate', $this->getBookmarkLifespan());
        $this->assign('bookmarkItem', $cookieID);

        // the rest of this is all touch and basic branch
        if ($bookmark = $this->getArg('bookmark')) {
            if ($bookmark == 'add') {
                $this->addBookmark($cookieID);
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $this->removeBookmark($cookieID);
                $status = 'off';
                $bookmarkAction = 'add';
            }

        } else {
            if ($this->hasBookmark($cookieID)) {
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $status = 'off';
                $bookmarkAction = 'add';
            }
        }

        $this->assign('bookmarkStatus', $status);
        $this->assign('bookmarkURL'   , $this->bookmarkToggleURL($bookmarkAction));
        $this->assign('bookmarkAction', $bookmarkAction);
    }  
    
    protected function getBookmarks() {
        $bookmarks = array();
        $bookmarkCookie = $this->getBookmarkCookie();
        if (isset($_COOKIE[$bookmarkCookie]) && strlen($_COOKIE[$bookmarkCookie])) {
            $bookmarks = explode(BOOKMARK_COOKIE_DELIMITER, $_COOKIE[$bookmarkCookie]);
        }
        return $bookmarks;
    }
    
    protected function generateBookmarkLink() {
        $hasBookmarks = count($this->getBookmarks()) > 0;
        $bookmarkLink = array();
        if ($hasBookmarks) {
            $bookmarkLink = array(array(
                'title' => $this->bookmarkLinkTitle,
                'url' => $this->buildBreadcrumbURL('bookmarks', $this->args, true),
                ));
            $this->assign('bookmarkLink', $bookmarkLink);
        }
        $this->assign('hasBookmarks', $hasBookmarks);
        return $bookmarkLink;
    }    
  
  //
  // Breadcrumbs
  //
  
  private function encodeBreadcrumbParam($breadcrumbs) {
    return urlencode(gzdeflate(json_encode($breadcrumbs), 9));
  }
  
  private function decodeBreadcrumbParam($breadcrumbs) {
    return json_decode(gzinflate(urldecode($breadcrumbs)), true);
  }
  
  private function loadBreadcrumbs() {
    $breadcrumbs = array();
  
    if ($breadcrumbArg = $this->getArg(MODULE_BREADCRUMB_PARAM)) {
      $breadcrumbs = $this->decodeBreadcrumbParam($breadcrumbArg);
      if (!is_array($breadcrumbs)) { $breadcrumbs = array(); }
    }

    if ($this->page != 'index') {
      // Make sure a module homepage is first in the breadcrumb list
      $addModuleHome = false;
      if (!count($breadcrumbs)) {
        $addModuleHome = true; // no breadrumbs
      } else {
        $firstBreadcrumb = reset($breadcrumbs);
        if ($firstBreadcrumb['p'] && $firstBreadcrumb['p'] != 'index') {
          $addModuleHome = true;
        }
      }
      if ($addModuleHome) {
        array_unshift($breadcrumbs, array(
          't'  => $this->moduleName,
          'lt' => $this->moduleName,
          'p'  => 'index',
          'a'  => '',        
        ));
      }
    }
          
    foreach ($breadcrumbs as $i => $b) {
      $breadcrumbs[$i]['title'] = $b['t'];
      $breadcrumbs[$i]['longTitle'] = $b['lt'];
          
      $breadcrumbs[$i]['url'] = ($b['p'] != 'index') ? $b['p'] : './';
      if (strlen($b['a'])) {
        $breadcrumbs[$i]['url'] .= "?{$b['a']}";
      }
      
      $linkCrumbs = array_slice($breadcrumbs, 0, $i);
      if (count($linkCrumbs)) { 
        $this->cleanBreadcrumbs($linkCrumbs);
        
        $crumbParam = http_build_query(array(
          MODULE_BREADCRUMB_PARAM => $this->encodeBreadcrumbParam($linkCrumbs),
        ));
        if (strlen($crumbParam)) {
          $breadcrumbs[$i]['url'] .= (strlen($b['a']) ? '&' : '?').$crumbParam;
        }
      }
    }
    
    $this->breadcrumbs = $breadcrumbs;  
    //error_log(__FUNCTION__."(): loaded breadcrumbs ".print_r($this->breadcrumbs, true));
  }
  
  private function cleanBreadcrumbs(&$breadcrumbs) {
    foreach ($breadcrumbs as $index => $breadcrumb) {
      unset($breadcrumbs[$index]['url']);
      unset($breadcrumbs[$index]['title']);
      unset($breadcrumbs[$index]['longTitle']);
    }
  }
  
  private function getBreadcrumbString($addBreadcrumb=true) {
    $breadcrumbs = $this->breadcrumbs;
    
    $this->cleanBreadcrumbs($breadcrumbs);
    
    if ($addBreadcrumb) {
      $args = $this->args;
      unset($args[MODULE_BREADCRUMB_PARAM]);
      
      $breadcrumbs[] = array(
        't'  => $this->breadcrumbTitle,
        'lt' => $this->breadcrumbLongTitle,
        'p'  => $this->page,
        'a'  => http_build_query($args),
      );
    }
    
    //error_log(__FUNCTION__."(): saving breadcrumbs for {$this->page} ".print_r($breadcrumbs, true));
    return $this->encodeBreadcrumbParam($breadcrumbs);
  }
  
  private function getBreadcrumbArgs($addBreadcrumb=true) {
    return array(
      MODULE_BREADCRUMB_PARAM => $this->getBreadcrumbString($addBreadcrumb),
    );
  }

  protected function buildBreadcrumbURL($page, $args, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURLForModule($this->configModule, $page, $args, $addBreadcrumb);
  }
  
  protected function buildBreadcrumbURLForModule($id, $page, $args, $addBreadcrumb=true) {
    return "/$id/$page?".http_build_query(array_merge($args, $this->getBreadcrumbArgs($addBreadcrumb)));
  }
  
  protected function getBreadcrumbArgString($prefix='?', $addBreadcrumb=true) {
    return $prefix.http_build_query($this->getBreadcrumbArgs($addBreadcrumb));
  }

  //
  // Page config
  //
  private function loadPageConfig() {
    if (!isset($this->pageConfig)) {
      $this->setPageTitle($this->moduleName);

      // Load site configuration and help text
      $this->loadSiteConfigFile('strings', false);
  
      // load module config file
      $pageData = $this->getPageData();

      if (isset($pageData[$this->page])) {
        $pageConfig = $pageData[$this->page];
        
        if (isset($pageConfig['pageTitle']) && strlen($pageConfig['pageTitle'])) {
          $this->pageTitle = $pageConfig['pageTitle'];
        }
          
        if (isset($pageConfig['breadcrumbTitle'])  && strlen($pageConfig['breadcrumbTitle'])) {
          $this->breadcrumbTitle = $pageConfig['breadcrumbTitle'];
        } else {
          $this->breadcrumbTitle = $this->pageTitle;
        }
          
        if (isset($pageConfig['breadcrumbLongTitle']) && strlen($pageConfig['breadcrumbLongTitle'])) {
          $this->breadcrumbLongTitle = $pageConfig['breadcrumbLongTitle'];
        } else {
          $this->breadcrumbLongTitle = $this->pageTitle;
        }     
        $this->pageConfig = $pageConfig;
      } else {
        $this->pageConfig = array();
      }
    }
  }
  
  protected function setTemplatePage($page, $moduleID=null) {
    $moduleID = is_null($moduleID) ? $this->id : $moduleID;
    $this->templatePage = $page;
    $this->templateModule = $moduleID;
  }
  
  
  // Programmatic overrides for titles generated from backend data
  protected function setPage($page) {
    $this->page = $page;
  }
  protected function getPageTitle() {
    return $this->pageTitle;
  }
  protected function setPageTitles($title) {
    $this->setPageTitle($title);
    $this->setBreadcrumbTitle($title);
    $this->setBreadcrumbLongTitle($title);
  }

  protected function setPageTitle($title) {
    $this->pageTitle = $title;
  }
  protected function getBreadcrumbTitle() {
    return $this->breadcrumbTitle;
  }
  protected function setBreadcrumbTitle($title) {
    $this->breadcrumbTitle = $title;
  }
  protected function getBreadcrumbLongTitle() {
    return $this->breadcrumbLongTitle;
  }
  protected function setBreadcrumbLongTitle($title) {
    $this->breadcrumbLongTitle = $title;
  }

  //
  // Module debugging
  //
  protected function addModuleDebugString($string) {
    $this->moduleDebugStrings[] = $string;
  }

  //
  // Config files
  //
  
    protected function getThemeVars() {
        $config = ConfigFile::factory('config', 'theme', ConfigFile::OPTION_CREATE_EMPTY);
        $sections = array(
            'common',
            $this->pagetype,
            $this->pagetype . '-' . $this->platform
        );
        
        $vars = array();
        foreach ($sections as $section) {
            if ($sectionVars = $config->getOptionalSection($section)) {
                $vars = array_merge($vars, $sectionVars);
            }
        }
        
        return $vars;
    }
  
  protected function getPageConfig($name, $opts=0) {
    $config = ModuleConfigFile::factory($this->configModule, "page-$name", $opts);
    Kurogo::siteConfig()->addConfig($config);
    return $config;
  }
  
  public function getPageData() {
     return $this->getModuleSections('pages');
  }
  
  protected function loadSiteConfigFile($name, $keyName=null, $opts=0) {
    $config = ConfigFile::factory($name, 'site', $opts);
    Kurogo::siteConfig()->addConfig($config);
    if ($keyName === null) { $keyName = $name; }

    return $this->loadConfigFile($config, $keyName);
  }

  protected function loadPageConfigFile($page, $keyName=null, $opts=0) {
    $config = $this->getPageConfig($page, $opts);
    if ($keyName === null) { $keyName = $name; }
    return $this->loadConfigFile($config, $keyName);
  }
  
  protected function loadConfigFile(Config $config, $keyName=null) {
    $this->loadTemplateEngineIfNeeded();

    $themeVars = $config->getSectionVars(Config::EXPAND_VALUE);
    
    if ($keyName === false) {
      foreach($themeVars as $key => $value) {
        $this->templateEngine->assign($key, $value);
      }
    } else {
      $this->templateEngine->assign($keyName, $themeVars);
    }
    
    return $themeVars;
  }
  
  protected function showLogin() {
    return $this->getOptionalModuleVar('SHOW_LOGIN', false);
  }
  
  //
  // Convenience functions
  //
  public function assignByRef($var, &$value) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assignByRef($var, $value);
  }
  
  public function assign($var, $value=null) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assign($var, $value);
  }
  
  public function getTemplateVars($key) {
    $this->loadTemplateEngineIfNeeded();
    
    return $this->templateEngine->getTemplateVars($key);
  }
  
    public function setRefresh($time) {
    
        if (!$time || ($this->refreshTime && $time > $this->refreshTime)) {
            return;
        }

        $this->refreshTime = $time;
        $this->assign('refreshPage', $this->refreshTime);
    }
  
  private function setPageVariables() {
    $this->loadTemplateEngineIfNeeded();
        
    $this->loadPageConfig();
    
    // Set variables common to all modules
    $this->assign('moduleID',     $this->id);
    $this->assign('configModule',  $this->configModule);
    $this->assign('templateModule', $this->templateModule);
    $this->assign('moduleName',   $this->moduleName);
    $this->assign('page',         $this->page);
    $this->assign('isModuleHome', $this->page == 'index');
    $this->assign('request_uri' , $_SERVER['REQUEST_URI']);
    
    // Font size for template
    $this->assign('fontsizes',    $this->fontsizes);
    $this->assign('fontsize',     $this->fontsize);
    $this->assign('fontsizeCSS',  $this->getFontSizeCSS());
    $this->assign('fontSizeURLs', $this->getFontSizeURLs());

    // Minify URLs
    $this->assign('minify', $this->getMinifyUrls());
    
    // Google Analytics. This probably needs to be moved
    if ($gaID = Kurogo::getOptionalSiteVar('GOOGLE_ANALYTICS_ID')) {
        $this->assign('GOOGLE_ANALYTICS_ID', $gaID);
        $this->assign('gaImageURL', $this->googleAnalyticsGetImageUrl($gaID));
    }
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Tablet module nav list
    if ($this->pagetype == 'tablet' && $this->page != 'pane') {
      $this->addInternalJavascript('/common/javascript/lib/iscroll-4.0.js');
      $this->assign('moduleNavList', $this->getModuleNavlist());
    }
            
    // Set variables for each page
    $this->initializeForPage();

    $this->assign('pageTitle', $this->pageTitle);

    // Variables which may have been modified by the module subclass
    $this->assign('inlineCSSBlocks',              $this->inlineCSSBlocks);
    $this->assign('cssURLs',                      $this->cssURLs);
    $this->assign('inlineJavascriptBlocks',       $this->inlineJavascriptBlocks);
    $this->assign('onOrientationChangeBlocks',    $this->onOrientationChangeBlocks);
    $this->assign('onLoadBlocks',                 $this->onLoadBlocks);
    $this->assign('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);
    $this->assign('javascriptURLs',               $this->javascriptURLs);
    
    $this->assign('breadcrumbs',            $this->breadcrumbs);
    $this->assign('breadcrumbArgs',         $this->getBreadcrumbArgs());
    $this->assign('breadcrumbSamePageArgs', $this->getBreadcrumbArgs(false));

    $this->assign('moduleDebugStrings',     $this->moduleDebugStrings);
    
    $moduleStrings = $this->getOptionalModuleSection('strings');
    $this->assign('moduleStrings', $moduleStrings);

    // Module Help
    if ($this->page == 'help') {
      $this->assign('hasHelp', false);
      $template = 'common/templates/'.$this->page;
    } else {
      $this->assign('hasHelp', isset($moduleStrings['help']));
      $template = 'modules/'.$this->templateModule.'/templates/'.$this->templatePage;
    }
    
    // Pager support
    if (isset($this->htmlPager)) {
      $this->assign('pager', $this->getPager());
    }
    
    // Tab support
    if (isset($this->tabbedView)) {
      $this->assign('tabbedView', $this->tabbedView);
    }
    
    $this->assign('imageExt', $this->imageExt);
    $this->assign($this->getThemeVars());
    
    // Access Key Start
    $accessKeyStart = count($this->breadcrumbs);
    if ($this->configModule != 'home') {
      $accessKeyStart++;  // Home link
    }
    $this->assign('accessKeyStart', $accessKeyStart);

    if (Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
        includePackage('Authentication');
        $this->setCacheMaxAge(0);
        $session = $this->getSession();
        $this->assign('session', $session);
        $this->assign('session_isLoggedIn', $this->isLoggedIn());
        $this->assign('showLogin', Kurogo::getSiteVar('AUTHENTICATION_ENABLED') && $this->showLogin());
        if ($this->isLoggedIn()) {
            $user = $session->getUser();
            $authority = $user->getAuthenticationAuthority();
            $this->assign('session_authority_class', $authority->getAuthorityClass());
            $this->assign('session_authority_title', $authority->getAuthorityTitle());
            $this->assign('session_userID', $user->getUserID());
            $this->assign('session_fullName', $user->getFullname());
            if (count($session->getUsers())==1) {
                $this->assign('session_logout_url', $this->buildURLForModule('login', 'logout', array('authority'=>$user->getAuthenticationAuthorityIndex())));
                $this->assign('session_multiple_logins', false);
            } else {
                $this->assign('session_multiple_logins', true);
                $this->assign('session_logout_url', $this->buildURLForModule('login', 'logout', array()));
            }

            if ($session_max_idle = intval(Kurogo::getOptionalSiteVar('AUTHENTICATION_IDLE_TIMEOUT', 0))) {
                $this->setRefresh($session_max_idle+2);
            }
        }
    }

    /* set cache age. Modules that present content that rarely changes can set this value
    to something higher */
    header(sprintf("Cache-Control: max-age=%d", $this->cacheMaxAge));
    header("Expires: " . gmdate('D, d M Y H:i:s', time() + $this->cacheMaxAge) . ' GMT');
    
    return $template;
  }

  //
  // Display page
  //
  public function displayPage() {
    $template = $this->setPageVariables();
    
    // Load template for page
    $this->templateEngine->displayForDevice($template);    
  }
  
  //
  // Fetch page
  //
  public function fetchPage() {
    $template = $this->setPageVariables();
    
    // return template for page in variable
    return $this->templateEngine->fetchForDevice($template);    
  }
  
  protected function setCacheMaxAge($age) {
    $this->cacheMaxAge = intval($age);
  }
  
  
  //
  // Subclass this function to set up variables for each template page
  //
  abstract protected function initializeForPage();
  
  //
  // Subclass these functions for federated search support
  // Return 2 items and a link to get more
  //
  public function federatedSearch($searchTerms, $maxCount, &$results) {
    return 0;
  }
  
  protected function urlForFederatedSearch($searchTerms) {
    return $this->buildBreadcrumbURL('search', array(
      'filter' => $searchTerms,
    ), false);
  }
}
