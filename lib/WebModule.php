<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Module
  */

/**
  * Breadcrumb Parameter
  */

if (!function_exists('gzdeflate')) {
    die("Kurogo requires the zlib PHP extension. http://www.php.net/manual/en/book.zlib.php");
}

abstract class WebModule extends Module {

    const INCLUDE_HIDDEN_MODULES=true;
    const EXCLUDE_HIDDEN_MODULES=false;
    
    const AJAX_PARAMETER = 'ajax';
    
    const BREADCRUMB_PARAM = '_b';
    const AJAX_BREADCRUMB_TITLE = '_abt';
    const AJAX_BREADCRUMB_LONG_TITLE = '_ablt';
    const AJAX_BREADCRUMB_CONTAINER_PAGE = '_acp';
    const AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS = '_acpa';

    const MODULE_NAV_COOKIE = 'moduleNav';
    const TAB_COOKIE_PREFIX = 'moduletab_';
    const BOOKMARK_COOKIE_DELIMITER = '@@';
    
    const WEB_BRIDGE_BUILD_TEMPLATES_PAGE = '__nativeWebTemplates';
      
  protected $page = 'index';

  protected $templateModule = 'none'; 
  protected $templatePage = 'index';

  protected $deviceClassifier;  

  protected $pagetype = 'unknown';
  protected $platform = 'unknown';
  protected $browser = 'unknown';

  protected $ajaxContentLoad = false;
  protected $ajaxContainerPage = '';
  protected $ajaxContainerPageArgs = '';
  
  protected $hasWebBridgePageRefresh = false;
  protected $hasWebBridgeAutoRefresh = false;
  
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
  private $onLoadBlocks = array('scrollToTop();');
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
  protected $canBeRemoved = true;
  protected $canBeDisabled = true;
  protected $canBeHidden = true;
  protected $canAllowRobots = true;
  protected $defaultAllowRobots = true;
  protected $hideFooterLinks = false;
  protected $includeCommonCSS = true;  
  protected $includeCommonJS = true;
  
    //
    // Tabbed View support
    //
    
    protected function tabCookieForPage() {
        $cookieArgs = $this->args;
        unset($cookieArgs[self::BREADCRUMB_PARAM]);
        unset($cookieArgs[self::AJAX_BREADCRUMB_TITLE]);
        unset($cookieArgs[self::AJAX_BREADCRUMB_LONG_TITLE]);
        unset($cookieArgs[self::AJAX_BREADCRUMB_CONTAINER_PAGE]);
        unset($cookieArgs[self::AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS]);
        
        return self::TAB_COOKIE_PREFIX."{$this->configModule}_{$this->page}_".md5(http_build_query($cookieArgs));
    }
  
    protected function getCurrentTab($tabKeys, $defaultTab=null) {
        $currentTab = null;
        
        $tabCookie = $this->tabCookieForPage();
        
        if (isset($this->args['tab']) && in_array($this->args['tab'], $tabKeys)) {
            $currentTab = $this->args['tab']; // argument set
        
        } else if (isset($_COOKIE[$tabCookie]) && in_array($_COOKIE[$tabCookie], $tabKeys)) {
            $currentTab = $_COOKIE[$tabCookie]; // cookie set
        
        } else {
            foreach ($this->pageConfig as $key => $value) {
                if (strpos($key, 'tab_') === 0) {
                    $tabKey = substr($key, 4);
                    if (in_array($tabKey, $tabKeys)) {
                        $currentTab = $tabKey; // page config tab order set
                        break;
                    }
                }
            }
        
            if (!isset($currentTab)) {
                if (isset($defaultTab) && $defaultTab) {
                    $currentTab = $defaultTab;
                } else {
                    // still haven't found it, fall back on tabKey order
                    $currentTab = reset($tabKeys);
                }
            }
        }
        
        return $currentTab;
    }
  
    protected function enableTabs($tabKeys, $defaultTab=null, $javascripts=array(), $classes=array()) {
        // prefill from config to get order
        $tabs = array();
        foreach ($this->pageConfig as $key => $value) {
            if (strpos($key, 'tab_') === 0) {
                $tabKey = substr($key, 4);
                if (in_array($tabKey, $tabKeys)) {
                    $tabs[$tabKey] = array(
                        'title' => $value,
                    );
                }
            }
        }
        
        // Fill out rest of tabs
        foreach ($tabKeys as $tabKey) {
            // Fill out default titles for tabs not in config:
            if (!isset($tabs[$tabKey]) || !is_array($tabs[$tabKey])) {
                $tabs[$tabKey] = array(
                    'title' => ucwords($tabKey),
                );
            }
        
            $tabArgs = $this->args;
            $tabArgs['tab'] = $tabKey;
            $tabs[$tabKey]['url'] = $this->buildBreadcrumbURL($this->page, $tabArgs, false);
            $tabs[$tabKey]['id'] = "{$tabKey}-".md5($tabs[$tabKey]['url']);
            $tabs[$tabKey]['javascript'] = isset($javascripts[$tabKey]) ? $javascripts[$tabKey] : '';
            $tabs[$tabKey]['class'] = isset($classes[$tabKey]) ? $classes[$tabKey] : '';
        }
        
        $currentTab = $this->getCurrentTab($tabKeys, $defaultTab);
        $tabCookie = $this->tabCookieForPage();
        
        $this->tabbedView = array(
            'tabs'       => $tabs,
            'current'    => $currentTab,
            'tabCookie'  => $tabCookie,
        );
        
        $currentJS = $tabs[$currentTab]['javascript'];
        $this->addInlineJavascriptFooter("(function(){ var tabKey = '{$currentTab}';var tabId = '{$tabs[$currentTab]['id']}';var tabCookie = '{$tabCookie}';showTab(tabId);{$currentJS} })();");
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
  
  protected function getAccess() {
    if (!$access = parent::getAccess()) {
        if (KurogoWebBridge::shouldIgnoreAuth()) {
            $access = true;
        }
    }
    return $access;
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
  private function getMinifyArgString($pageOnly=false, $noCommon=false) {
    $minifyArgs = array();
    if (Kurogo::getSiteVar('MINIFY_DEBUG')) {
      $minifyArgs['debug'] = 1;
    }
    if ($pageOnly) {
      $minifyArgs['pageOnly'] = 'true';
    }
    if ($noCommon) {
      $minifyArgs['noCommon'] = 'true';
    }

    
    $minifyArgs['config'] = $this->configModule;
    $minifyArgString = http_build_query($minifyArgs);
    
    return ($minifyArgString ? "&$minifyArgString" : '');
  }
  
  private function getMinifyUrls($pageOnly=false) {
    $page = preg_replace('/[\s-]+/', '+', $this->page);
    $minKey = "{$this->id}-{$page}-{$this->pagetype}-{$this->platform}-{$this->browser}-".md5(THEME_DIR);
    
    return array(
      'css' => "/min/g=css-$minKey".$this->getMinifyArgString($pageOnly, !$this->includeCommonCSS()),
      'js'  => "/min/g=js-$minKey".$this->getMinifyArgString($pageOnly, !$this->includeCommonJS()),
    );
  }
  
  protected function includeCommonCSS() {
    return $this->includeCommonCSS;
  }

  protected function includeCommonJS() {
    return $this->includeCommonJS;
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
  // Lazy load
  //
  private function loadTemplateEngineIfNeeded() {
    if (!isset($this->templateEngine)) {
      Kurogo::log(LOG_DEBUG, "Initializing template engine", 'module');
      $this->templateEngine = new TemplateEngine($this->id);
      $this->templateEngine->registerPlugin('function', 'drawChart', array('KurogoChart', 'drawChart'));
      $this->templateEngine->registerPlugin('modifier','getLocalizedString', array($this,'getLocalizedString'));
    }
  }
  
  protected function initChart() {
    $this->addInternalCSS('/common/css/chart.css');
    $this->addInternalJavascript('/common/javascript/chart.js');
  }
  //
  // URL helper functions
  //
  protected function buildURL($page, $args=array()) {
    return self::buildURLForModule($this->configModule, $page, $args);
  }

  public static function buildURLForModule($id, $page, $args=array()) {
    KurogoWebBridge::removeAddedParameters($args);
    
    if (KurogoWebBridge::shouldRewriteInternalLinks()) {
      return KurogoWebBridge::getInternalLink($id, $page, $args);
      
    } else {
      $argString = '';
      if (is_array($args) && count($args)) {
        $argString = http_build_query($args);
      }
      
      return "/$id/$page".(strlen($argString) ? "?$argString" : '');
    }
  }

  protected function buildAjaxURL($page, $args=array()) {
      return self::buildAjaxURLForModule($this->configModule, $page, $args);
  }

  public static function buildAjaxURLForModule($id, $page, $args=array()) {
      if (KurogoWebBridge::shouldRewriteInternalLinks()) {
        return KurogoWebBridge::getAjaxLink($id, $page, $args);
        
      } else {
        $argString = '';
        if (is_array($args) && count($args)) {
            $argString = http_build_query($args);
        }
        return FULL_URL_PREFIX."$id/$page".(strlen($argString) ? "?$argString" : '');
      }
  }
  
  protected function buildExternalURL($url) {
    if (KurogoWebBridge::shouldRewriteInternalLinks()) {
      return KurogoWebBridge::getExternalLink($url);
    } else {
      return $url;
    }
  }
  
  protected function buildDownloadURL($url) {
    if (KurogoWebBridge::shouldRewriteInternalLinks()) {
      return KurogoWebBridge::getDownloadLink($url);
    } else {
      return $url;
    }
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

  protected function redirectToArray($params, $type=Kurogo::REDIRECT_TEMPORARY) {
        $id = isset($params['id']) ? $params['id'] : '';
        $page = isset($params['page']) ? $params['page'] : '';
        $args = isset($params['args']) ? $params['args'] : array();
        
        if ($id) {
            self::redirectToModule($id, $page, $args, $type);
        } elseif ($page) {
            self::redirectTo($page, $args, false, $type);
        }
        
        return false;
  }

  public function redirectToURL($url, $type=Kurogo::REDIRECT_TEMPORARY) {
    Kurogo::redirectToURL($url, $type);
  }

  public function redirectToModule($id, $page, $args=array(), $type=Kurogo::REDIRECT_TEMPORARY) {
    $url = self::buildURLForModule($id, $page, $args);
    
    //error_log('Redirecting to: '.$url);
    if (KurogoWebBridge::shouldRewriteRedirects()) {
      KurogoWebBridge::redirectToURL($url);
    } else {
      $url = URL_PREFIX . ltrim($url, '/');
      Kurogo::log(LOG_DEBUG, "Redirecting to module $id at $url", 'module');
      Kurogo::redirectToURL($url, $type);
    }
  }

  protected function redirectTo($page, $args=null, $preserveBreadcrumbs=false, $type=Kurogo::REDIRECT_TEMPORARY) {
    if (!isset($args)) { $args = $this->args; }
    
    $url = '';
    if ($preserveBreadcrumbs) {
      $url = $this->buildBreadcrumbURL($page, $args, false);
    } else {
      $url = $this->buildURL($page, $args);
    }
    
    //error_log('Redirecting to: '.$url);
    if (KurogoWebBridge::shouldRewriteRedirects()) {
      KurogoWebBridge::redirectToURL($url);
    } else {
      $url = URL_PREFIX . ltrim($url, '/');
      Kurogo::log(LOG_DEBUG, "Redirecting to page $page at $url", 'module');
      Kurogo::redirectToURL($url, $type);
    }
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
            $args = array_merge($this->getArrayForRequest(), array('code'=>'protected'));
            unset($args['args']);
            if ($this->getArg(self::AJAX_PARAMETER)) {
                $string = 'Unauthorized <script type="text/javascript">redirectToModule(\'kurogo\', \'error\',' . json_encode($args) .');</script>';
                die($string);
            } else {
                $this->redirectToModule('kurogo', 'error', $args);
            }
        } else {
            $loginModuleID = $this->getLoginModuleID();
            $args = $this->getArrayForRequest();
            unset($args['args']);
            if ($this->getArg(self::AJAX_PARAMETER)) {
                $string = 'Unauthorized <script type="text/javascript">redirectToModule(\'' . $loginModuleID . '\',\'index\',' . json_encode($args) . ');</script>';
                die($string);
            } else {
                $this->redirectToModule($loginModuleID, '', $args);
            } 
        }
  }
  
    /* This method would be called by other modules to get a valid link from a model object */
    public function linkForItem(KurogoObject $object, $options=null) {
       throw new KurogoException("linkForItem must be subclassed if it is going to be used");
    }

    /* default implmentation. Subclasses may wish to override this */
    public function linkForValue($value, Module $callingModule, $otherValue=null) {
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

    protected function getBrowser() {
        $this->loadDeviceClassifierIfNeeded();
        return $this->deviceClassifier->getBrowser();
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

        $this->moduleName = $this->getModuleVar('title','module');

        $this->pagetype = $this->getPagetype();
        $this->platform = $this->getPlatform();
        $this->browser  = $this->getBrowser();

        switch ($this->getPagetype()) {
            case 'compliant':
                $this->imageExt = '.png';
                break;
            
            case 'basic':
                $this->imageExt = '.gif';
                break;
        }
        
        $this->ajaxContentLoad = $this->getArg(self::AJAX_PARAMETER) ? true : false;
        $this->ajaxContainerPage = $this->getArg(self::AJAX_BREADCRUMB_CONTAINER_PAGE, $this->page);
        $this->ajaxContainerPageArgs = $this->getArg(self::AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS, http_build_query($this->args));
        
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
    $this->redirectToModule('kurogo', 'error', array_merge($this->getArrayForRequest(), array('code'=>'disabled')));
  }

    protected function unsecureModule() {
        $host = Kurogo::getOptionalSiteVar('HOST', $_SERVER['SERVER_NAME'], 'site settings');
        if (empty($host)) {
            $host = $_SERVER['SERVER_NAME'];
        }
        $port = Kurogo::getOptionalSiteVar('PORT', 80, 'site settings');
        if (empty($port)) {
            $port = 80;
        }

        $redirect= sprintf("http://%s%s%s", $host, $port == 80 ? '': ":$port", $_SERVER['REQUEST_URI']);
        Kurogo::log(LOG_DEBUG, "Redirecting to non-secure url $redirect",'module');
        Kurogo::redirectToURL($redirect);
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
        Kurogo::redirectToURL($redirect, Kurogo::REDIRECT_PERMANENT);
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
    
    public function allowRobots() {
        // Returns integers so the admin module can use this function
        if ($this->canAllowRobots && $this->getOptionalModuleVar('robots', $this->defaultAllowRobots)) {
            return 1;
        } else {
            return 0;
        }
    }
    
    public function canAllowRobots() {
        return $this->canAllowRobots;
    }
    
    public function canBeAddedToHomeScreen() {
        return $this->canBeAddedToHomeScreen;
    }
  
    public function canBeRemoved() {
        return $this->canBeRemoved;
    }

    public function canBeDisabled() {
        return $this->canBeDisabled;
    }

    public function canBeHidden() {
        return $this->canBeHidden;
    }

    /* return a list of all available module id's based on their class. This will include both site and Kurogo modules */    
    public static function getAllModuleClasses() {
     	
        $modulePaths = array(
          SITE_MODULES_DIR,
          MODULES_DIR
        );

        $moduleClasses = array();
        $moduleIDs = array();
        foreach ($modulePaths as $path) {
            $moduleFiles = glob($path . "/*/*WebModule.php");
            foreach ($moduleFiles as $file) {
                $moduleFile = realpath_exists($file);
                if (preg_match("/(Site)?([A-Za-z]+WebModule)\.php$/", $file, $bits)) {
                    $className = $bits[1] . $bits[2];
                    // prevent loading a class twice (i.e. a site overridden class) 
                    if (!in_array($className, $moduleClasses)) {
                        if ($moduleFile && include_once($moduleFile)) {
                            $info = new ReflectionClass($className);
                            if (!$info->isAbstract()) {
                                try {
                                    $module = new $className();
                                    $moduleClasses[] = $className;
                                    $moduleIDs[] = $module->getID();
                                } catch (Exception $e) {}
                            }
                        }
                    }
                }
            }
        }
        $moduleIDs = array_unique($moduleIDs);
        sort($moduleIDs);
        return $moduleIDs;        
    }
  

  public static function getAllModules() {
  	$configFiles = glob(SITE_CONFIG_DIR . "/*/module.ini");
    $modules = array();

  	foreach ($configFiles as $file) {
  		if (preg_match("#" . preg_quote(SITE_CONFIG_DIR,"#") . "/([^/]+)/module.ini$#", $file, $bits)) {
  			$id = $bits[1];
			try {
				if ($module = WebModule::factory($id, '', array(), false)) {
				   $modules[$id] = $module;
				}
			} catch (KurogoException $e) {
			}
  		}
  	}
    ksort($modules);    
    return $modules;        
  }

    protected function elapsedTime($timestamp) {
        $now = time();
        $diff = $now - $timestamp;
        $today = mktime(0,0,0);
        $today_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
        $date = new DateTime("@" . $timestamp);
        Kurogo::includePackage('DateTime');
        if ($diff > 0) {
            // more than 6 days
            if ($today - $today_timestamp > 518400) {
                return DateFormatter::formatDate($date, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE);
            } elseif ($today - $today_timestamp > 86400) { // up to 6 days
                // @TODO localize
                return sprintf("%d days ago", $diff/86400);
            } elseif ($today - $today_timestamp > 0) { // yesterday
                // @TODO localize
                return strftime('Yesterday @ %l:%M %p', $timestamp);
            } elseif ($diff > 3600) { 
                // @TODO localize
                return sprintf("%d hour%s ago", $diff/3600, intval($diff/3600)>1?'s':'');
            } elseif ($diff > 60) {
                // @TODO localize
                return sprintf("%d minute%s ago", $diff/60, intval($diff/60)>1?'s':'');
            } else {
                // @TODO localize
                return sprintf("%d second%s ago", $diff, $diff>1 ?'s':'');
            }
        
        } else {
            return DateFormatter::formatDate($date, DateFormatter::MEDIUM_STYLE, DateFormatter::MEDIUM_STYLE);
        }    
    }

  //
  // Module list control functions
  //

  protected function getModuleNavlist() {
    $navModules = $this->getAllModuleNavigationData(self::EXCLUDE_HIDDEN_MODULES);

    if (count($navModules['primary']) && count($navModules['secondary'])) {
      $separator = array('separator' => array('separator' => true));
    } else {
      $separator = array();
    }
        
    return array_merge($navModules['primary'], $separator, $navModules['secondary']);
  }
  
    public function isOnHomeScreen() {
        $navModules = $this->getAllModuleNavigationData(self::INCLUDE_HIDDEN_MODULES);
        $allModules = array_merge(array_keys($navModules['primary']), array_keys($navModules['secondary']));
        return in_array($this->configModule, $allModules);    
    }
    
    public function isOnTabletHome() {
        $portletConfig = $this->getHomeModulePortletConfig();
        return array_key_exists($this->configModule, $portletConfig);
    }
    
    public function removeFromHomeScreen() {
        $config = Kurogo::getSiteConfig('navigation', 'site');
        $config->removeSection($this->configModule);
        Kurogo::sharedInstance()->saveConfig($config);
    }

    public function removeFromTabletPane() {
        $config = $this->getHomeModulePortletConfig();
        if ($pane = $this->isOnTabletHome()) {
            $config->removeSection($this->configModule);
            Kurogo::sharedInstance()->saveConfig($config);
        }
    }
    
    public function removeModule($removeFromHome=false) {
    
        if ($this->isOnHomeScreen()) {
            if ($removeFromHome) {
                $this->removeFromHomeScreen();
            } else {
                throw new KurogoConfigurationException("Module must first be removed from home screen");
            }
        }

        if ($this->isOnTabletHome()) {
            if ($removeFromHome) {
                $this->removeFromTabletPane();
            } else {
                throw new KurogoConfigurationException("Module must first be removed from tablet pane");
            }
        }
        
        return parent::removeModule();
    }

/*  
    protected function getUserHiddenModuleIDs() {
    
        $hiddenIDs = array();
        if (isset($_COOKIE[self::HIDDEN_MODULES_COOKIE]) && $_COOKIE[self::HIDDEN_MODULES_COOKIE] != "NONE") {
            $hiddenIDs = explode(",", $_COOKIE[self::HIDDEN_MODULES_COOKIE]);
        }
        
        return $hiddenIDs;
    }
*/
    /* retained for compatibility */
    protected function getNavigationModules($includeHidden=self::INCLUDE_HIDDEN_MODULES) {
        return $this->getAllModuleNavigationData($includeHidden);
    }

    /* This method can be overridden to provide dynamic navigation data. It will only be used if DYNAMIC_MODULE_NAV_DATA = 1 */
    protected function getModuleNavigationData($moduleNavData) {
        return $moduleNavData;
    }
    
    public function getIcons() {
        $navigation_icon_set = $this->getOptionalThemeVar('navigation_icon_set');
        $iconSetBase = APP_DIR . '/common/images/iconsets/' . $navigation_icon_set;
        $size = 60;
        $icons = array();
        $files = glob($iconSetBase . "/$size/*.png");
        foreach ($files as $file) {
            $icon = basename($file, '.png');
            $icons[$icon] = $icon;
        }
        
        return $icons;
    }

    protected function getAllModuleNavigationData($includeHidden=self::INCLUDE_HIDDEN_MODULES, $iconSetType='navigation') {

        $modules = $moduleNavData = array(
            'primary'  => array(), 
            'secondary'=> array(), 
        );
        
        $navModules = Kurogo::getSiteSections('navigation', Config::APPLY_CONTEXTS_NAVIGATION);
        foreach ($navModules as $moduleID=>$moduleData) {
            $type = Kurogo::arrayVal($moduleData, 'type', 'primary');
            $moduleNavData[$type][$moduleID] = $moduleData;
        }

        if ($iconSet = $this->getOptionalThemeVar($iconSetType .'_icon_set')) {
            $iconSetSize = $this->getOptionalThemeVar($iconSetType .'_icon_size');
            $imgPrefix = "/common/images/iconsets/{$iconSet}/{$iconSetSize}/";
        } else {
            $homeModuleID = $this->getHomeModuleID();
            $imgPrefix = "/modules/{$homeModuleID}/images/";
        }

        //this is fixed for now
        $modulesThatCannotBeHidden = array('customize');
        $allModuleData = array();

        $userNavData = $this->getUserNavData();
        
        foreach ($moduleNavData as $type => $modulesOfType) {

            foreach ($modulesOfType as $moduleID => $navData) {
                $icon = $moduleID;
                $selected = $this->configModule == $moduleID;
                $primary = $type == 'primary';
                      
                $classes = array();
                if ($selected) { $classes[] = 'selected'; }
                if (!$primary) { $classes[] = 'utility'; }

                $hidable = !in_array($moduleID, $modulesThatCannotBeHidden);
                $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '' : '';
                $linkTarget = Kurogo::arrayVal($navData, 'linkTarget');
                
                // customize is implemented as a page on the home module
                if ($moduleID == 'customize') {
                    $visible = true;
                    $icon = $moduleID;
                    $moduleData = array(
                        'type'        => $type,
                        'selected'    => $selected,
                        'title'       => $this->getLocalizedString('CUSTOMIZE_TITLE'),
                        'shortTitle'  => $this->getLocalizedString('CUSTOMIZE_SHORT_TITLE'),
                        'url'         => "/" . $this->getHomeModuleID() . "/customize",
                        'hideable'     => $hidable,
                        'visible'      => true,
                        'img'         => $imgPrefix . "{$icon}{$imgSuffix}".$this->imageExt,
                        'class'       => implode(' ', $classes),
                        'linkTarget'  => $linkTarget
                    );
                } else {

                    //this will throw an exception if a module is not available, so watch out
                    $moduleConfig = Kurogo::getModuleConfig('module', $moduleID);

                    $title = Kurogo::arrayVal($navData,'title', $moduleConfig->getVar('title'));
                    $shortTitle = Kurogo::arrayVal($navData,'shortTitle', $title);
                    $icon = $moduleConfig->getOptionalVar('icon', $moduleID);
                            
        
                    $visible = Kurogo::arrayVal($userNavData, $moduleID, Kurogo::arrayVal($navData,'visible', 1));
                
                    $moduleData = array(
                        'type'        => $type,
                        'selected'    => $selected,
                        'title'       => $title,
                        'shortTitle'  => $shortTitle,
                        'url'         => "/$moduleID/",
                        'hideable'     => $hidable,
                        'visible'      => $visible,
                        'img'         => $imgPrefix . "{$icon}{$imgSuffix}".$this->imageExt,
                        'class'       => implode(' ', $classes),
                        'linkTarget'  => $linkTarget,
                    );

                    if (Kurogo::getOptionalSiteVar('DYNAMIC_MODULE_NAV_DATA', false)) {
                        $module = WebModule::factory($moduleID, false, array(), false); // do not initialize
                        $moduleData = $module->getModuleNavigationData($moduleData);
                    }
                }
                
                if ($visible || $includeHidden || ($type=='primary' && isset($userNavData['visible']))) {
                    $modules[$type][$moduleID] = $moduleData;
                }
          }
        }

        if (isset($userNavData['visible'])) {
            $userModuleNavData = array();
            foreach ($userNavData['visible'] as $moduleID=>$visible) {
                if (isset($modules['primary'][$moduleID])) {
                    if ($visible || $includeHidden) {
                        $userModuleNavData[$moduleID] = $modules['primary'][$moduleID];
                        $userModuleNavData[$moduleID]['visible'] = $visible;
                    }
                }
            }
            
            // make sure all primary modules are defined in userNavData
            // this ensures that new modules show up if a user has customized their layout
            $userModules = array_keys($userNavData['visible']);
            $navModules = array_keys($modules['primary']);
            if ($diff = array_diff($navModules, $userModules)) {
                foreach ($diff as $moduleID) {
                    $userModuleNavData[$moduleID] = $modules['primary'][$moduleID];
                }
            }
            
            $modules['primary'] = $userModuleNavData;
        }
        return $modules;
    }
    
    protected function setUserNavData($moduleIDs) {
        $cookieData = json_encode($moduleIDs);
        setcookie(self::MODULE_NAV_COOKIE, $cookieData, time() + Kurogo::getSiteVar('MODULE_NAV_COOKIE_LIFESPAN'), COOKIE_PATH);
        $_COOKIE[self::MODULE_NAV_COOKIE] = $cookieData;
    }

    protected function getUserNavData() {
        if ($this->getArg('resetUserNavData')) {
            $this->resetUserNavData();
        }
        if ($userNavData = Kurogo::arrayVal($_COOKIE, self::MODULE_NAV_COOKIE)) {
            $data = json_decode($userNavData, true);
            if (is_array($data)) {
                $userNavData = array('visible'=>$data);
            } else {
                $userNavData = null;
            }
        }
        return $userNavData;
    }

    protected function resetUserNavData() {
        setcookie(self::MODULE_NAV_COOKIE, false, mktime(0,0,0), COOKIE_PATH);
        $_COOKIE[self::MODULE_NAV_COOKIE] = false;
    }
  
  /*
  protected function getUserSortedModules($modules) {
    // sort primary modules if sort cookie is set
    if (isset($_COOKIE[self::MODULE_ORDER_COOKIE])) {
      $sortedIDs = array_merge(array($this->getHomeModuleID()), explode(",", $_COOKIE[self::MODULE_ORDER_COOKIE]));
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
    
    setcookie(self::MODULE_ORDER_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[self::MODULE_ORDER_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setNavigationHiddenModules($moduleIDs) {
    $lifespan = Kurogo::getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = count($moduleIDs) ? implode(",", $moduleIDs) : 'NONE';
    
    KurogoDebug::Debug(func_get_args(), true);
    
    setcookie(self::HIDDEN_MODULES_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[self::HIDDEN_MODULES_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  */
  
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
      $properties = array(
        'inlineCSSBlocks',
        'cssURLs',
        'inlineJavascriptBlocks',
        'inlineJavascriptFooterBlocks',
        'onOrientationChangeBlocks',
        'onLoadBlocks',
        'javascriptURLs',
      );
      $data = array(
          'properties' => array(),
          'minifyCSS'  => '',
          'minifyJS'   => '',
      );
      foreach ($properties as $property) {
        $data['properties'][$property] = $this->$property;
      }
  
      // Add page Javascript and CSS if any
      $context = stream_context_create(array(
        'http' => array(
          'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ),
      ));
      
      $javascript = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['js'], '/'), false, $context);
      if ($javascript) {
        $data['minifyJS'] = $javascript;
      }
  
      $css = @file_get_contents(FULL_URL_PREFIX.ltrim($minifyURLs['css'], '/'), false, $context);
      if ($css) {
        $data['minifyCSS'] = $css;
      }
      
      $cache->write($data, $cacheName);
    }
    
    return $data;
  }
  protected function importCSSAndJavascript($data) {
    foreach ($data['properties'] as $memberName => $arrays) {
      $this->$memberName = array_unique(array_merge($this->$memberName, $arrays));
    }
    
    if ($data['minifyCSS']) {
      array_unshift($this->inlineCSSBlocks, $data['minifyCSS']);
    }
    
    if ($data['minifyJS']) {
      array_unshift($this->inlineJavascriptBlocks, $data['minifyJS']);
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

    protected function hasBookmarks(){
        return count($this->getBookmarks()) > 0;
    }

    private function bookmarkToggleURL($toggle) {
        $args = $this->args;
        $args['bookmark'] = $toggle;
        return $this->buildBreadcrumbURL($this->page, $args, false);
    }

    protected function setBookmarks($bookmarks) {
        $values = implode(self::BOOKMARK_COOKIE_DELIMITER, $bookmarks);
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

        // the rest of this is all basic pagetype
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
            $bookmarks = explode(self::BOOKMARK_COOKIE_DELIMITER, $_COOKIE[$bookmarkCookie]);
        }
        return $bookmarks;
    }
    
    protected function generateBookmarkLink() {
        $hasBookmarks = $this->hasBookmarks();
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
    return json_encode($breadcrumbs);
  }
  
  private function decodeBreadcrumbParam($breadcrumbs) {
    return json_decode($breadcrumbs, true);
  }
  
  private function loadBreadcrumbs() {
    $breadcrumbs = array();
  
    if ($breadcrumbArg = $this->getArg(self::BREADCRUMB_PARAM)) {
      $breadcrumbs = $this->decodeBreadcrumbParam($breadcrumbArg);
      if (!is_array($breadcrumbs)) { $breadcrumbs = array(); }
    }

    if ($this->page != 'index' && $this->ajaxContainerPage != 'index') {
      // Make sure a module homepage is first in the breadcrumb list
      // Unless this page is being ajaxed in... then the original 
      // parent page might be the index page.
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
          self::BREADCRUMB_PARAM => $this->encodeBreadcrumbParam($linkCrumbs),
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
    if (KurogoWebBridge::isNativeCall()) {
      return $addBreadcrumb ? 'new' : 'same'; // Don't need actual breadcrumb on native
    } else {
      $breadcrumbs = $this->breadcrumbs;
    }
    
    $this->cleanBreadcrumbs($breadcrumbs);
    
    if ($addBreadcrumb) {
      $args = $this->args;
      unset($args[self::BREADCRUMB_PARAM]);
      unset($args[self::AJAX_BREADCRUMB_TITLE]);
      unset($args[self::AJAX_BREADCRUMB_LONG_TITLE]);
      unset($args[self::AJAX_BREADCRUMB_CONTAINER_PAGE]);
      unset($args[self::AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS]);
      
      $breadcrumbs[] = array(
        't'  => $this->breadcrumbTitle,
        'lt' => $this->breadcrumbLongTitle,
        'p'  => $this->ajaxContentLoad ? $this->ajaxContainerPage : $this->page,
        'a'  => $this->ajaxContentLoad ? $this->ajaxContainerPageArgs : http_build_query($args),
      );
    }
    
    //error_log(__FUNCTION__."(): saving breadcrumbs for {$this->page} ".print_r($breadcrumbs, true));
    return $this->encodeBreadcrumbParam($breadcrumbs);
  }
  
  private function getBreadcrumbArgs($addBreadcrumb=true) {
    return array(
      self::BREADCRUMB_PARAM => $this->getBreadcrumbString($addBreadcrumb),
    );
  }

  protected function buildBreadcrumbURL($page, $args, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURLForModule($this->configModule, $page, $args, $addBreadcrumb);
  }
  
  protected function buildBreadcrumbURLForModule($id, $page, $args, $addBreadcrumb=true) {
    KurogoWebBridge::removeAddedParameters($args);
    
    $args = array_merge($args, $this->getBreadcrumbArgs($addBreadcrumb));
    
    if (KurogoWebBridge::shouldRewriteInternalLinks()) {
      $url = KurogoWebBridge::getInternalLink($id, $page, $args);
    } else {
      $url = "/$id/$page?".http_build_query($args);
    }
    
    return $url;
  }
  
  protected function buildAjaxBreadcrumbURL($page, $args, $addBreadcrumb=true) {
      return $this->buildAjaxBreadcrumbURLForModule($this->configModule, $page, $args, $addBreadcrumb);
  }
  
  protected function buildAjaxBreadcrumbURLForModule($id, $page, $args, $addBreadcrumb=true) {
      if ($this->pagetype == 'basic') {
          // behavior for basic where no ajax is used
          return $this->buildBreadcrumbURLForModule($this->configModule, $page, $args, true);
          
      } else {
          // forward breadcrumb title
          $args[self::AJAX_BREADCRUMB_TITLE] = $this->getArg(self::AJAX_BREADCRUMB_TITLE, $this->breadcrumbTitle);
          
          // forward breadcrumb title
          $args[self::AJAX_BREADCRUMB_LONG_TITLE] = $this->getArg(self::AJAX_BREADCRUMB_LONG_TITLE, $this->breadcrumbLongTitle);
          
          // forward parent page id
          $args[self::AJAX_BREADCRUMB_CONTAINER_PAGE] = $this->getArg(self::AJAX_BREADCRUMB_CONTAINER_PAGE, $this->ajaxContainerPage);
          
          // forward parent page args
          $args[self::AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS] = $this->getArg(self::AJAX_BREADCRUMB_CONTAINER_PAGE_ARGS, $this->ajaxContainerPageArgs);
          
          // forward current breadcrumb arg rather than adding
          if (isset($this->args[self::BREADCRUMB_PARAM])) {
              $args[self::BREADCRUMB_PARAM] = $this->args[self::BREADCRUMB_PARAM];
          }
          
          return $this->buildAjaxURLForModule($id, $page, $args);
      }
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
      $this->assign('strings', Kurogo::getSiteSection('strings'));
  
      // load module config file
      $pageData = $this->getPageData();

      if (!isset($pageData[$this->page])) {
      	throw new KurogoPageNotFoundException(Kurogo::getLocalizedString("ERROR_PAGE_NOT_FOUND", $this->page));
      }
      
        $pageConfig = $pageData[$this->page];
        
        if (KurogoWebBridge::isNativeCall()) {
          $this->hasWebBridgePageRefresh = self::argVal($pageConfig, 'nativePageRefresh', false);
          $this->hasWebBridgeAutoRefresh = self::argVal($pageConfig, 'nativePageAutoRefresh', false);
        }
        
        if (KurogoWebBridge::isNativeCall() && self::argVal($pageConfig, 'nativePageTitle', '')) {
          $this->pageTitle = $pageConfig['nativePageTitle'];
          
        } else if (isset($pageConfig['pageTitle']) && strlen($pageConfig['pageTitle'])) {
          $this->pageTitle = $pageConfig['pageTitle'];
        }
        
        if (KurogoWebBridge::isNativeCall() && self::argVal($pageConfig, 'nativeBreadcrumbTitle', '')) {
          $this->breadcrumbTitle = $pageConfig['nativeBreadcrumbTitle'];
          
        } else if (isset($pageConfig['breadcrumbTitle'])  && strlen($pageConfig['breadcrumbTitle'])) {
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
      }
    
    
    // Ajax overrides for breadcrumb title and long title
    if (isset($this->args[self::AJAX_BREADCRUMB_TITLE])) {
      $this->breadcrumbTitle = $this->args[self::AJAX_BREADCRUMB_TITLE];
      $this->breadcrumbLongTitle = $this->breadcrumbTitle;
    }
    
    if (isset($this->args[self::AJAX_BREADCRUMB_LONG_TITLE])) {
      $this->breadcrumbLongTitle = $this->args[self::AJAX_BREADCRUMB_LONG_TITLE];
    }
  }
  
  protected function setTemplatePage($page, $moduleID=null) {
    $moduleID = is_null($moduleID) ? $this->id : $moduleID;
    $this->templatePage = $page;
    $this->templateModule = $moduleID;
  }
  
  
  // Programmatic overrides for titles generated from backend data
  protected function setPage($page) {
    if (preg_match("/^([a-z0-9_-]+)$/i", $page)) {
        Kurogo::log(LOG_INFO, "Setting page to $page", 'module');
        $this->page = $page;
    } else {
        throw new KurogoPageNotFoundException($this->getLocalizedString('ERROR_INVALID_PAGE'));
    }
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

  protected function setWebBridgePageRefresh($hasPageRefresh) {
    $this->hasWebBridgePageRefresh = $hasPageRefresh;
  }
  protected function setWebBridgeAutoRefresh($hasAutoRefresh) {
    $this->hasWebBridgeAutoRefresh = $hasAutoRefresh;
  }

  //
  // Module debugging
  //
  protected function addModuleDebugString($label, $string) {
    $this->moduleDebugStrings[$label] = $string;
  }

  //
  // Config files
  //
  
    
  public function getPageData() {
     $pages = $this->getModuleSections('pages');
     if (Kurogo::isLocalhost() && !isset($pages[self::WEB_BRIDGE_BUILD_TEMPLATES_PAGE])) {
        $pages[self::WEB_BRIDGE_BUILD_TEMPLATES_PAGE] = array(); // AppQ build
     }
     return $pages;
  }
  
  protected function loadPageConfigFile($page, $keyName=null, $opts=0) {
    Kurogo::log(LOG_WARNING, "loadPageConfigFile is deprecated, use loadPageConfigArea", "module");
    return $this->loadPageConfigArea($page, $keyName, $opts);
  }

  protected function loadPageConfigArea($page, $keyName=null, $opts=0) {

    $themeVars = $this->getModuleSections('page-' . $page);
    
    $this->loadTemplateEngineIfNeeded();
    if (!$keyName) { // false, null, empty string, etc
      foreach($themeVars as $key => $value) {
        $this->templateEngine->assign($key, $value);
      }
    } else {
      $this->templateEngine->assign($keyName, $themeVars);
    }
    
    return $themeVars;

  }
  
  protected function shouldShowNavigation() {
    return $this->page != 'pane';
  }
  
  protected function showLogin() {
    return $this->pagetype == 'tablet' || $this->getOptionalModuleVar('SHOW_LOGIN', false);
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
    $this->assign('navImageID',   $this->getModuleIcon());
    $this->assign('page',         $this->page);
    $this->assign('isModuleHome', $this->page == 'index');
    $this->assign('request_uri' , $_SERVER['REQUEST_URI']);
    $this->assign('hideFooterLinks' , $this->hideFooterLinks);
    $this->assign('ajaxContentLoad', $this->ajaxContentLoad);
    $this->assign('charset', Kurogo::getCharset());
    $this->assign('http_protocol', HTTP_PROTOCOL);

    $this->assign('webBridgeAjaxContentLoad', KurogoWebBridge::isAjaxContentLoad());
    
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
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Tablet iScroll
    if ($this->pagetype == 'tablet') {
        $this->addInternalJavascript('/common/javascript/lib/iscroll-4.2.js');

        // Module nav list
        if ($this->shouldShowNavigation()) {
           $this->assign('navigationModules', $this->getModuleNavlist());
           $allowCustomize = Kurogo::getOptionalModuleVar('ALLOW_CUSTOMIZE', true, $this->getHomeModuleID());
           $this->assignUserContexts($allowCustomize);
        }
    }
    
    
    if ($this->page == self::WEB_BRIDGE_BUILD_TEMPLATES_PAGE) {
        $title = 'Error!';
        $message = '';
        try {
            if (!Kurogo::isLocalhost()) {
                throw new KurogoException("{$this->page} command can only be run from localhost");
            }
            
            $platforms = array_filter(array_map('trim', explode(',', $this->getArg('platform', ''))));
            if (!$platforms) {
                throw new KurogoException("No platforms specified");
            }
            
            foreach ($platforms as $platform) {
                $this->buildNativeWebTemplatesForPlatform($platform);
            }
            
            $title = 'Success!';
            $message = 'Generated native web templates for '.implode(' and ', $platforms);
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $this->assign('contentTitle', $title);
        $this->assign('contentBody', $message);
      
    } else if (KurogoWebBridge::useNativeTemplatePageInitializer()) {
        Kurogo::log(LOG_DEBUG,"Calling initializeForNativeTemplatePage for $this->configModule - $this->page", 'module');
        $this->initializeForNativeTemplatePage(); //subclass behavior
        Kurogo::log(LOG_DEBUG,"Returned from initializeForNativeTemplatePage for $this->configModule - $this->page", 'module');
        
    } else {
        Kurogo::log(LOG_DEBUG,"Calling initializeForPage for $this->configModule - $this->page", 'module');
        $this->initializeForPage(); //subclass behavior
        Kurogo::log(LOG_DEBUG,"Returned from initializeForPage for $this->configModule - $this->page", 'module');
    }

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
    $this->assign('breadcrumbsShowAll',     $this->getOptionalThemeVar('breadcrumbs_show', true));
    
    if (Kurogo::getSiteVar('MODULE_DEBUG')) {
        $this->addModuleDebugString('Config Mode', implode(', ', Kurogo::sharedInstance()->getConfigModes()));
        $this->addModuleDebugString('Version', KUROGO_VERSION);
        if (SITE_VERSION) {
            $this->addModuleDebugString('Site Version', SITE_VERSION);
        }
        if (SITE_BUILD) {
            $this->addModuleDebugString('Site Build', SITE_BUILD);
        }
        if ($contexts = Kurogo::sharedInstance()->getActiveContexts()) {
            $this->addModuleDebugString('Context(s)', implode(', ', $contexts));
        }
        $this->assign('moduleDebugStrings',     $this->moduleDebugStrings);
    }

    $this->assign('webBridgeOnPageLoadParams', KurogoWebBridge::getOnPageLoadParams(
        $this->pageTitle, $this->breadcrumbTitle, 
        $this->hasWebBridgePageRefresh, $this->hasWebBridgeAutoRefresh));
    
    $this->assign('webBridgeOnPageLoadConfig', KurogoWebBridge::getOnPageLoadConfig());
    
    $this->assign('webBridgeConfig', KurogoWebBridge::getServerConfig(
          $this->configModule, $this->page, $this->args));
    
    $moduleStrings = $this->getOptionalModuleSection('strings');
    $this->assign('moduleStrings', $moduleStrings);
    $this->assign('homeLink', $this->buildURLForModule($this->getHomeModuleID(),'',array()));
    $this->assign('homeModuleID', $this->getHomeModuleID());
    
    $this->assignLocalizedStrings();
    
    if ($this->page == 'help') {
      // Module Help
      $this->assign('hasHelp', false);
      $template = 'common/templates/'.$this->page;
      
    } else if ($this->page == self::WEB_BRIDGE_BUILD_TEMPLATES_PAGE) {
        $template = 'common/templates/staticContent';
    
    } else if (KurogoWebBridge::useWrapperPageTemplate()) {
      // Web bridge page wrapper
      $template = 'common/templates/webBridge';
      $this->assign('webBridgeJSLocalizedStrings', json_encode(Kurogo::getLocalizedStrings()));
      
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
    $this->assign(Kurogo::getThemeVars());
    
    // Access Key Start
    $accessKeyStart = count($this->breadcrumbs);
    if ($this->configModule != $this->getHomeModuleID()) {
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
                $this->assign('session_logout_url', $this->buildURLForModule($this->getLoginModuleID(), 'logout', array('authority'=>$user->getAuthenticationAuthorityIndex())));
                $this->assign('footerLoginLink', $this->buildURLForModule($this->getLoginModuleID(), '', array()));
                $this->assign('footerLoginText', $this->getLocalizedString('SIGNED_IN_SINGLE', $authority->getAuthorityTitle(), $user->getFullName()));
                $this->assign('footerLoginClass', $authority->getAuthorityClass());
            } else {
                $this->assign('footerLoginClass', 'login_multiple');
                $this->assign('session_logout_url', $this->buildURLForModule($this->getLoginModuleID(), 'logout', array()));
                $this->assign('footerLoginLink', $this->buildURLForModule($this->getLoginModuleID(), 'logout', array()));
                $this->assign('footerLoginText', $this->getLocalizedString('SIGNED_IN_MULTIPLE'));
            }

            if ($session_max_idle = intval(Kurogo::getOptionalSiteVar('AUTHENTICATION_IDLE_TIMEOUT', 0))) {
                $this->setRefresh($session_max_idle+60);
            }
        } else {
            $this->assign('footerLoginClass', 'noauth');
            $this->assign('footerLoginLink', $this->buildURLForModule($this->getLoginModuleID(),'', array()));
            $this->assign('footerLoginText', $this->getLocalizedString('SIGN_IN_SITE', Kurogo::getSiteString('SITE_NAME')));
        }
    }

    /* set cache age. Modules that present content that rarely changes can set this value
    to something higher */
    header(sprintf("Cache-Control: max-age=%d", $this->cacheMaxAge));

    /*
     * Date is set by apache (or your webserver of choice).  Under apache, and possibly other
     * webservers, it is impossible to either manually set this header or read the value of it.
     * Here, we want to set the Expires header such that disable caching.  One method of doing
     * this is to set Expires to the same value as Date.
     * Because of execution time up to this point, very occasionally these two values are off by
     * 1 second.  The net result is that with a $this->cacheMaxAge of 0, the Date and Expires
     * headers are different by 1 second.  Combined with Pragma: no-cache and
     * Cache-Control: no-cache, you get conflicting behavior which can (in theory) be exploited.
     * It also causes security tools to complain about the difference.
     *
     * The only reliable thing to do when explicitly attempting to avoid caching is to set
     * Expires to a time in the past. The best way to do that is to set it to Unix epoch.
     */

    if($this->cacheMaxAge > 0)
    {
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $this->cacheMaxAge) . ' GMT');
    }
    else
    {
        header("Expires: " . gmdate('D, d M Y H:i:s', 0) . ' GMT');
    }
    
    return $template;
  }
  
  public function getLastNativeWebTemplatesBuildForPlatform($platform) {
    $media = KurogoWebBridge::getAvailableMediaInfoForModule($this->configModule);
    if (isset($media[$platform])) {
        return $this->elapsedTime($media[$platform]['mtime']);
    }
    
    return null;
  }

  public function getNativeWebTemplatesURLForPlatform($platform) {
    $media = KurogoWebBridge::getAvailableMediaInfoForModule($this->configModule);
    if (isset($media[$platform])) {
        return $media[$platform]['url'];
    }
    
    return null;
  }

  public function buildNativeWebTemplatesForPlatform($platform) {
      $pages = array_keys($this->getModuleSections('pages'));
      if ($pages) {
         $pages = array_diff($pages, array('pane')); 
      }
      if (!$pages) {
          throw new KurogoConfigurationException("module does not have any pages defined in pages.ini");
      }
      
      $additionalAssets = $this->nativeWebTemplateAssets();
      $nativeConfig = $this->getOptionalModuleSection('native_template');
      if ($nativeConfig && $nativeConfig['additional_assets']) {
          $additionalAssets = array_unique(array_merge($additionalAssets, $nativeConfig['additional_assets']));
      }
      
      // Phone version
      $rewriter = new KurogoWebBridge($this->configModule, KurogoWebBridge::PAGETYPE_PHONE, $platform, KurogoWebBridge::BROWSER);
      $rewriter->saveTemplates($pages, $additionalAssets);
      
      if (Kurogo::getOptionalSiteVar('NATIVE_TABLET_ENABLED', 1)) {
          // Tablet version
          $rewriter = new KurogoWebBridge($this->configModule, KurogoWebBridge::PAGETYPE_TABLET, $platform, KurogoWebBridge::BROWSER);
          $rewriter->saveTemplates($pages, $additionalAssets);
      }
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
    // Subclass this function to set up variables for each native template page
    // Native template pages are called with no arguments
    // Since initializeForPage usually fails when called with no arguments 
    // this is empty by default
    //
    protected function initializeForNativeTemplatePage() {
    }
    
    //
    // Subclass this function to manually specify additional local assets which must
    // be loaded.  Return an array of asset paths. (e.g. '/common/images/button.png')
    // Note: these can also be listed in module.ini in the [native_templates] section.
    public function nativeWebTemplateAssets() {
        return array();
    }

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

    protected function assignUserContexts($includeCustomize = true) {
        if ($userContextListData = $this->getUserContextListData()) {

            if ($this->pagetype == 'tablet') {
                $this->assign('userContextListDescription', Kurogo::getSiteString('USER_CONTEXT_LIST_DESCRIPTION_TABLET'));
                $userContextListStyle =Kurogo::getSiteVar('USER_CONTEXT_LIST_STYLE_TABLET', 'contexts');
            } else {
                $this->assign('userContextListDescription', Kurogo::getSiteString('USER_CONTEXT_LIST_DESCRIPTION'));
                $userContextListStyle = Kurogo::getSiteVar('USER_CONTEXT_LIST_STYLE_COMPLIANT', 'contexts');
            }

            if ($includeCustomize) {
                $homeModuleID = $this->getHomeModuleID();
                $userCustomized = (bool) $this->getUserNavData();
                if (!$userCustomized || $userContextListStyle == 'select') {
                    $userContextListData[] = array(
                        'title'=>Kurogo::getSiteString("USER_CONTEXT_CUSTOMIZE"),
                        'url'=>$this->buildURLForModule($homeModuleID, 'customize'),
                        'ajax'=>false,
                    );
                }
                $this->assign('customizeURL', $this->buildURLForModule($homeModuleID, 'customize'));
            }

            $this->assign('userContextList', $userContextListData);
            $this->assign('userContextListStyle', $userContextListStyle);

            if ($this->pagetype=='compliant' || $this->pagetype == 'tablet') {
                 $this->addInlineJavascript(
                  'var MODULE_NAV_COOKIE = "'.self::MODULE_NAV_COOKIE.'";'.
                  'var COOKIE_PATH = "'.COOKIE_PATH.'";'
                );
            }
        }
    }
    
    protected function getUserContextListData($reloadPage='modules', $includeCustom = true) {
        // show user selectable context switching 
        if ($contexts = Kurogo::sharedInstance()->getContexts()) {
            $userContextList = array();
            $ajaxURLPrefix = Kurogo::getSiteVar('DEVICE_DEBUG') ? rtrim(URL_PREFIX,'/') : '';
            $activeCount = 0;
            $homeModuleID = $this->getHomeModuleID();

            $userCustomized = (bool) $this->getUserNavData();
            foreach ($contexts as $context) {
                if ($context->isManual()) {
                    $args = array_merge($context->getContextArgs(), array('resetUserNavData'=>1));

                    if ($this->pagetype == 'compliant' || $this->pagetype == 'tablet') {
                        $ajax = true;
                        $url = $ajaxURLPrefix . self::buildURLForModule($homeModuleID, $reloadPage, $args);
                    } else {
                        $ajax = false;
                        $url = self::buildURLForModule($homeModuleID, 'index', $args);
                    }
                    $userContextList[] = array(
                        'active'=>$context->isActive() && !$userCustomized,
                        'context'=>$context->getID(),
                        'ajax'=>$ajax,
                        'title'=>$context->getTitle(),
                        'url'=>$url,
                    );
                }
            }

            if ($includeCustom && $userCustomized) {
                $userContextList[] = array(
                    'title'=>Kurogo::getSiteString("USER_CONTEXT_CUSTOM"),
                    'active'=> true,
                    'url'=>$this->buildURLForModule($homeModuleID, 'customize'),
                    'ajax'=>false,
                );
            }
            
            return $userContextList;
            
        }
    }
    
}
  
