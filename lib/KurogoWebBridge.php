<?php

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

    // use same ajax param used by web modules so that module ajax calls
    // also disable web bridge ajax loader (otherwise we would get double-loads)
    const AJAX_PARAMETER        = 'ajax';
    
    const BRIDGE_URL_INTERNAL_LINK = 'kgobridge://link/';
    const BRIDGE_URL_EVENT_ONLOAD  = 'kgobridge://event/load';
    const BRIDGE_URL_EVENT_ERROR   = 'kgobridge://event/error';
    
    const FILE_TYPE_HTML       = 'html';
    const FILE_TYPE_CSS        = 'css';
    const FILE_TYPE_JAVASCRIPT = 'js';
    const FILE_TYPE_ASSET      = 'asset';
    
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
            $zip->addFile($path, $zipPath);
            
        } else if (is_dir($path)) {
            $zip->addEmptyDir($zipPath);
            
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
                '@device/'.$this->pagetype.'-[^/]+/@',
                '@^min/\?g=file-/([^&]+)(&.+|)$@',
                '@^min/g=([^-]+)-([^&]+)(&.+|)$@',
            ),
            array(
                '',
                '$1',
                $this->page.'_min.$1',
            ),
            ltrim($urlSuffix, '/')
        );
    }

    protected static function getPartsForMatches($matches) {
        $urlSuffix = html_entity_decode($matches[4]);
        $file = self::$currentInstance->urlSuffixToFile($urlSuffix);
        
        if (strpos($matches[0], '/'.FileLoader::fileDir().'/') !== FALSE) {
            $file = '';                  // do not rewrite fileloader urls
            $replacement = $matches[0];
            
        } else if ($file) {
            $replacement = $matches[1].$file.$matches[5];
            
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
        // rewrite javascript url rewrites
        $contents = preg_replace(
            array(
              '@(window.location\s*=\s*[\'\"])\.\./([^\'\"]+)([\'\"])@',
              '@(window.location\s*=\s*[\'\"])\./([^\'\"]+)([\'\"])@',
            ),
            array(
              '$1'.self::BRIDGE_URL_INTERNAL_LINK.'$2$3',
              '$1'.self::BRIDGE_URL_INTERNAL_LINK.$this->module.'/$2$3',
            ),
            $contents
        );
        
        // rewrite form action urls
        $contents = preg_replace(
            array(
              '@(<form\s+[^>]*action=")('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).')([^"]+)(")@',
              '@(<form\s+[^>]*action=")([^"/]+)(")@',
            ),
            array(
                '$1'.self::BRIDGE_URL_INTERNAL_LINK.'$3$4',
                '$1'.self::BRIDGE_URL_INTERNAL_LINK.$this->module.'/$2$3',
            ),
            $contents
        );

        // rewrite all other internal urls
        $oldFileType = $this->processingFileType;
        $this->processingFileType = $fileType;
        self::$currentInstance = $this;
        $contents = preg_replace_callback(
            ';'.
                '(href\s*=\s*"|href\s*=\s*\'|src\s*=\s*"|src\s*=\s*\'|url\(")'.
                '('.
                    '('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).')'.
                    '([^>\'\"\\\)]+)'.
                ')'.
                '("\)|"|\')'.
            ';', 
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
                self::AJAX_PARAMETER."=1&".self::ASSET_CHECK_PARAMETER.'=1');
            
            if ($contents) {
                self::$currentInstance = $this;
                $contents = $this->_rewriteURLsToFilePaths($contents, 'saveContentAndAssetsCallback', true);
            }
        }
        
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

    public static function getOnPageLoadURL($pageTitle, $backTitle, $hasRefresh) {
        if (!self::hasNativePlatform()) {
            return '';
        }
        
        $params = array(
          'pagetitle' => $pageTitle,
        );
        if ($backTitle) {
          $params['backtitle'] = $backTitle;
        }
        if ($hasRefresh) {
          $params['refresh'] = 1;
        }
        return self::BRIDGE_URL_EVENT_ONLOAD.'?'.http_build_query($params);
    }
    
    //
    // Config URL for HTTP errors
    //

    public static function getOnPageLoadErrorURL() {
        if (!self::hasNativePlatform()) {
            return '';
        }
        
        // http status code will be appended by javascript
        return self::BRIDGE_URL_EVENT_ERROR.'?type=load&code=';
    }

    public static function getURLBase() {
        if (self::hasNativePlatform()) {
            return '__KUROGO_URL_BASE__';
        } else {
            return URL_BASE;
        }
    }

    public static function getServerURL($id, $page) {
        $url = '';
        if (self::hasNativePlatform()) {
            $url .= '__KUROGO_SERVER_URL__';
        } else {
            $url .= rtrim(FULL_URL_PREFIX, '/');
        }
        $url .= "/{$id}/{$page}?".self::AJAX_PARAMETER."=1";
        if (self::forceNativePlatform($pagetype, $platform, $browser)) {
            $url .= '&'.http_build_query(self::pagetypeAndPlatformToParams($pagetype, $platform, $browser));
        }
        return $url;
    }

    public static function getServerArgs($args) {
        if (self::hasNativePlatform()) {
            return '__KUROGO_MODULE_EXTRA_ARGS__';
        } else {
            return http_build_query($args);
        }
    }
    
    public static function getServerTimeout() {
        return Kurogo::getOptionalSiteVar('WEB_BRIDGE_AJAX_TIMEOUT', 30);
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
        return isset($_GET[self::AJAX_PARAMETER]) && $_GET[self::AJAX_PARAMETER];
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
        unset($args[self::AJAX_PARAMETER]);
    }
    
    //
    // Rewriting links
    //
    
    public static function getInternalLink($id, $page, $args=array()) {
        if (!$page) { $page = 'index'; }
        
        return self::BRIDGE_URL_INTERNAL_LINK."$id/$page".
            ($args ? '?'.http_build_query($args) : '');
    }
    
    public static function redirectTo($url) {
        echo '<script type="text/javascript">window.location = "'.$url.'";</script>';
        exit;
    }
    
    //
    //  Payload calls
    //
    
    public static function getAssetsPath() {
        return 'media/web_bridge';
    }
    
    public static function getAssetsDir() {
        return 'media'.DIRECTORY_SEPARATOR.'web_bridge';
    }
    
    public static function getAssetsConfiguration($module) {
        $info = array();
        $files = glob(WEB_BRIDGE_DIR."/*/$module.zip");
        if ($files) {
            foreach ($files as $file) {
                $parts = explode(DIRECTORY_SEPARATOR, dirname($file));
                if (!$parts) { continue; }

                $platform = end($parts);
                $contents = file_get_contents($file);
                if (!$platform || !$contents) { continue; }

                $info[$platform] = array(
                    'md5' => md5($contents),
                    'url' => FULL_URL_PREFIX.self::getAssetsPath()."/$platform/$module.zip",
                );
                
                $tabletFile = dirname($file)."/$module-tablet.zip";
                if (file_exists(dirname($file)."/$module-tablet.zip")) {
                    $tabletContents = file_get_contents($file);
                    if ($tabletContents) {
                        $info["$platform-tablet"] = array(
                            'md5' => md5($tabletContents),
                            'url' => FULL_URL_PREFIX.self::getAssetsPath()."/$platform/$module-tablet.zip",
                        );
                    }
                }
            }
        }
        return $info ? $info : null;
    }
}
