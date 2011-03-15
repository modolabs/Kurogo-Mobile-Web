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

abstract class WebModule extends Module {

  protected $moduleName = '';
  protected $hasFeeds = false;
  protected $feedFields = array();
  
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
  
  private $templateEngine = null;
  
  private $htmlPager = null;
  private $inPagedMode = true;
  
  private $tabbedView = null;
  
  protected $cacheMaxAge = 0;
  
  protected $autoPhoneNumberDetection = true;
  
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
  private function getMinifyUrls($pageOnly=false) {
    $page = preg_replace('/[\s-]+/', '+', $this->page);
    $minKey = "{$this->id}-{$page}-{$this->pagetype}-{$this->platform}-".md5(SITE_DIR);
    $minDebug = $this->getSiteVar('MINIFY_DEBUG') ? '&amp;debug=1' : '';
    
    $addArgString = $pageOnly ? '&amp;pageOnly=true' : '';
    
    return array(
      'css' => "/min/g=css-$minKey$minDebug$addArgString",
      'js'  => "/min/g=js-$minKey$minDebug$addArgString",
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
    return self::buildURLForModule($this->id, $page, $args);
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
    
    if ($to == '' && $GLOBALS['deviceClassifier']->mailToLinkNeedsAtInToField()) {
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
    
  public function hasFeeds() {
     return $this->hasFeeds;
  }
  
  public function getFeedFields() {
     return $this->feedFields;
  }
  
  public function removeFeed($index) {
       $feedData = $this->loadFeedData();
       if (isset($feedData[$index])) {
           unset($feedData[$index]);
           if (is_numeric($index)) {
              $feedData = array_values($feedData);
           }
           
           $this->saveConfig(array('feeds'=>$feedData), 'feeds');
       }
  }
  
  public function addFeed($newFeedData, &$error=null) {
       $feedData = $this->loadFeedData();
       if (!isset($newFeedData['TITLE']) || empty($newFeedData['TITLE'])) {
         $error = "Feed Title cannot be blank";
         return false;
       }

       if (isset($newFeedData['BASE_URL']) && empty($newFeedData['BASE_URL'])) {
         $error = "Feed URL cannot be blank";
         return false;
       }
       
       if (isset($newFeedData['LABEL'])) {
          $label = $newFeedData['LABEL'];
          unset($newFeedData['LABEL']);
          $feedData[$label] = $newFeedData;
       } else {
          $feedData[] = $newFeedData;
       }
       
       return $this->saveConfig(array('feeds'=>$feedData), 'feeds');
  }
  
  //
  // Admin Methods
  //
  //
  
  protected function prepareAdminForSection($section, $adminModule) {
    switch ($section)
    {
        case 'strings':
            $strings = $this->getModuleSection('strings');
            $formListItems = array();
            foreach ($strings as $string=>$value) {
                $item = array(
                    'label'=>implode(" ", array_map("ucfirst", explode("_", strtolower($section)))),
                    'name'=>"moduleData[strings][$string]",
                    'typename'=>"moduleData][strings][$string",
                    'value'=>is_array($value) ? implode("\n\n", $value) : $value,
                    'type'=>is_array($value) ? 'paragraph' : 'text'
                );
                
                $formListItems[] = $item;
            }
            $adminModule->assign('formListItems' ,$formListItems);
            break;
    }
  }
  
  protected function saveConfig($moduleData, $section=null) {
        switch ($section)
        {
            case 'feeds':
            case 'nav':
                $type = $section;
                break;
            default:
                $type = 'module';
                break;
        }

        $moduleConfigFile = ModuleConfigFile::factory($this->id, $type, ConfigFile::OPTION_CREATE_EMPTY);
        
        switch ($section)
        {
            case 'feeds':
            case 'nav':
                $moduleData = $moduleData[$section];
                // clear out empty values
                foreach ($moduleData as $feed=>$feedData) {
                    foreach ($feedData as $var=>$value) {
                        if (strlen($value)==0) {
                            unset($moduleData[$feed][$var]);
                        }
                    }
                }
                $moduleConfigFile->setSectionVars($moduleData);
                break;
            default:
                $moduleConfigFile->addSectionVars($moduleData, !$section);
        }
        
        $moduleConfigFile->saveFile();
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
  
    $module = parent::factory($id, 'web');
    $module->init($page, $args);
    $module->initialize();

      return $module;
    }
    
    private function init($page='', $args=array()) {
      
        parent::init();

        $moduleData = $this->getModuleData();
        $this->moduleName = $moduleData['title'];

        $this->setArgs($args);

        $this->pagetype      = $GLOBALS['deviceClassifier']->getPagetype();
        $this->platform      = $GLOBALS['deviceClassifier']->getPlatform();
        $this->supportsCerts = $GLOBALS['deviceClassifier']->getSupportsCerts();

        // Pull in fontsize
        if (isset($args['font'])) {
          $this->fontsize = $args['font'];
          setcookie('fontsize', $this->fontsize, time() + $this->getSiteVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
        
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
          $this->setAutoPhoneNumberDetection($this->getSiteVar('AUTODETECT_PHONE_NUMBERS'));
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
    
  protected function getSectionTitleForKey($key) {
     switch ($key)
     {
            case 'strings':
                return 'Strings';
     }
     return $key;
  }

  protected function getModuleItemForKey($key, $value) {
    $item = array(
        'label'=>implode(" ", array_map("ucfirst", explode("_", strtolower($key)))),
        'name'=>"moduleData[$key]",
        'typename'=>"moduleData][$key",
        'value'=>$value,
        'type'=>'text'
    );

    switch ($key)
    {
        case 'display_type':
            $item['label'] = 'Display type';
            $item['type'] = 'radio';
            $item['options'] = array(
                'list'=>'List View',
                'springboard'=>'Springboard');
            break;
        case 'title':
            $item['type'] = 'text';
            $item['subtitle'] = 'The name this module will be presented as to users (i.e. the home screen)';
            break;
        case 'disabled':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'If a module is disabled, it will be inaccessible to all users';
            $item['label'] = 'Module Disabled';
            break;
        case 'disableable':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'Allows users to hide the module from the home screen using the Customize module';
            $item['label'] = 'Users can disable';
            break;
        case 'movable':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'Allows users to adjust the order of this module on the home screen using the Customize module';
            $item['label'] = 'Users can reorder';
            break;
        case 'search':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'Module should be included when doing site-wide (federated) search from the home screen';
            $item['label'] = 'Search';
            break;
        case 'protected':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'Allows access to the module only by authenticated users';
            $item['label'] = 'Protected';
            break;
        case 'secure':
            $item['type'] = 'boolean';
            $item['subtitle'] = 'Module must be accessed using a SSL connection. Note: Maintaing a proper SSL site is the responsibility of the system administrator';
            $item['label'] = 'Secure';
            break;
        case 'id':
            $item['type'] = 'label';
            $item['subtitle'] = 'The internal id for this module. It can only be changed in the source code';
            break;
        default:
            break;
    }
    
    return $item;
  }

  protected function moduleDisabled() {
    $this->redirectToModule('error', '', array('code'=>'disabled', 'url'=>$_SERVER['REQUEST_URI']));
  }
  
  protected function secureModule() {
      // redirect to https (at this time, we are assuming it's on the same host)
     $redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
     header("Location: $redirect");    
     exit();
  }

  //
  // Module control functions
  //
  protected function getAllModules() {
    $dirs = array(MODULES_DIR, SITE_MODULES_DIR);
    $modules = array();
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $d = dir($dir);
            while (false !== ($entry = $d->read())) {
                if ($entry[0]!='.' && is_dir(sprintf("%s/%s", $dir, $entry))) {
                   $module = WebModule::factory($entry);
                   $modules[$entry] = $module;
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
  private function getModuleNavigationConfig() {
    static $moduleNavConfig;
    if (!$moduleNavConfig) {
        $moduleNavConfig = $this->getConfig('home', 'module');
    }
    
    return $moduleNavConfig;
  }

  protected function getModuleNavlist() {
    $navModules = $this->getNavigationModules(false);
    $separator = array('separator' => array('separator' => true));
    return array_merge($navModules['primary'], $separator, $navModules['secondary']);
  }
  
  protected function getModuleCustomizeList() {    
    $navModules = $this->getNavigationModules(true);
    return $navModules['primary'];
  }

  protected function getNavigationModules($includeDisabled=true) {
    $moduleNavConfig = $this->getModuleNavigationConfig();
    
    $moduleConfig = array();
    
    $moduleConfig['primary'] = $moduleNavConfig->getSection('primary_modules', Config::LOG_ERRORS);
    if (!$moduleConfig['primary']) { $moduleConfig['primary'] = array(); }

    $moduleConfig['secondary'] = $moduleNavConfig->getSection('secondary_modules', Config::LOG_ERRORS);
    if (!$moduleConfig['secondary']) { $moduleConfig['secondary'] = array(); }

    $disabledIDs = array();
    if (isset($_COOKIE[DISABLED_MODULES_COOKIE]) && $_COOKIE[DISABLED_MODULES_COOKIE] != "NONE") {
      $disabledIDs = explode(",", $_COOKIE[DISABLED_MODULES_COOKIE]);
    }
    
    $modules = array(
      'primary' => array(),
      'secondary' => array(),
    );
    
    foreach ($moduleConfig as $type => $modulesOfType) {
      foreach ($modulesOfType as $id => $title) {
        $disabled = in_array($id, $disabledIDs);
        
        if ($includeDisabled || !$disabled) {
          $selected = $this->id == $id;
          $primary = $type == 'primary';
  
          $classes = array();
          if ($selected) { $classes[] = 'selected'; }
          if (!$primary) { $classes[] = 'utility'; }
    
          $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '-selected' : '';

          $modules[$type][$id] = array(
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
    $lifespan = $this->getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = implode(",", $moduleIDs);
    
    setcookie(MODULE_ORDER_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[MODULE_ORDER_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setNavigationHiddenModules($moduleIDs) {
    $lifespan = $this->getSiteVar('MODULE_ORDER_COOKIE_LIFESPAN');
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
    $this->cssURLs[] = '/min/g='.MIN_FILE_PREFIX.$path;
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
    $this->javascriptURLs[] = '/min/g='.MIN_FILE_PREFIX.$path;
  }
  protected function addExternalJavascript($url) {
    $this->javascriptURLs[] = $url;
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
    
    $javascript = @file_get_contents(FULL_URL_PREFIX.$minifyURLs['js']);
    if ($javascript) {
      array_unshift($data['inlineJavascriptBlocks'], $javascript);
    }

    $css = @file_get_contents(FULL_URL_PREFIX.$minifyURLs['css']);
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
  protected function addJQuery() {
    $this->addInternalJavascript('/common/javascript/jquery.js');
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
        $this->cleanBreadcrumbs(&$linkCrumbs);
        
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
    
    $this->cleanBreadcrumbs(&$breadcrumbs);
    
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
    return $this->buildBreadcrumbURLForModule($this->id, $page, $args, $addBreadcrumb);
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
      $this->loadSiteConfigFile('strings', false, ConfigFile::OPTION_CREATE_WITH_DEFAULT);
  
      // load module config file
      $pageData = $this->getPageData();

      if (isset($pageData[$this->page])) {
        $pageConfig = $pageData[$this->page];
        
        if (isset($pageConfig['pageTitle'])) {
          $this->pageTitle = $pageConfig['pageTitle'];
        }
          
        if (isset($pageConfig['breadcrumbTitle'])) {
          $this->breadcrumbTitle = $pageConfig['breadcrumbTitle'];
        } else {
          $this->breadcrumbTitle = $this->pageTitle;
        }
          
        if (isset($pageConfig['breadcrumbLongTitle'])) {
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
  
  protected function getPageConfig($name, $opts) {
    $config = ModuleConfigFile::factory($this->id, "page-$name", $opts);
    $GLOBALS['siteConfig']->addConfig($config);
    return $config;
  }
  
  protected function getPageData() {
     $pageConfig = $this->getConfig($this->id, 'pages');
     return $pageConfig->getSectionVars(true);
  }
  
  protected function loadSiteConfigFile($name, $keyName=null, $opts=0) {
    $config = ConfigFile::factory($name, 'site', $opts);
    $GLOBALS['siteConfig']->addConfig($config);
    if ($keyName === null) { $keyName = $name; }

    return $this->loadConfigFile($config, $keyName);
  }

  protected function loadPageConfigFile($page, $keyName=null, $opts=0) {
    $opts = $opts | ConfigFile::OPTION_CREATE_WITH_DEFAULT;
    $config = $this->getPageConfig($page, $opts);
    if ($keyName === null) { $keyName = $name; }
    return $this->loadConfigFile($config, $keyName);
  }
  
  protected function loadConfigFile(Config $config, $keyName=null) {
    $this->loadTemplateEngineIfNeeded();

    $themeVars = $config->getSectionVars(true);
    
    if ($keyName === false) {
      foreach($themeVars as $key => $value) {
        $this->templateEngine->assign($key, $value);
      }
    } else {
      $this->templateEngine->assign($keyName, $themeVars);
    }
    
    return $themeVars;
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
  
  private function setPageVariables() {
    $this->loadTemplateEngineIfNeeded();
        
    $this->loadPageConfig();
    
    // Set variables common to all modules
    $this->assign('moduleID',     $this->id);
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
    if ($gaID = $this->getSiteVar('GOOGLE_ANALYTICS_ID', null, Config::SUPRESS_ERRORS)) {
        $this->assign('GOOGLE_ANALYTICS_ID', $gaID);
        $this->assign('gaImageURL', $this->googleAnalyticsGetImageUrl($gaID));
    }
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Tablet module nav list
    if ($this->pagetype == 'tablet') {
      $this->addInternalJavascript('/common/javascript/lib/iscroll.js');
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
    
    $moduleStrings = $this->getModuleSection('strings', array(), Config::SUPRESS_ERRORS);
    $this->assign('moduleStrings', $moduleStrings);

    // Module Help
    if ($this->page == 'help') {
      $this->assign('hasHelp', false);
      $template = 'common/'.$this->page;
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
    
    // Access Key Start
    $accessKeyStart = count($this->breadcrumbs);
    if ($this->id != 'home') {
      $accessKeyStart++;  // Home link
    }
    $this->assign('accessKeyStart', $accessKeyStart);

    if ($this->getSiteVar('AUTHENTICATION_ENABLED')) {
        includePackage('Authentication');
        $this->setCacheMaxAge(0);
        $session = $this->getSession();
        $this->assign('session', $session);
        $this->assign('session_isLoggedIn', $this->isLoggedIn());

        if ($this->isLoggedIn()) {
            $this->assign('session_max_idle', intval($this->getSiteVar('AUTHENTICATION_IDLE_TIMEOUT', 0, Config::SUPRESS_ERRORS)));
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
