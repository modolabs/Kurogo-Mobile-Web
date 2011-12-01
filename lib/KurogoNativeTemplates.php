<?php

class KurogoNativeTemplates
{
    protected $platform = 'unknown';
    protected $module = 'unknown';
    protected $page = 'index';
    protected $path = '';
    protected $pathExists = false;
    
    const NATIVE_PLATFORM_PARAMETER = 'nativePlatform';
    const ASSET_CHECK_PARAMETER     = 'nativeAssetCheck';

    const INTERNAL_LINK_SCHEME = 'kgolink://';
    const CONFIG_LINK_SCHEME   = 'kgoconfig://';
    
    const FILE_TYPE_HTML       = 'html';
    const FILE_TYPE_CSS        = 'css';
    const FILE_TYPE_JAVASCRIPT = 'js';
    const FILE_TYPE_ASSET      = 'asset';

    // This global could be removed by using closures (ie php 5.3+)
    static protected $currentInstance = null;
    protected $processingFileType = self::FILE_TYPE_HTML;
    
    public function __construct($platform, $module, $dir=null) {
        $this->platform = $platform;
        $this->module = $module;
        $this->path = ($dir ? rtrim($dir, '/') : CACHE_DIR.'/nativeBuild')."/$module";
    }

    public function setPage($page) {
        $this->page = $page;
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
            http_build_query(array(self::NATIVE_PLATFORM_PARAMETER => $this->platform));
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
        return str_replace(
            array('/', '-'), // Android does not support hyphens in asset filenames
            array('_', '__'),
            preg_replace(
                array(
                    '@device/native-[^/]+/@',
                    '@^min/\?g=file-/([^&]+)(&.+|)$@',
                    '@^min/g=([^-]+)-([^&]+)(&.+|)$@',
                    '@/(images|javascript|css)/@',
                    '@^modules/([^/]+)/@',
                    '@^common/@',
                ),
                array(
                    '',
                    '$1',
                    $this->page.'_min.$1',
                    '/',
                    '$1_',
                    '',
                ),
                ltrim($urlSuffix, '/')
            )
        );
    }

    protected static function getPartsForMatches($matches) {
        $urlSuffix = html_entity_decode($matches[4]);
        $file = self::$currentInstance->urlSuffixToFile($urlSuffix);
        
        if ($file) {
            $replacement = $matches[1];
            if (self::$currentInstance->processingFileType == self::FILE_TYPE_HTML) {
                $replacement .= 'modules/'.self::$currentInstance->module.'/';
            }
            $replacement .= $file.$matches[5];
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
              '$1'.self::INTERNAL_LINK_SCHEME.'$2$3',
              '$1'.self::INTERNAL_LINK_SCHEME.$this->module.'/$2$3',
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
                '$1'.self::INTERNAL_LINK_SCHEME.'$3$4',
                '$1'.self::INTERNAL_LINK_SCHEME.$this->module.'/$2$3',
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

    public function saveAssets($assets) {
        foreach ($assets as $asset) {
            $contents = $this->getAsset($asset);
            $file = $this->urlSuffixToFile($asset);
            if ($contents && $file) {
                $this->saveAsset($contents, $file);
            }
        }
    }

    public function saveTemplatePage($page) {
        $this->setPage($page);
        $this->saveContentAndAssets();
        
        // Also check for inline content
        $contents = $this->getAsset("{$this->module}/{$this->page}?ajax=1&".self::ASSET_CHECK_PARAMETER.'=1');
        if ($contents) {
            self::$currentInstance = $this;
            $contents = $this->_rewriteURLsToFilePaths($contents, 'saveContentAndAssetsCallback', true);
        }
    }

    //
    // Config URL for setting native navbar options
    //

    public static function getNativePageConfigURL($pageTitle, $backTitle, $hasRefresh) {
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
        return self::CONFIG_LINK_SCHEME.'navbar/?'.http_build_query($params);
    }

    public static function getNativeURLBase() {
        if (self::hasNativePlatform()) {
            return '__KUROGO_URL_BASE__';
        } else {
            return URL_BASE;
        }
    }

    public static function getNativeServerURL() {
        if (self::hasNativePlatform()) {
            return '__KUROGO_SERVER_URL__';
        } else {
            return ltrim(FULL_URL_PREFIX, '/');
        }
    }

    public static function getNativeServerPath($id, $page) {
        if (self::shouldForceNativePlatform($platform)) {
            return "/{$id}/{$page}?ajax=1&nativePlatform={$platform}";
        } else {
            return "/{$id}/{$page}?ajax=1";
        }
    }

    public static function getNativeServerArgs($args) {
        if (self::hasNativePlatform()) {
            return '__KUROGO_MODULE_EXTRA_ARGS__';
        } else {
            return http_build_query($args);
        }
    }

    //
    // Detecting native user agents
    //

    private static function isAjax() {
      return isset($_GET['ajax']) && $_GET['ajax'];
    }

    // This is used to check template pages for inline images
    private static function isAssetCheck() {
      return isset($_GET[self::ASSET_CHECK_PARAMETER]) && $_GET[self::ASSET_CHECK_PARAMETER];
    }
    
    private static function hasNativePlatform() {
      return isset($_GET[self::NATIVE_PLATFORM_PARAMETER]) && $_GET[self::NATIVE_PLATFORM_PARAMETER];
    }

    // Note: this gets called before the device classifier is initialized
    // We cannot reliably set the user agent in javascript so use a special get parameter
    public static function shouldForceNativePlatform(&$platform) {
        if (self::hasNativePlatform()) {
            $platform = $_GET[self::NATIVE_PLATFORM_PARAMETER];
            return true;
        }
        return false;
    }
    
    public static function isNativeCall() {
        return Kurogo::deviceClassifier()->getPagetype() == 'native' || self::hasNativePlatform();
    }
    
    public static function useNativeTemplateInitializer() {
        return self::isNativeCall() && (!self::isAjax() || self::isAssetCheck());
    }
    
    public static function useNativeTemplateWrapper() {
        return self::isNativeCall() && !self::isAjax();
    }
    
    public static function shouldRewriteAssetPaths() {
        return self::hasNativePlatform() && self::isAjax() && !self::isAssetCheck();
    }
    
    public static function shouldRewriteInternalLinks() {
        return self::hasNativePlatform();
    }
    
    //
    // Rewriting links
    //
    
    public static function getInternalLink($id, $page, $args=array()) {
        if (!$page) { $page = 'index'; }
        
        return self::INTERNAL_LINK_SCHEME."$id/$page".
            ($args ? '?'.http_build_query($args) : '');
    }
}
