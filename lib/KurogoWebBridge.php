<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoWebBridge
{
    protected $pagetype = 'unknown';
    protected $platform = 'unknown';
    protected $module = 'unknown';
    protected $page = 'index';
    protected $path = '';
    protected $pathExists = false;
    
    const PAGETYPE_PHONE  = 'compliant';
    const PAGETYPE_TABLET = 'tablet';
    const BROWSER         = 'native';

    const PAGETYPE_PARAMETER    = 'webBridgePagetype';
    const PLATFORM_PARAMETER    = 'webBridgePlatform';
    const BROWSER_PARAMETER     = 'webBridgeBrowser';
    const ASSET_CHECK_PARAMETER = 'webBridgeAssetCheck';
    
    const BRIDGE_URL_INTERNAL_LINK = 'kgobridge://link/';
    const BRIDGE_URL_EXTERNAL_LINK = 'kgobridge://external/link';
    const BRIDGE_URL_DOWNLOAD_LINK = 'kgobridge://download/';
    
    const FILE_TYPE_HTML       = 'html';
    const FILE_TYPE_CSS        = 'css';
    const FILE_TYPE_JAVASCRIPT = 'js';
    const FILE_TYPE_ASSET      = 'asset';
    
    const STUB_API_CLASS = 'KurogoWebBridgeAPIModule';
    const STUB_API_CLASS_FILE = __FILE__;
    
    // This global could be removed by using closures (ie php 5.3+)
    static protected $currentInstance = null;
    protected $processingFileType = self::FILE_TYPE_HTML;
    
    public function __construct($module, $pagetype=null, $platform=null, $browser=null) {
        if (!$pagetype) {
            $pagetype = Kurogo::deviceClassifier()->getPagetype();
        }
        if (!$platform) {
            $platform = Kurogo::deviceClassifier()->getPlatform();
        }
        if (!$platform) {
            $browser = Kurogo::deviceClassifier()->getBrowser();
        }
        $this->pagetype = $pagetype;
        $this->platform = $platform;
        $this->browser  = $browser;
        $this->module = $module;
        $this->path = WEB_BRIDGE_DIR.DIRECTORY_SEPARATOR.$platform.DIRECTORY_SEPARATOR.$module.
            ($pagetype == self::PAGETYPE_TABLET ? '-tablet' : '');
    }

    public function setPage($page) {
        $this->page = $page;
    }
    
    protected function nativeParams() {
        return self::pagetypeAndPlatformToParams($this->pagetype, $this->platform, $this->browser);
    }

    protected function rmPath($path) {
        if (is_file($path)) {
            if (!is_writeable($path)) { chmod($path, 0666); }
            
            unlink($path);
            return !is_file($path);
            
        } else if (is_dir($path)) {
            if (!is_writeable($path)) { chmod($path, 0777); }
            
            $handle = opendir($path);
            while ($entry = readdir($handle)) {
                if ($entry != '..' && $entry != '.' && $entry != '') {
                    $this->rmPath($path.DIRECTORY_SEPARATOR.$entry);
                }
            }
            closedir($handle);
            
            rmdir($path);
            return !is_dir($path);
        }
        return true; // never existed
    }

    protected function _addPathToZip($path, $zip, $zipPath='') {
        $zipPath .= DIRECTORY_SEPARATOR.basename($path);
        
        if (is_file($path)) {
            // Make sure repeatedly zipping the file doesn't modify it.
            // Since the zipfile contains the file modtime, force it to a set value.
            touch($path, 1234567890);  // 2009/02/13 23:31:30 UTC ... because
            
            $zip->addFile($path, $zipPath);
            
        } else if (is_dir($path)) {
            $handle = opendir($path);
            while ($entry = readdir($handle)) {
                if ($entry != '..' && $entry != '.' && $entry != '') {
                    $this->_addPathToZip($path.DIRECTORY_SEPARATOR.$entry, $zip, $zipPath);
                }
            }
            closedir($handle);
        }
    }

    protected function zipPath($path, $zipFile) {
        if (!class_exists('ZipArchive')) {
            throw new KurogoException("class ZipArchive (php-zip) not available");
        }
        
        if (!is_dir($path) && !is_file($path)) {
            throw new KurogoException("$path not found");
        }
        
        $pathToZip = realpath($path);
        
        $zip = new ZipArchive();
        $zip->open($zipFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        $this->_addPathToZip($path, $zip);
        $zip->close();
    }

    protected static function getFileType($file) {
        $parts = explode('.', $file);
        $ext = strtolower(end($parts));
        if (count($parts) < 2 || in_array($ext, array('html', 'php'))) {
            return self::FILE_TYPE_HTML;
            
        } else if (count($parts) > 1 && $ext == 'css') {
            return self::FILE_TYPE_CSS;
            
        } else if (count($parts) > 1 && $ext == 'js') {
            return self::FILE_TYPE_JAVASCRIPT;
            
        } else {
            return self::FILE_TYPE_ASSET;
        }
    }

    //
    // Helper functions to avoid code duplication
    //
    protected function getAsset($urlSuffix) {
        $url = FULL_URL_PREFIX.$urlSuffix.(stripos($urlSuffix, '?') ? '&' : '?').
            http_build_query($this->nativeParams());
        //error_log($url);
        $contents = @file_get_contents($url);
        if (!$contents) {
            Kurogo::log(LOG_NOTICE, "Failed to load asset $url", 'api');
        }
        return $contents;
    }

    protected function saveAsset($contents, $file) {
        $filePath = "{$this->path}/$file";
        
        $dir = dirname($filePath);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0700, true)) {
                throw new KurogoDataException("Could not create $dir");
            }
        }
        
        if (!file_put_contents($filePath, $contents)) {
            throw new KurogoDataException("Unable to write to $filePath");
        }
    }

    protected function urlSuffixToFile($urlSuffix) {
        return preg_replace(
            array(
                '@.php$@',                             // remove .php extension
                '@device/'.$this->pagetype.'-[^/]+/@', // remove device classifier
                '@^min/\?g=file-/([^&]+)(&.+|)$@',     // rewrite minify local files
                '@^min/g=([^-]+)-([^&]+)(&.+|)$@',     // rewrite minify css and js urls
            ),
            array(
                '',
                '',
                '$1',
                $this->page.'_min.$1',
            ),
            ltrim($urlSuffix, '/')
        );
    }

    protected static function getPartsForMatches($matches) {
        $urlSuffix = html_entity_decode($matches[3]);
        $file = self::$currentInstance->urlSuffixToFile($urlSuffix);
        
        if (strpos($matches[0], '/'.FileLoader::fileDir().'/') !== FALSE) {
            $file = '';                  // do not rewrite fileloader urls
            $replacement = $matches[0];
            
        } else if ($file) {
            $replacement = $matches[1].$file.$matches[4];
            
        } else {
            Kurogo::log(LOG_NOTICE, "Unable to determine file name for '{$matches[0]}'", 'api');
            $replacement = $matches[0];
        }
        
        return array($urlSuffix, $file, $replacement);
    }

    //
    // Callbacks for preg_replace_callback
    //

    protected static function rewriteURLsToFilePathsCallback($matches) {
        list($urlSuffix, $file, $replacement) = self::getPartsForMatches($matches);
        
        return $replacement;
    }

    protected static function saveContentAndAssetsCallback($matches) {
        list($urlSuffix, $file, $replacement) = self::getPartsForMatches($matches);
        
        if ($file) {
            self::$currentInstance->saveContentAndAssets($urlSuffix, $file, self::getFileType($file));
        }
        
        return $replacement;
    }

    //
    // Template and asset generation functions
    //

    protected function _rewriteURLsToFilePaths($contents, $callback='rewriteURLsToFilePathsCallback', $fileType=self::FILE_TYPE_HTML) {
        // rewrite javascript url rewrites, removing php extension and relative paths
        $contents = preg_replace(
            array(
              '@(window.location\s*=\s*[\'\"])\.\./([^\'\"\.]+)(.php|([^"]*))([\'\"])@',
              '@(window.location\s*=\s*[\'\"])\./([^\'\"\.]+)(.php|([^"]*))([\'\"])@',
            ),
            array(
              '$1'.self::BRIDGE_URL_INTERNAL_LINK.'$2$4$5',
              '$1'.self::BRIDGE_URL_INTERNAL_LINK.$this->module.'/$2$4$5',
            ),
            $contents
        );
        
        // rewrite form action urls, removing php extension
        $contents = preg_replace(
            array(
              '@(<form\s+[^>]*action=")('.preg_quote(URL_PREFIX).')([^"\.]+)(.php|([^"]*))(")@',
            ),
            array(
                '$1'.self::BRIDGE_URL_INTERNAL_LINK.'$3$5$6',
            ),
            $contents
        );

        // rewrite all other internal urls
        $oldFileType = $this->processingFileType;
        $this->processingFileType = $fileType;
        self::$currentInstance = $this;
        $contents = preg_replace_callback(
            ';(href\s*=\s*"|href\s*=\s*\'|src\s*=\s*"|src\s*=\s*\'|url\(")'.
                '('.preg_quote(URL_PREFIX).')([^>\'\"\\\)]+)'.
            '("\)|"|\');', 
            array(get_class(), $callback), 
            $contents
        );
        $this->processingFileType = $oldFileType; // restore state since saveContentAndAssets is called recursively
        
        return $contents;
    }

    protected function saveContentAndAssets($urlSuffix=null, $file=null, $fileType=self::FILE_TYPE_HTML) {
        if (!$urlSuffix) {
            $urlSuffix = "{$this->module}/{$this->page}";
        }
        if (!$file) {
            $file = "{$this->page}.html";
        }
        
        $filePath = "{$this->path}/$file";
        
        $contents = $this->getAsset($urlSuffix);
        if ($contents) {
            if ($fileType != self::FILE_TYPE_ASSET) {
                self::$currentInstance = $this;
                $contents = $this->_rewriteURLsToFilePaths($contents, 'saveContentAndAssetsCallback', $fileType);
            }
            
            $this->saveAsset($contents, $file);
        }
    }

    public function rewriteURLsToFilePaths($contents) {
        return $this->_rewriteURLsToFilePaths($contents); // internal function with more arguments
    }

    public function saveTemplates($pages, $additionalAssets = array()) {
        foreach ($pages as $page) {
            $this->setPage($page);
            $this->saveContentAndAssets();
            
            // Also check for inline content
            $contents = $this->getAsset("{$this->module}/{$this->page}?".
                WebModule::AJAX_PARAMETER."=1&".self::ASSET_CHECK_PARAMETER.'=1');
            
            if ($contents) {
                self::$currentInstance = $this;
                $contents = $this->_rewriteURLsToFilePaths($contents, 'saveContentAndAssetsCallback', true);
            }
        }
        
        // make sure to get loading spinner
        $additionalAssets[] = "/common/images/loading.gif";
        
        foreach ($additionalAssets as $asset) {
            $contents = $this->getAsset($asset);
            $file = $this->urlSuffixToFile($asset);
            if ($contents && $file) {
                $this->saveAsset($contents, $file);
            }
        }
        
        // write out zip file
        $this->zipPath($this->path, $this->path.'.zip');
        $this->rmPath($this->path);
    }

    //
    // Config URL for setting native navbar options
    //

    public static function getOnPageLoadParams($pageTitle, $backTitle, $hasRefresh, $hasAutoRefresh=false) {
        $params = array(
            'pagetitle' => $pageTitle,
        );
        if ($backTitle) {
            $params['backtitle'] = $backTitle;
        }
        if ($hasRefresh) {
            $params['refresh'] = 1;
        }
        if ($hasAutoRefresh) {
            $params['autorefresh'] = 1;
        }
        
        return json_encode($params);
    }
    
    //
    // Server template configuration
    //

    public static function getServerConfig($id, $page, $args) {
        $isNative = self::hasNativePlatform();
        
        $staticConfig = array(
            'module'     => $id,
            'page'       => $page,
            'ajaxArgs'   => WebModule::AJAX_PARAMETER."=1",
            'timeout'    => Kurogo::getOptionalSiteVar('WEB_BRIDGE_AJAX_TIMEOUT', 60),
            'events'     => $isNative,
        );
        if (self::forceNativePlatform($pagetype, $platform, $browser)) {
            $staticConfig['ajaxArgs'] .= '&'.http_build_query(
                self::pagetypeAndPlatformToParams($pagetype, $platform, $browser));
        }
        
        // configMappings are so that keys used by native side are
        // independent of the config keys in the kgoBridge class
        $configMappings = json_encode(array(
            'KGO_WEB_BRIDGE_CONFIG_URL'   => 'urlPrefix',
            'KGO_WEB_BRIDGE_PAGE_ARGS'    => 'pageArgs',
            'KGO_WEB_BRIDGE_COOKIES'      => 'cookies',
            'KGO_WEB_BRIDGE_AJAX_CONTENT' => 'ajaxContent',
            'KGO_WEB_BRIDGE_GEOLOCATION'  => 'geolocation',
        ));
        
        // native bridge variables
        $jsInit = '__KGO_WEB_BRIDGE_JAVASCRIPT_INIT__';
        $bridgeConfig = '__KGO_WEB_BRIDGE_CONFIG_JSON__';
        
        if (!$isNative) {
            // emulate what native bridge would replace variables with
            $jsInit = '';
            $bridgeConfig = json_encode(array(
                'KGO_WEB_BRIDGE_CONFIG_URL' => rtrim(FULL_URL_PREFIX, '/'),
                'KGO_WEB_BRIDGE_PAGE_ARGS' => http_build_query($args),
            ));
        }
        
        return array(
            'jsInit'         => $jsInit,
            'configMappings' => $configMappings,
            'staticConfig'   => json_encode($staticConfig),
            'bridgeConfig'   => $bridgeConfig,
        );
    }
    
    public static function getOnPageLoadConfig() {
        // These config variables come from the real server
        $config = array(
            'cookiePath' => COOKIE_PATH,
        );
        return json_encode($config);
    }


    //
    // Detecting native user agents
    //

    // Note: the following functions may be called before the device classifier is initialized

    private static function paramsToPagetypeAndPlatform(&$pagetype, &$platform, &$browser) {
        if (isset($_GET[self::PAGETYPE_PARAMETER]) && $_GET[self::PAGETYPE_PARAMETER] && 
            isset($_GET[self::PLATFORM_PARAMETER]) && $_GET[self::PLATFORM_PARAMETER] &&
            isset($_GET[self::BROWSER_PARAMETER]) && $_GET[self::BROWSER_PARAMETER]) {

            $pagetype = $_GET[self::PAGETYPE_PARAMETER];
            $platform = $_GET[self::PLATFORM_PARAMETER];
            $browser  = $_GET[self::BROWSER_PARAMETER];
            
            return true;
        } else {
            return false;
        }
    }

    private static function pagetypeAndPlatformToParams($pagetype, $platform, $browser) {
        return array(
            self::PAGETYPE_PARAMETER => $pagetype,
            self::PLATFORM_PARAMETER => $platform,
            self::BROWSER_PARAMETER  => $browser,
        );
    }

    public static function isAjaxContentLoad() {
        return isset($_GET[WebModule::AJAX_PARAMETER]) && $_GET[WebModule::AJAX_PARAMETER];
    }

    private static function isAssetCheck() {
        return isset($_GET[self::ASSET_CHECK_PARAMETER]) && $_GET[self::ASSET_CHECK_PARAMETER];
    }

    private static function hasNativePlatform() {
        return isset($_GET[self::PAGETYPE_PARAMETER]) && $_GET[self::PAGETYPE_PARAMETER] && 
               isset($_GET[self::PLATFORM_PARAMETER]) && $_GET[self::PLATFORM_PARAMETER] && 
               isset($_GET[self::BROWSER_PARAMETER])  && $_GET[self::BROWSER_PARAMETER];
    }

    public static function forceNativePlatform(&$pagetype, &$platform, &$browser) {
        if (self::hasNativePlatform()) {
            self::paramsToPagetypeAndPlatform($pagetype, $platform, $browser);
            return true;
            
        } else {
            return false;
        }
    }
    
    public static function shouldRewriteAssetPaths() {
        return self::hasNativePlatform() && self::isAjaxContentLoad() && !self::isAssetCheck();
    }
    
    public static function shouldRewriteInternalLinks() {
        return self::hasNativePlatform();
    }

    // Note: the following functions can only be used after the device classifier initializes

    public static function isNativeCall() {
        return self::hasNativePlatform() || Kurogo::deviceClassifier()->getBrowser() == 'native';
    }
    
    public static function useNativeTemplatePageInitializer() {
        return self::isNativeCall() && (!self::isAjaxContentLoad() || self::isAssetCheck());
    }
    
    public static function shouldRewriteRedirects() {
        return self::isNativeCall() && self::isAjaxContentLoad() && !self::isAssetCheck();
    }
    
    public static function shouldIgnoreAuth() {
        return Kurogo::isLocalhost() && self::isNativeCall() && (!self::isAjaxContentLoad() || self::isAssetCheck());
    }
    
    public static function useWrapperPageTemplate() {
        return self::isNativeCall() && !self::isAjaxContentLoad();
    }
    
    public static function removeAddedParameters(&$args) {
        if (is_array($args)) {
            unset($args[WebModule::AJAX_PARAMETER]);
        }
    }
    
    //
    // Rewriting links
    //
    
    public static function getInternalLink($id, $page, $args=array()) {
        if (!$page) { $page = 'index'; }
        
        return self::BRIDGE_URL_INTERNAL_LINK."$id/$page".
            ($args ? '?'.http_build_query($args) : '');
    }
    
    public static function getAjaxLink($id, $page, $args=array()) {
        if (self::forceNativePlatform($pagetype, $platform, $browser)) {
            $args = array_merge(self::pagetypeAndPlatformToParams($pagetype, $platform, $browser), $args);
        }
        return FULL_URL_PREFIX."$id/$page?".http_build_query($args);
    }
    
    public static function getExternalLink($url) {
        if (strpos($url, self::BRIDGE_URL_INTERNAL_LINK) === 0) {
            // Use different scheme for urls which should be external but are in Kurogo
            // so they don't get rewritten automatically by the TemplateEngine
            $url = self::BRIDGE_URL_EXTERNAL_LINK.'?'.http_build_query(array(
                'url' => str_replace(self::BRIDGE_URL_INTERNAL_LINK, FULL_URL_PREFIX, $url),
            ));
        }
        return $url;
    }
    
    public static function getDownloadLink($url) {
        if (strpos($url, self::BRIDGE_URL_INTERNAL_LINK) === 0) {
            // Use different scheme for urls which should be external but are in Kurogo
            // These must always be files to be downloaded -- internal web pages
            // should be Kurogo pages and use internal links so authn works properly.
            $url = self::BRIDGE_URL_DOWNLOAD_LINK.'?'.http_build_query(array(
                'url' => str_replace(self::BRIDGE_URL_INTERNAL_LINK, FULL_URL_PREFIX, $url),
            ));
        }
        return $url;
    }
    
    public static function redirectToURL($url) {
        if (!self::hasNativePlatform()) {
            // web debugging mode:
            $url = URL_PREFIX.ltrim($url, '/');
        }
        echo '<script type="text/javascript">window.location = "'.$url.'";</script>';
        exit;
    }
    
    //
    //  Check for existence of media assets calls
    //
    
    public static function getAssetsDir() {
        return 'media'.DIRECTORY_SEPARATOR.'web_bridge';
    }
    
    public static function getAvailableMediaInfoForModule($id) {
        $cacheKey = "webbridge-mediainfo-$id";
        
        // use memory cache to make this more efficient
        $info = Kurogo::getCache($cacheKey);
        if ($info === false) {
            $files = array_merge(
                (array)glob(WEB_BRIDGE_DIR."/*/$id.zip"), 
                (array)glob(WEB_BRIDGE_DIR."/*/$id-tablet.zip")
            );
        
            $info = array();
            foreach ($files as $file) {
                $name = basename($file, '.zip');
                $dir = realpath(dirname($file));
                
                $parts = explode(DIRECTORY_SEPARATOR, $dir);
                if (!$parts) { continue; }
                
                $platform = end($parts);
                if (!$platform) { continue; }
                
                $key = $platform;
                if ($name == "$id-tablet") {
                    $key .= "-tablet";
                }
                
                $file = realpath_exists($file);
                if (!$file) { continue; }
                
                $info[$key] = array(
                    'url'   => FULL_URL_PREFIX."media/web_bridge/$platform/$name.zip",
                    'file'  => $file,
                    'mtime' => filemtime($file),
                    'md5'   => md5_file($file),
                );
            }
            Kurogo::setCache($cacheKey, $info);
        }
        return $info ? $info : array();
    }
    
    public static function moduleHasMediaAssets($id) {
        return count(self::getAvailableMediaInfoForModule($id)) > 0;
    }
    
    public static function getHelloMessageForModule($id, $platform=null) {
        $bridgeConfig = array();
        
        $mediaInfo = KurogoWebBridge::getAvailableMediaInfoForModule($id);
        foreach ($mediaInfo as $key => $mediaItem) {
            $bridgeConfig[$key] = array(
                'md5' => $mediaItem['md5'],
                'url' => $mediaItem['url'],
            );
        }

        if ($bridgeConfig && $platform) {
            $bridgeConfig = isset($bridgeConfig[$platform]) ? array($platform=>$bridgeConfig[$platform]) : null;
        }
        return $bridgeConfig ? $bridgeConfig : null;
    }
}

