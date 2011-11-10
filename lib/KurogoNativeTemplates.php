<?php

class KurogoNativeTemplates
{
    protected $platform = 'unknown';
    protected $module = 'unknown';
    protected $page = 'index';
    protected $path = '';
    protected $pathExists = false;
    protected $streamContext = null;
    protected $preg_replace_callback_re = null;
    protected $preg_replace_patterns = null;
    protected $preg_replace_replacements = null;
    
    const INTERNAL_LINK_SCHEME = 'kurogo://';
    
    // This global could be removed by using closures (ie php 5.3+)
    static protected $currentInstance = null;
    
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

        $this->preg_replace_callback_re = 
            ';'.
                '(\'|\\\"|\"|\()'.
                '('.
                    '('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).'|\.\./)'.
                    '([^\'\"\\\)]+)'.
                ')'.
                '(\'|\\\"|\"|\))'.
            ';';

        $this->preg_replace_patterns = array(
            '@device/native-[^/]+/@',
            '@^min/\?g=file-/([^&]+)(&.+|)$@',
            '@^min/g=([^-]+)-([^&]+)(&.+|)$@',
            '@/(images|javascript|css)/@',
            '@^modules/([^/]+)/@',
            '@^common/@',
        );

        // Sets preg_replace_replacements
        $this->setPage($this->page);
    }
    
    public function setPage($page) {
        $this->page = $page;
        
        // Always overwrite because it depends on the page
        $this->preg_replace_replacements = array(
            '',
            '$1',
            $this->page.'-min.$1',
            '/',
            '$1_',
            '',
        );
    }
    
    protected static function isFileAsset($file) {
        $parts = explode('.', $file);
        $ext = strtolower(end($parts));
        return count($parts) > 1 && in_array($ext, array('png', 'gif', 'jpg', 'jpeg'));
    }
    
    // Avoid code duplication between rewriteURLsToFilePathsCallback and saveContentAndAssetsCallback
    protected static function getPartsForMatches($matches) {
        $urlSuffix = html_entity_decode($matches[4]);
        
        $file = strtr(preg_replace(
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
                self::$currentInstance->page.'-min.$1',
                '/',
                '$1_',
                '',
            ),
            $urlSuffix
        ), '/', '-');
        
        
        if ($file) {
            $replacement = $matches[1].'modules/'.self::$currentInstance->module.'/'.$file.$matches[5];
        } else {
            Kurogo::log(LOG_NOTICE, "Unable to determine file name for '{$matches[0]}'", 'api');
            $replacement = $matches[0];
        }
        
        return array($urlSuffix, $file, $replacement);
    }
    
    protected static function rewriteURLsToFilePathsCallback($matches) {
        list($urlSuffix, $file, $replacement) = self::getPartsForMatches($matches);

        return $replacement;
    }
    
    protected static function saveContentAndAssetsCallback($matches) {
        list($urlSuffix, $file, $replacement) = self::getPartsForMatches($matches);

        if ($file) {
            self::$currentInstance->saveContentAndAssets($urlSuffix, $file);
        }
        
        return $replacement;
    }

    public function rewriteURLsToFilePaths($contents, $preg_replace_callback='rewriteURLsToFilePathsCallback') {
        // This global could be removed by using closures (ie php 5.3+)
        self::$currentInstance = $this;

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
            '@(<form\s+[^>]*action=")([^"]+)(")@',
            '$1'.self::INTERNAL_LINK_SCHEME.$this->module.'/$2$3',
            $contents
        );
        

        // rewrite all other internal urls
        $contents = preg_replace_callback(
            ';'.
                '(\'|\\\"|\"|\()'.
                '('.
                    '('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).')'.
                    '([^\'\"\\\)]+)'.
                ')'.
                '(\'|\\\"|\"|\))'.
            ';', 
            array(get_class(), $preg_replace_callback), 
            $contents
        );
        return $contents;
    }
    
    public function saveContentAndAssets($urlSuffix=null, $file=null) {
        if (!$urlSuffix) {
            $urlSuffix = "{$this->module}/{$this->page}";
        }
        if (!$file) {
            $file = "{$this->page}.html";
        }
        
        $filePath = "{$this->path}/$file";
        $url = FULL_URL_PREFIX.$urlSuffix;
        //error_log($url);
        $contents = @file_get_contents($url, false, $this->streamContext);
        if (!$contents) {
            Kurogo::log(LOG_NOTICE, "Failed to load asset $url", 'api');
            return;
        }
        
        if (!self::isFileAsset($file)) {
            // This global could be removed by using closures (ie php 5.3+)
            self::$currentInstance = $this;
    
            $contents = $this->rewriteURLsToFilePaths($contents, 'saveContentAndAssetsCallback');
        }
        
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

    // Note: this gets called before the device classifier is initialized
    // We cannot reliably set the user agent in javascript so use a special get parameter
    public static function isNativeContentCall(&$platform) {
        if (isset($_GET['nativePlatform'], $_GET['ajax']) && $_GET['nativePlatform'] && $_GET['ajax']) {
            $platform = $_GET['nativePlatform'];
            return true;
        }
        return false;
    }
}
