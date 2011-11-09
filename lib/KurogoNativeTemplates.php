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

        $this->setPage($this->page);
    }
    
    public function setPage($page) {
        $this->page = $page;
        
        // Set regular expressions, some of which contain the page name
        if (!$this->preg_replace_callback_re) {
            $this->preg_replace_callback_re = 
                ';'.
                    '(\'|\\\"|\"|\()'.
                    '('.
                        '('.preg_quote(FULL_URL_PREFIX).'|'.preg_quote(URL_PREFIX).'|\.\./)'.
                        '([^\'\"\\\)]+)'.
                    ')'.
                    '(\'|\\\"|\"|\))'.
                ';';
        }
        
        if (!$this->preg_replace_patterns) {
            $this->preg_replace_patterns = array(
                '@device/native-[^/]+/@',
                '@^min/\?g=file-/([^&]+)(&.+|)$@',
                '@^min/g=([^-]+)-([^&]+)(&.+|)$@',
                '@/(images|javascript|css)/@',
                '@^modules/([^/]+)/@',
                '@^common/@',
            );
        }
        
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
    
    protected function rewriteURLsToFilePathsCallback($matches) {
        $file = strtr(preg_replace(
            self::$currentInstance->preg_replace_patterns,
            self::$currentInstance->preg_replace_replacements,
            html_entity_decode($matches[4])
        ), '/', '-');
        
        if ($file) {
            return $matches[1].$file.$matches[5];
        }
        
        Kurogo::log(LOG_NOTICE, "Unable to determine file name for '{$matches[0]}'", 'api');
        return $matches[0];
    }

    public function rewriteURLsToFilePaths($contents) {
        // This global could be removed by using closures (ie php 5.3+)
        self::$currentInstance = $this;

        return preg_replace_callback(
            $this->preg_replace_callback_re, 
            array(get_class(), 'rewriteURLsToFilePathsCallback'), 
            $contents
        );
    }
    
    protected function saveContentAndAssetsCallback($matches) {
        $urlSuffix = html_entity_decode($matches[4]);
        $file = strtr(preg_replace(
            self::$currentInstance->preg_replace_patterns,
            self::$currentInstance->preg_replace_replacements,
            $urlSuffix
        ), '/', '-');
        
        if ($file) {
            $scanForAssets = false;
            $parts = explode('.', $file);
            $ext = strtolower(end($parts));
            $scanForAssets = count($parts) > 1 && in_array($ext, array('html', 'css', 'js'));
            
            self::$currentInstance->saveContentAndAssets($urlSuffix, $file, $scanForAssets);
            
            return $matches[1].$file.$matches[5];
        }
        
        Kurogo::log(LOG_NOTICE, "Unable to determine file name for '{$matches[0]}'", 'api');
        return $matches[0];
    }
    
    public function saveContentAndAssets($urlSuffix=null, $file=null, $scanForAssets=true) {
        if (!$urlSuffix) {
            $urlSuffix = "{$this->module}/{$this->page}";
        }
        if (!$file) {
            $file = "{$this->page}.html";
        }
        
        $filePath = "{$this->path}/$file";
        $url = FULL_URL_PREFIX.$urlSuffix;
        error_log($url);
        $contents = @file_get_contents($url, false, $this->streamContext);
        if (!$contents) {
            Kurogo::log(LOG_NOTICE, "Failed to load asset $url", 'api');
            return;
        }
        
        if ($scanForAssets) {
            // This global could be removed by using closures (ie php 5.3+)
            self::$currentInstance = $this;
    
            $contents = preg_replace_callback(
                $this->preg_replace_callback_re, 
                array(get_class(), 'saveContentAndAssetsCallback'), 
                $contents
            );
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
