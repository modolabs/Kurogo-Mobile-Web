<?php

class KurogoNativeTemplates
{
    protected $platform = 'unknown';
    protected $module = 'unknown';
    protected $page = 'index';
    protected $path = '';
    protected $pathExists = false;
    protected $streamContext = null;
    
    const INTERNAL_LINK_SCHEME = 'kurogo://';
    
    // This global could be removed by using closures (ie php 5.3+)
    static protected $currentInstance = null;
    protected $processingHTML = true;
    
    public function __construct($platform, $module, $dir=null) {
        $platformToUserAgent = self::getNativeUserAgents();
        if (isset($platformToUserAgent[$platform])) {
            $this->platform = $platform;
        }
        
        $this->module = $module;
        $this->path = ($dir ? rtrim($dir, '/') : CACHE_DIR.'/nativeBuild')."/$module";
        
        $this->streamContext = stream_context_create(array(
            'http' => array(
                'user_agent' => self::getNativeUserAgentForPlatform($this->platform),
            ),
        ));
    }
    
    public function setPage($page) {
        $this->page = $page;
    }
    
    protected static function isFileAsset($file) {
        $parts = explode('.', $file);
        $ext = strtolower(end($parts));
        return count($parts) > 1 && in_array($ext, array('png', 'gif', 'jpg', 'jpeg'));
    }
    
    protected static function isHTMLFile($file) {
        $parts = explode('.', $file);
        $ext = strtolower(end($parts));
        return count($parts) < 2 || in_array($ext, array('html', 'php'));        
    }
    
    //
    // Helper functions to avoid code duplication
    //
    protected function getAsset($urlSuffix) {
        $url = FULL_URL_PREFIX.$urlSuffix;
        //error_log($url);
        $contents = @file_get_contents($url, false, $this->streamContext);
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
        return strtr(preg_replace(
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
                $this->page.'-min.$1',
                '/',
                '$1_',
                '',
            ),
            ltrim($urlSuffix, '/')
        ), '/', '-');
    }
    
    protected static function getPartsForMatches($matches) {
        $urlSuffix = html_entity_decode($matches[4]);
        $file = self::$currentInstance->urlSuffixToFile($urlSuffix);
        
        if ($file) {
            $replacement = $matches[1];
            if (self::$currentInstance->processingHTML) {
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
            self::$currentInstance->saveContentAndAssets($urlSuffix, $file, self::isHTMLFile($file));
        }
        
        return $replacement;
    }
    
    //
    // Template and asset generation functions
    //

    protected function _rewriteURLsToFilePaths($contents, $isHTML=true, $callback='rewriteURLsToFilePathsCallback') {
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
        $oldProcessingHTML = $this->processingHTML;
        $this->processingHTML = $isHTML;
        self::$currentInstance = $this;
        $contents = preg_replace_callback(
            ';'.
                '(\'|\\\"|\"|\()'.
                '('.
                    '('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).')'.
                    '([^\'\"\\\)]+)'.
                ')'.
                '(\'|\\\"|\"|\))'.
            ';', 
            array(get_class(), $callback), 
            $contents
        );
        $this->processingHTML = $oldProcessingHTML; // restore state since saveContentAndAssets is called recursively
        
        return $contents;
    }
    
    protected function saveContentAndAssets($urlSuffix=null, $file=null, $isHTML=true) {
        if (!$urlSuffix) {
            $urlSuffix = "{$this->module}/{$this->page}";
        }
        if (!$file) {
            $file = "{$this->page}.html";
        }
        
        $filePath = "{$this->path}/$file";
        
        $contents = $this->getAsset($urlSuffix);
        if ($contents) {
            if (!self::isFileAsset($file)) {
                self::$currentInstance = $this;
                $contents = $this->_rewriteURLsToFilePaths($contents, $isHTML, 'saveContentAndAssetsCallback');
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
        $contents = $this->getAsset("{$this->module}/{$this->page}?nativeAssetCheck=1&ajax=1");
        if ($contents) {
            self::$currentInstance = $this;
            $contents = $this->_rewriteURLsToFilePaths($contents, true, 'saveContentAndAssetsCallback');
        }
    }
    
    //
    // Detecting native user agents
    //
    
    private static function getNativeUserAgents() {
        static $nativeBuildUserAgents = null;
        if (!isset($nativeBuildUserAgents)) {
            $nativeBuildUserAgents = array(
                'iphone'        => '',
                'ipad'          => '',
                'android'       => '',
                'androidtablet' => '',
                'unknown'       => '',
            );
            foreach ($nativeBuildUserAgents as $platform => $userAgent) {
                $nativeBuildUserAgents[$platform] = "Kurogo (native-$platform)";
            }
        }
      
        return $nativeBuildUserAgents;
    }
    
    private static function getNativeUserAgentForPlatform($platform) {
        $platformToUserAgent = self::getNativeUserAgents();
        if (isset($platformToUserAgent[$platform])) {
            return $platformToUserAgent[$platform];
        }
        return $platformToUserAgent['unknown'];
    }
    
    public static function isNativeUserAgent($userAgent, &$platform) {
        $userAgentToPlatform = array_flip(self::getNativeUserAgents());
        if (isset($userAgentToPlatform[$userAgent])) {
            $platform = $userAgentToPlatform[$userAgent];
            return true;
        }
        return false;
    }
    
    public static function isNativeCall() {
        return Kurogo::deviceClassifier()->getPageType() == 'native';
    }

    // Note: this gets called before the device classifier is initialized
    // We cannot reliably set the user agent in javascript so use a special get parameter
    public static function isNativeContentCall(&$platform) {
        if (isset($_GET['nativePlatform'], $_GET['ajax']) && $_GET['nativePlatform'] && $_GET['ajax']) {
            $platform = $_GET['nativePlatform'];
            return true;
        }
        return false;
    }
    
    public static function isNativeTemplateCall() {
        return Kurogo::deviceClassifier()->getPageType() == 'native' && 
            (!isset($_GET['ajax']) || !$_GET['ajax']);
    }
    
    // This is used to check template pages for inline images
    public static function isNativeInlineAssetCall() {
        return //Kurogo::deviceClassifier()->getPageType() == 'native' && 
            isset($_GET['nativeAssetCheck']) && $_GET['nativeAssetCheck'];
    }
}
