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

if (!function_exists('gzdeflate')) {
    die("Kurogo requires the zlib PHP extension.");
}

abstract class WebModule extends Module {

    const INCLUDE_DISABLED_MODULES=true;
    const EXCLUDE_DISABLED_MODULES=false;
      
  protected $page = 'index';

  protected $templateModule = 'none'; 
  protected $templatePage = 'index';

    protected $deviceClassifier;  
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
    
  private $templateEngine = null;
  
  private $htmlPager = null;
  private $inPagedMode = true;
  
  private $tabbedView = null;
  
  protected $refreshTime = 0;
  protected $cacheMaxAge = 0;
  
  protected $autoPhoneNumberDetection = true;
  protected $canBeAddedToHomeScreen = true;
  protected $hideFooterLinks = false;
  
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
      // From http://code.google.com/mobile/analytics/docs/web/
      // "Reminder: Change the prefix on your Analytics web property ID from 
      // UA- to MO- in the server-side snippets given below. For example, if 
      // your web property ID is UA-12345-67, you would use MO-12345-67 in your 
      // server-side snippets."
      $gaID = str_replace('UA-', 'MO-', $gaID);
      
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
  // Percent Mobile Analytics
  //
  private function percentMobileAnalyticsGetImageUrl($pmID){
      if (isset($pmID) && strlen($pmID)){
       $url = "http://tracking.percentmobile.com/pixel/" .
          $pmID .
          "/pixel.gif?v=271009_js";
       
       return $url;
      }
      else {
          return "";
      }
  }

  //
  // Lazy load
  //
  private function loadTemplateEngineIfNeeded() {
    if (!isset($this->templateEngine)) {
      Kurogo::log(LOG_DEBUG, "Initializing template engine", 'module');
      $this->templateEngine = new TemplateEngine($this->id);
      $this->templateEngine->registerPlugin('function', 'drawChart', array($this, 'drawChart'));
      $this->templateEngine->registerPlugin('modifier','getLocalizedString', array($this,'getLocalizedString'));
    }
  }
  
  public function drawChart($params) {
    static $chartDrawn;
    $result = '';
    if (!$chartDrawn) {
        $result .= '<style type="text/css">@import url("' . $this->getInternalCSSURL('/common/css/chart.css') . '");</style>';
        $result .= '<script type="text/javascript" src="' . $this->getInternalJavascriptURL('/common/javascript/chart.js') . '"></script>';
        $chartDrawn = true;
    }
    $result .= KurogoChart::drawChart($params);
    return $result;
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
    Kurogo::log(LOG_DEBUG, "Redirecting to module $id at $url",'module');
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
    Kurogo::log(LOG_DEBUG, "Redirecting to page $page at $url",'module');
    header("Location: ". URL_PREFIX . ltrim($url, '/'));
    exit;
  }

    protected function buildURLFromArray($params) {
        $id = isset($params['id']) ? $params['id'] : '';
        $page = isset($params['page']) ? $params['page'] : '';
        $args = isset($params['args']) ? $params['args'] : array();
        
        if ($id) {
            return self::buildURLForModule($id, $page, $args);
        } elseif ($page) {
            return self::buildURL($page, $args);
        }
        
        return false;
    }
  
    public function getArrayForRequest() {
        $params = array(
            'id'=>$this->configModule,
            'page'=>$this->page,
            'args'=>$this->args
        );
        
        return $params;
    }
    
  protected function unauthorizedAccess() {
        if ($this->isLoggedIn()) {  
            $this->redirectToModule('error', '', array_merge($this->getArrayForRequest(), array('code'=>'protected')));
        } else {
            $this->redirectToModule('login', '', $this->getArrayForRequest());
        }
  }
  
    /* This method would be called by other modules to get a valid link from a model object */
    public function linkForItem(KurogoObject $object, $options=null) {
       throw new KurogoException("linkForItem must be subclassed if it is going to be used");
    }

    /* default implmentation. Subclasses may wish to override this */
    public function linkForValue($value, Module $callingModule, KurogoObject $otherValue=null) {
        return array(
            'title'=>$value, 
            'url'  =>$this->buildBreadcrumbURL(
                'search', 
                array('filter'=>$value),
                false
            )
        );
    }
  
    //
    // Factory function
    // instantiates objects for the different modules
    //
    public static function factory($id, $page='', $args=array(), $initialize=true) {
  
        if (!$module = parent::factory($id, 'web')) {
            return false;
        }
        
        $module->init($page, $args);

        if ($initialize) {
            $module->initialize();
        }

        return $module;
    }
    
    protected function getPageType() {
        $this->loadDeviceClassifierIfNeeded();
        return $this->deviceClassifier->getPageType();
    }

    protected function getPlatform() {
        $this->loadDeviceClassifierIfNeeded();
        return $this->deviceClassifier->getPlatform();
    }

    protected function getSupportsCerts() {
        $this->loadDeviceClassifierIfNeeded();
        return $this->deviceClassifier->getSupportsCerts();
    }
    
    protected function loadDeviceClassifierIfNeeded() {
        $this->deviceClassifier = Kurogo::deviceClassifier();
    }
        
    protected function init($page='', $args=array()) {
      
        $this->setArgs($args);
        //Don't call parent if we don't have a page. This is a work around.
        if ($page) {
            $this->setPage($page);
            parent::init();
        }

        $this->moduleName    = $this->getModuleVar('title','module');

        $this->pagetype      = $this->getPagetype();
        $this->platform      = $this->getPlatform();
        $this->supportsCerts = $this->getSupportsCerts();

        switch ($this->getPagetype()) {
            case 'compliant':
                $this->imageExt = '.png';
                break;
            
          case 'touch':
          case 'basic':
                $this->imageExt = '.gif';
                break;
        }
        
        if ($page) {
            // Pull in fontsize
            if (isset($args['font'])) {
                $this->fontsize = $args['font'];
                setcookie('fontsize', $this->fontsize, time() + Kurogo::getSiteVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
        
            } else if (isset($_COOKIE['fontsize'])) { 
              $this->fontsize = $_COOKIE['fontsize'];
            }
            
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
        
  protected function moduleDisabled() {
    $this->redirectToModule('error', '', array_merge($this->getArrayForRequest(), array('code'=>'disabled')));
  }
  
    public static function getAllThemes() {
        $themes = array();
        $d = dir(SITE_DIR . "/themes");
        while (false !== ($entry = $d->read())) {
            if ($entry[0]!='.' && is_dir(SITE_DIR . "/themes/$entry")) {
                
                $configFile = SITE_DIR . "/themes/$entry/config.ini";
                try {
                    $config = ConfigFile::factory($configFile, 'file');
                    $themes[$entry] = $config->getOptionalVar('theme_name', $entry, 'general');
                    
                } catch (KurogoException $e) {
                }
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
        Kurogo::log(LOG_DEBUG, "Redirecting to secure url $redirect",'module');
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
  
  public static function getAllModules() {
  	$configFiles = glob(SITE_CONFIG_DIR . "/*/module.ini");
    $modules = array();

  	foreach ($configFiles as $file) {
  		if (preg_match("#" . preg_quote(SITE_CONFIG_DIR,"#") . "/([^/]+)/module.ini$#", $file, $bits)) {
  			$id = $bits[1];
			try {
				if ($module = WebModule::factory($id)) {
				   $modules[$id] = $module;
				}
			} catch (KurogoException $e) {
			}
  		}
  	}
    ksort($modules);    
    return $modules;        
  }

    protected function elapsedTime($timestamp, $date_format='%b %e, %Y @ %l:%M %p') {
        $now = time();
        $diff = $now - $timestamp;
        $today = mktime(0,0,0);
        $today_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
    
        if ($diff > 0) {
            if ($today - $today_timestamp > 86400) {
                return sprintf("%d days ago", $diff/86400);
            } elseif ($today - $today_timestamp > 0) {
                return strftime('Yesterday @ %l:%M %p', $timestamp);
            } elseif ($diff > 3600) {
                return sprintf("%d hour%s ago", $diff/3600, intval($diff/3600)>1?'s':'');
            } elseif ($diff > 60) {
                return sprintf("%d minute%s ago", $diff/60, intval($diff/60)>1?'s':'');
            } else {
                return sprintf("%d second%s ago", $diff, $diff>1 ?'s':'');
            }
        
        } else {
            return strftime($date_format, $timestamp);
        }    
    }

  //
  // Module list control functions
  //

  protected function getModuleNavlist() {
    $navModules = $this->getAllModuleNavigationData(self::EXCLUDE_DISABLED_MODULES);

    if (count($navModules['primary']) && count($navModules['secondary'])) {
      $separator = array('separator' => array('separator' => true));
    } else {
      $separator = array();
    }
        
    return array_merge($navModules['home'], $navModules['primary'], $separator, $navModules['secondary']);
  }
  
    protected function isOnHomeScreen() {
        $navModules = $this->getAllModuleNavigationData(self::INCLUDE_DISABLED_MODULES);
        $allModules = array_merge(array_keys($navModules['primary']), array_keys($navModules['secondary']));
        return in_array($this->configModule, $allModules);    
    }
  
    protected function getUserDisabledModuleIDs() {
    
        $disabledIDs = array();
        if (isset($_COOKIE[DISABLED_MODULES_COOKIE]) && $_COOKIE[DISABLED_MODULES_COOKIE] != "NONE") {
            $disabledIDs = explode(",", $_COOKIE[DISABLED_MODULES_COOKIE]);
        }
        
        return $disabledIDs;
    }

    /* retained for compatibility */
    protected function getNavigationModules($includeDisabled=self::INCLUDE_DISABLED_MODULES) {
        return $this->getAllModuleNavigationData($includeDisabled);
    }

    private function getModuleNavigationIDs($includeDisabled=self::INCLUDE_DISABLED_MODULES) {
        $moduleNavConfig = $this->getModuleNavigationConfig();
        
        $disabledIDs = $this->getUserDisabledModuleIDs();
        $disabledModules = $includeDisabled || !$disabledIDs ? array() : array_combine($disabledIDs, $disabledIDs);

        $modules = array(
            'home'     => $this->pagetype == 'tablet' ? array('home'=>'Home') : array(),
            'primary'  => array_diff_key($moduleNavConfig->getOptionalSection('primary_modules'), $disabledModules),
            'secondary'=> array_diff_key($moduleNavConfig->getOptionalSection('secondary_modules'), $disabledModules)
        );

        return $modules;
    }

    /* This method can be overridden to provide dynamic navigation data. It will only be used if DYNAMIC_MODULE_NAV_DATA = 1 */
    protected function getModuleNavigationData($moduleNavData) {
        return $moduleNavData;
    }
    
    protected function getAllModuleNavigationData($includeDisabled=self::INCLUDE_DISABLED_MODULES) {
    
        $moduleConfig = $this->getModuleNavigationIDs($includeDisabled);
    
        $modules = array(
            'home'    => array(),
            'primary' => array(),
            'secondary' => array()
        );
        
        $disabledIDs = $this->getUserDisabledModuleIDs();
        
        foreach ($moduleConfig as $type => $modulesOfType) {

            foreach ($modulesOfType as $moduleID => $title) {
                $shortTitle = $title;
                $moduleConfig = ModuleConfigFile::factory($moduleID, 'module');
                if ($moduleConfig) {
                    $shortTitle = $moduleConfig->getOptionalVar('shortTitle', $title);
                }
            
                $selected = $this->configModule == $moduleID;
                $primary = $type == 'primary';
      
                $classes = array();
                if ($selected) { $classes[] = 'selected'; }
                if (!$primary) { $classes[] = 'utility'; }
        
                $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '-selected' : '';

                //this is fixed for now
                $modulesThatCannotBeDisabled = array('customize');
    
                $moduleNavData = array(
                    'type'        => $type,
                    'selected'    => $selected,
                    'title'       => $title,
                    'shortTitle'  => $shortTitle,
                    'url'         => "/$moduleID/",
                    'disableable' => !in_array($moduleID, $modulesThatCannotBeDisabled),
                    'disabled'    => $includeDisabled && in_array($moduleID, $disabledIDs),
                    'img'         => "/modules/home/images/{$moduleID}{$imgSuffix}".$this->imageExt,
                    'class'       => implode(' ', $classes),
                );

                if (Kurogo::getOptionalSiteVar('DYNAMIC_MODULE_NAV_DATA', false)) {
                    $module = WebModule::factory($moduleID, false, array(), false); // do not initialize
                    
                    if ($moduleNavData = $module->getModuleNavigationData($moduleNavData)) {
                        $modules[$moduleNavData['type']][$moduleID] = $moduleNavData;
                    }
                } else {
                    $modules[$type][$moduleID] = $moduleNavData;
                }
                
          }
        }
        
        $modules = $this->getUserSortedModules($modules);
        //error_log('$modules(): '.print_r(array_keys($modules), true));
        return $modules;
    }
  
  protected function getUserSortedModules($modules) {
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
  protected function getInternalCSSURL($path) {
    $path = '/min/g='.MIN_FILE_PREFIX.$path.$this->getMinifyArgString();
    return $path;
  }
  protected function addInternalCSS($path) {
    Kurogo::log(LOG_DEBUG, "Adding internal css $path", 'module');
    $this->cssURLs[] = $this->getInternalCSSURL($path);
  }
  protected function addExternalCSS($url) {
    Kurogo::log(LOG_DEBUG, "Adding external css $url", 'module');
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
  protected function getInternalJavascriptURL($path) {
    $path = '/min/?g='.MIN_FILE_PREFIX.$path.$this->getMinifyArgString();
    return $path;
  }
  protected function addInternalJavascript($path) {
    Kurogo::log(LOG_DEBUG, "Adding internal javascript $path", 'module');
    $path = $this->getInternalJavascriptURL($path);
    if (!in_array($path, $this->javascriptURLs)) {
        $this->javascriptURLs[] = $path;
    }
  }
  protected function addExternalJavascript($url) {
    Kurogo::log(LOG_DEBUG, "Adding external javascript $url", 'module');
    if (!in_array($url, $this->javascriptURLs)) {
        $this->javascriptURLs[] = $url;
    }
  }
  
  public function exportCSSAndJavascript() {
    $minifyURLs = $this->getMinifyUrls(true);

    $cache = new DiskCache(CACHE_DIR.'/minify', Kurogo::getOptionalSiteVar('MINIFY_CACHE_TIMEOUT', 30), true);
    $cacheName = "export_{$this->configModule}-{$this->page}-{$this->pagetype}-{$this->platform}-".
        md5($minifyURLs['js'].$minifyURLs['css']);

    if ($cache->isFresh($cacheName)) {
      $data = $cache->read($cacheName);
      
    } else {
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
      $context = stream_context_create(array(
        'http' => array(
          'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ),
      ));
      
      $javascript = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['js'], '/'), false, $context);
      if ($javascript) {
        array_unshift($data['inlineJavascriptBlocks'], $javascript);
      }
  
      $css = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['css'], '/'), false, $context);
      if ($css) {
        array_unshift($data['inlineCSSBlocks'], $css);
      }
      
      $cache->write($data, $cacheName);
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
        $bookmarkLink = array(array(
            'title' => $this->getLocalizedString('BOOKMARK_TITLE'),
            'url' => $this->buildBreadcrumbURL('bookmarks', $this->args, true),
        ));
        $this->assign('bookmarkLink', $bookmarkLink);
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
    if ($json = @gzinflate(urldecode($breadcrumbs))) {
        return json_decode($json, true);
    }

    return null;
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
        Kurogo::log(LOG_DEBUG, "Loading page configuration for $this->configModule - $this->page", 'module');
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
    Kurogo::log(LOG_INFO, "Setting page to $page", 'module');
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
  
    protected function getThemeVar($key) {
        $vars = $this->getThemeVars();
        if (!isset($vars[$key])) {
            throw new KurogoConfigurationException("Config variable '$key' not set");
        }
        
        return $vars[$key];
    }

    protected function getOptionalThemeVar($var, $default='') {
        $vars = $this->getThemeVars();
        return isset($vars[$var]) ? $vars[$var] : $default;
    }
    
    protected function getThemeVars() {
        static $vars = array();
        if ($vars) {
            return $vars;
        }
        
        $config = ConfigFile::factory('config', 'theme', ConfigFile::OPTION_CREATE_EMPTY);
        $sections = array(
            'common',
            $this->pagetype,
            $this->pagetype . '-' . $this->platform
        );
        
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
    
    private function assignLocalizedStrings() {
        $this->assign('footerKurogo', $this->getLocalizedString('FOOTER_KUROGO'));
        $this->assign('footerBackToTop', $this->getLocalizedString('FOOTER_BACK_TO_TOP'));
        $this->assign('homeLinkText', $this->getLocalizedString('HOME_LINK', Kurogo::getSiteString('SITE_NAME')));
        $this->assign('moduleHomeLinkText', $this->getLocalizedString('HOME_LINK', $this->getModuleName()));
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
    $this->assign('hideFooterLinks' , $this->hideFooterLinks);
    
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
        $this->assign('GOOGLE_ANALYTICS_DOMAIN', Kurogo::getOptionalSiteVar('GOOGLE_ANALYTICS_DOMAIN'));
        $this->assign('gaImageURL', $this->googleAnalyticsGetImageUrl($gaID));
    }

    // Percent Mobile Analytics
    if ($pmID = Kurogo::getOptionalSiteVar('PERCENT_MOBILE_ID')){
        $this->assign('PERCENT_MOBILE_ID', $pmID);
        
        $pmBASEURL = "http://assets.percentmobile.com/percent_mobile.js";
        $this->assign('PERCENT_MOBILE_URL', $pmBASEURL);
        
        //$this->assign('pmImageURLJS', $this->percentMobileAnalyticsGetImageUrlJS($pmID));
        $this->assign('pmImageURL', $this->percentMobileAnalyticsGetImageUrl($pmID));
    }
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Tablet module nav list
    if ($this->pagetype == 'tablet' && $this->page != 'pane') {
      $this->addInternalJavascript('/common/javascript/lib/iscroll-4.0.js');
      $this->assign('moduleNavList', $this->getModuleNavlist());
    }
            
    Kurogo::log(LOG_DEBUG,"Calling initializeForPage for $this->configModule - $this->page", 'module');
    $this->initializeForPage(); //subclass behavior
    Kurogo::log(LOG_DEBUG,"Returned from initializeForPage for $this->configModule - $this->page", 'module');

    // Set variables for each page
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
    $this->assign('homeLink', $this->buildURLForModule('home','',array()));
    
    $this->assignLocalizedStrings();

    // Module Help
    if ($this->page == 'help') {
      $this->assign('hasHelp', false);
      $template = 'common/templates/'.$this->page;
    } else {
      $this->assign('hasHelp', isset($moduleStrings['help']));
      $this->assign('helpLink', $this->buildBreadcrumbURL('help',array()));
      $this->assign('helpLinkText', $this->getLocalizedString('HELP_TEXT', $this->getModuleName()));
      $template = 'modules/'.$this->templateModule.'/templates/'.$this->templatePage;
    }
    Kurogo::log(LOG_DEBUG,"Template file is $template", 'module');
    
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
        Kurogo::includePackage('Authentication');
        $this->setCacheMaxAge(0);
        $session = $this->getSession();
        $this->assign('session', $session);
        $this->assign('session_isLoggedIn', $this->isLoggedIn());
        $this->assign('showLogin', Kurogo::getSiteVar('AUTHENTICATION_ENABLED') && $this->showLogin());
        if ($this->isLoggedIn()) {
            $user = $session->getUser();
            $authority = $user->getAuthenticationAuthority();
            $this->assign('session_userID', $user->getUserID());
            $this->assign('session_fullName', $user->getFullname());
            if (count($session->getUsers())==1) {
                $this->assign('session_logout_url', $this->buildURLForModule('login', 'logout', array('authority'=>$user->getAuthenticationAuthorityIndex())));
                $this->assign('footerLoginLink', $this->buildURLForModule('login', '', array()));
                $this->assign('footerLoginText', $this->getLocalizedString('SIGNED_IN_SINGLE', $authority->getAuthorityTitle(), $user->getFullName()));
                $this->assign('footerLoginClass', $authority->getAuthorityClass());
            } else {
                $this->assign('footerLoginClass', 'login_multiple');
                $this->assign('session_logout_url', $this->buildURLForModule('login', 'logout', array()));
                $this->assign('footerLoginLink', $this->buildURLForModule('login', 'logout', array()));
                $this->assign('footerLoginText', $this->getLocalizedString('SIGNED_IN_MULTIPLE'));
            }

            if ($session_max_idle = intval(Kurogo::getOptionalSiteVar('AUTHENTICATION_IDLE_TIMEOUT', 0))) {
                $this->setRefresh($session_max_idle+2);
            }
        } else {
            $this->assign('footerLoginClass', 'noauth');
            $this->assign('footerLoginLink', $this->buildURLForModule('login','', array()));
            $this->assign('footerLoginText', $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')));
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
    $output = $this->templateEngine->fetchForDevice($template);

	// log this request
	$this->logView(strlen($output));
	echo $output;
	exit();
  }
  
  protected function logView($size=null) {
    if ($this->logView) {
        KurogoStats::logView('web', $this->configModule, $this->page, $this->logData, $this->logDataLabel, $size);
    }
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
    // Subclass this function and return an array of items for a given search term and feed
    //
    public function searchItems($searchTerms, $limit=null, $options=null) {  
        return array();
    }
  
    //
    // Subclass these functions for federated search support
    // Return 2 items and a link to get more
    //
  
    public function federatedSearch($searchTerms, $maxCount, &$results) {
        $total = 0;
        $results = array();
      
        // Ask for one more item than we show so that we can tell if we need to display
        // the more results link.  This will need to be changed to 0 if we ever
        // want to show the full number of matched items in the federated search screen
        $items = $this->searchItems($searchTerms, $maxCount+1, array('federatedSearch'=>true));
        $limit = is_array($items) ? min($maxCount, count($items)) : 0;

        for ($i = 0; $i < $limit; $i++) {
            $results[] = $this->linkforItem($items[$i], array('federatedSearch'=>true, 'filter'=>$searchTerms));
        }
        
        return count($items);
    }
  
    protected function urlForFederatedSearch($searchTerms) {
        return $this->buildBreadcrumbURL('search', array(
          'filter' => $searchTerms,
        ), false);
    }
}
