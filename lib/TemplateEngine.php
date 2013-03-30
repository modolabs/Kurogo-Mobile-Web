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
  * @package Core
  */

/**
  */
require_once realpath(LIB_DIR.'/smarty/Smarty.class.php');

/**
  * @package Core
  */
class TemplateEngine extends Smarty {
    protected $accessKey = 0;
    protected $extendsTrackerCurrentInclude = '';
    protected $extendsTrackerSeenFiles = array();
    
    //
    // findInclude resource type
    //
    // Implementing this as a resource allows us to evaluate variables in the 
    // findInclude statement at runtime. We'd also do this with findExtends but
    // there's no way to track which files you've checked already in the 
    // resource implementation.
    //
    
    protected function getIncludeFile($name) {
        $subDir = dirname($name);
        $page = basename($name, '.tpl');
        
        $pagetype = Kurogo::deviceClassifier()->getPagetype();
        $platform = Kurogo::deviceClassifier()->getPlatform();
        $browser  = Kurogo::deviceClassifier()->getBrowser();

        if (strlen($subDir)) { $subDir .= '/'; }
        
        $checkDirs = array(
            'THEME_DIR'      => THEME_DIR,
            'SITE_APP_DIR'   => SITE_APP_DIR,
            'SHARED_APP_DIR' => SHARED_APP_DIR,
            'APP_DIR'        => APP_DIR,
        );
        
        $searchFiles = DeviceClassifier::buildFileSearchList($pagetype, $platform, $browser, $page, 'tpl');
        
        foreach ($searchFiles as $file) {
            foreach ($checkDirs as $type => $dir) {
                if ($dir) {
                    $test = realpath_exists("$dir/$subDir$file");
                    if ($test) {
                        Kurogo::log(LOG_DEBUG, __FUNCTION__."($pagetype-$platform-$browser) choosing '$type/$file' for '$name'", 'template');
                        return addslashes($test);
                    }
                }
            }
        }
        return $name;
    }
    
    public function smartyResourceIncludeGetSource($name, &$source, $smarty) {
        $file = $this->getIncludeFile($name);
        if ($file !== false) {
            $source = file_get_contents($file);
            return true;
        }
        return false;
    }

    public function smartyResourceIncludeGetTimestamp($name, &$timestamp, $smarty) {
        $file = $this->getIncludeFile($name);
        if ($file !== false) {
            $timestamp = filemtime($file);
            return true;
        }
        return false;
    }

    public function smartyResourceIncludeGetSecure($name, $smarty) {
        return true;
    }

    public function smartyResourceIncludeGetTrusted($name, $smarty) {
        return true;
    }
    
    //
    // Extends file tracking
    //
    
    // The following functions track {extends} files used for device templates.
    // The purpose is to allow us to have extends relationships between files 
    // with the same name in different directories, but to be able to know
    // which directories have already been used so we don't loop.
    //
    // Fortunately for us, Smarty handles each extends chain as a unit
    // keeping the template resource_name the name of the include file 
    // throughout the entire process.
    //
    // So our basic technique is to track all the files used for a given 
    // resource_name and to toss the array either when the resource_name file
    // changes, when we leave the template via the postfilter below.

    
    protected function extendsTrackerReset($templateToMatch=null) {
        if (!$templateToMatch || $this->extendsTrackerUsingTemplate($templateToMatch)) {
            Kurogo::log(LOG_DEBUG, 'RESETTING TRACKER'.($this->extendsTrackerCurrentInclude ? " (old include {$this->extendsTrackerCurrentInclude})" : ''), 'template');
            $this->extendsTrackerCurrentInclude = '';
            $this->extendsTrackerSeenFiles = array();
        }
    }
    
    protected function extendsTrackerCheckTemplate($template) {
        if ($this->extendsTrackerResourceIsFile($template) && !$this->extendsTrackerUsingTemplate($template)) {
            Kurogo::log(LOG_DEBUG, "RESETTING TRACKER (new include {$template->resource_name})", 'template');
            $this->extendsTrackerCurrentInclude = $template->resource_name;
            $this->extendsTrackerSeenFiles = array();
            
            $this->extendsTrackerAddFile($template->resource_name);
        }
    }
    
    protected function extendsTrackerUsingTemplate($template) {
        return $this->extendsTrackerResourceIsFile($template) && 
            $template->resource_name == $this->extendsTrackerCurrentInclude;
    }
    
    protected function extendsTrackerResourceIsFile($template) {
        return $template->resource_type == 'file' || 
               $template->resource_type == 'findInclude';
    }
    
    protected function extendsTrackerSeenFile($file) {
        return isset($this->extendsTrackerSeenFiles[$file]);
    }
    
    protected function extendsTrackerAddFile($file) {
        Kurogo::log(LOG_DEBUG, "ADDING TO TRACKER -- {$file}", 'template');
        $this->extendsTrackerSeenFiles[$file] = true;
    }

    //
    // Finding extends files
    //
    
    protected function getExtendsFile($name, $template) {
        $pagetype = Kurogo::deviceClassifier()->getPagetype();
        $platform = Kurogo::deviceClassifier()->getPlatform();
        $browser  = Kurogo::deviceClassifier()->getBrowser();
        
        $checkDirs = array(
            'THEME_DIR'      => THEME_DIR,
            'SITE_APP_DIR'   => SITE_APP_DIR,
            'SHARED_APP_DIR' => SHARED_APP_DIR,
            'APP_DIR'        => APP_DIR,
        );
                
        foreach ($checkDirs as $type => $dir) {
            if ($dir) {
                $test = realpath_exists("$dir/$name");
                if ($test && !$this->extendsTrackerSeenFile($test)) {
                    Kurogo::log(LOG_DEBUG, __FUNCTION__."($pagetype-$platform-$browser) choosing     '$type/$name' for '$name'", 'template');
                    $this->extendsTrackerAddFile($test);
                    return addslashes($test);
                }
            }
        }
        return false;
    }
    
    //
    // Prefilter to map include and extend directives to real files
    //
    
    protected function replaceVariables($string, $variables) {
        $search = array();
        $replace = array();

        // TODO: fix this so it doesn't match on single { or }
        if (preg_match_all(';{?\$([A-za-z_]\w*)}?;', $string, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[1] as $i => $variable) {
                if (isset($variables[$variable]) && is_string($variables[$variable])) {
                    $search[] = $matches[0][$i];
                    $replace[] = $variables[$variable];
                }
            }
        }
        return $search ? str_replace($search, $replace, $string) : $string;
    }
    
    public function smartyPrefilterHandleExtends($source, $template) {
        $this->extendsTrackerCheckTemplate($template);
        
        $variables = $this->getTemplateVars();
        
        $search = array();
        $replace = array();
        if (preg_match_all(';=\s*"findExtends:([^"]+)";', $source, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[1] as $i => $name) {
                $path = $this->getExtendsFile($this->replaceVariables($name, $variables), $template);
                if ($path) {
                    $search[] = $matches[0][$i];
                    $replace[] = '="file:'.$path.'"';
                    Kurogo::log(LOG_DEBUG, __FUNCTION__." replacing extends $name with $path", 'template');
                } else {
                    Kurogo::log(LOG_WARNING, __FUNCTION__." FAILED to find EXTENDS for $name", 'template');
                    throw new SmartyException("Unable to load template \"findExtends : $name\"");
                }
            }
        }
        
        return $search ? str_replace($search, $replace, $source) : $source;
    }
    
    //
    // Filter to attempt to remove XSS injection attacks without removing HTML
    //
    public function smartyModifierSanitizeHTML($string, $allowedTags='editor') {
        return Sanitizer::sanitizeHTML($string, $allowedTags);
    }
    
    //
    // Filter to remove javascript from urls
    // Assumes URL is dumped into href or src attr as-is
    //
    public function smartyModifierSanitizeURL($string) {
        return Sanitizer::sanitizeURL($string);
    }
    
    //
    // Postfilter to detect when we are leaving an extends dependency chain
    //

    public function smartyPostfilterHandleExtends($source, $template) {
        $this->extendsTrackerReset($template);
        return $source;
    }
    
    protected function stripWhitespaceReplace($search, $replace, &$subject) {
        $len = strlen($search);
        $pos = 0;
        for ($i = 0, $count = count($replace); $i < $count; $i++) {
            if (($pos = strpos($subject, $search, $pos)) !== false) {
                $subject = substr_replace($subject, $replace[$i], $pos, $len);
            } else {
                break;
            }
        }
    }
    
    public function smartyOutputfilterAddURLPrefixAndStripWhitespace($source, $smarty) {
        // rewrite urls for the device classifier in case  our root is not / 
        // also handles debugging mode for paths without hostnames
        $source = preg_replace(
            ';(<[^>]+)(url\("?\'?|href\s*=\s*"|src\s*=\s*"|action\s*=\s*")('.URL_PREFIX.'|'.URL_DEVICE_DEBUG_PREFIX.'|/);', '\1\2'.URL_PREFIX, $source);
        
        if (Kurogo::getSiteVar('DEVICE_DEBUG')) {
            // if we are in debugging mode we need to also rewrite full paths with hostnames
            $source = preg_replace(
                ';(<[^>]+)(url\("?\'?|href\s*=\s*"|src\s*=\s*")('.FULL_URL_PREFIX.'|'.FULL_URL_BASE.');', '\1\2'.FULL_URL_PREFIX, $source);
        }
        
        // Most of the following code comes from the trimwhitespace filter:
        
        // Pull out the script blocks
        preg_match_all("!<script[^>]*?>.*?</script>!is", $source, $match);
        $scriptBlocks = $match[0];
        $source = preg_replace("!<script[^>]*?>.*?</script>!is", '@@@SMARTY:TRIM:SCRIPT@@@', $source);
        
        // Pull out the pre blocks
        preg_match_all("!<pre[^>]*?>.*?</pre>!is", $source, $match);
        $preBlocks = $match[0];
        $source = preg_replace("!<pre[^>]*?>.*?</pre>!is", '@@@SMARTY:TRIM:PRE@@@', $source);
        
        // Pull out the textarea blocks
        preg_match_all("!<textarea[^>]*?>.*?</textarea>!is", $source, $match);
        $textareaBlocks = $match[0];
        $source = preg_replace("!<textarea[^>]*?>.*?</textarea>!is", '@@@SMARTY:TRIM:TEXTAREA@@@', $source);
        
        // remove all leading spaces, tabs and carriage returns NOT
        // preceeded by a php close tag.
        $source = trim(preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $source));
        
        // remove all newlines before and after tags.
        $source = preg_replace('/[\r\n]*(<[^>]+>)[\r\n]*/m', '\1', $source);
        
        // collapse whitespace before and after tags.
        $source = preg_replace(array('/\s+(<)/m', '/(>)\s+/s'), array(' \1', '\1 '), $source);

        // strip spaces around non-breaking spaces
        $source = preg_replace('/\s*&nbsp;\s*/m', '&nbsp;', $source);

        // restore textarea, pre, script and style blocks
        $this->stripWhitespaceReplace("@@@SMARTY:TRIM:TEXTAREA@@@", $textareaBlocks, $source);
        $this->stripWhitespaceReplace("@@@SMARTY:TRIM:PRE@@@", $preBlocks, $source);
        $this->stripWhitespaceReplace("@@@SMARTY:TRIM:SCRIPT@@@", $scriptBlocks, $source);
    
        if (KurogoWebBridge::shouldRewriteAssetPaths()) {
            // Need to rewrite Kurogo assets to use filenames used in native templates
            $rewriter = new KurogoWebBridge($smarty->getTemplateVars('configModule'));
            $source = $rewriter->rewriteURLsToFilePaths($source);
        }
        
        return $source;
    }
    
    //
    // Access key plugins
    //
    
    public function smartyBlockAccessKeyLink($params, $content, &$smarty, &$repeat) {
        if (empty($params['href'])) {
            Kurogo::log(LOG_WARNING, "assign: missing 'href' parameter", 'template');
        }
        
        $html = '';
        
        if (!$repeat) {
            $html = '<a href="'.$params['href'].'"';
            
            if (isset($params['class'])) {
                $html .= " class=\"{$params['class']}\"";
            }
            if (isset($params['id'])) {
                $html .= " id=\"{$params['id']}\"";
            }
            if ($this->accessKey < 10 && Kurogo::deviceClassifier()->getPlatform() != "blackberry") {
                $html .= ' accesskey="'.$this->accessKey.'">'.$this->accessKey.': ';
                $this->accessKey++;
            } else {
                $html .= '>';
            }
            $html .= $content.'</a>';
        }
        return $html;
    }
    
    public function smartyTemplateAccessKeyReset($params, &$smarty) {
        if (!isset($params['index'])) {
                Kurogo::log(LOG_WARNING, "assign: missing 'index' parameter", 'template');
                return;
        }
        if ($this->accessKey == 0 || (isset($params['force']) && $params['force'])) {
            $this->accessKey = $params['index'];
        }
    }
    
    //
    // Modifier to convert https urls to http
    //
    
    public function nosecure($string) {
        return str_replace('https://','http://',$string);
    }
    
    
    //
    // Constructor
    //
    
    function __construct() {
        parent::__construct();

        // Fix this in a later release -- currently generates lots of warnings
        $this->error_reporting = E_ALL & ~E_NOTICE;

        // Device info
        $pagetype = Kurogo::deviceClassifier()->getPagetype();
        $platform = Kurogo::deviceClassifier()->getPlatform();
        $browser  = Kurogo::deviceClassifier()->getBrowser();
        
        // Smarty configuration
        $this->setCompileDir (CACHE_DIR.'/smarty/templates');
        $this->setCacheDir   (CACHE_DIR.'/smarty/html');
        $this->setCompileId  ("$pagetype-$platform-$browser");
        
        $className = get_class($this);
        
        $this->registerPlugin('modifier', 'sanitize_html', array($this, 'smartyModifierSanitizeHTML'));
        $this->registerPlugin('modifier', 'sanitize_url', array($this, 'smartyModifierSanitizeURL'));
        
        // findInclude is a resource
        $this->registerResource('findInclude', array(
            array($this, 'smartyResourceIncludeGetSource'),
            array($this, 'smartyResourceIncludeGetTimestamp'),
            array($this, 'smartyResourceIncludeGetSecure'),
            array($this, 'smartyResourceIncludeGetTrusted')
        ));
        
        // findExtends is a pre and post filter
        $this->registerFilter('pre', array($this, 'smartyPrefilterHandleExtends'));
        $this->registerFilter('post', array($this, 'smartyPostfilterHandleExtends'));

        // Postfilter to add url prefix to absolute urls and
        // strip unnecessary whitespace (ignores <pre>, <script>, etc)
        $this->registerFilter('output', array($this, 'smartyOutputfilterAddURLPrefixAndStripWhitespace'));
        
        $this->registerPlugin('block', 'html_access_key_link', array($this, 'smartyBlockAccessKeyLink'));
        $this->registerPlugin('function', 'html_access_key_reset', array($this, 'smartyTemplateAccessKeyReset'));

        $this->registerPlugin('modifier', 'nosecure', array($this, 'nosecure'));
            
        // variables common to all modules
        $this->assign('pagetype', $pagetype);
        $this->assign('platform', $platform);
        $this->assign('browser',  $browser);
        $this->assign('deviceOverride', Kurogo::deviceClassifier()->getOverride());
        $this->assign('showDeviceDetection', Kurogo::getSiteVar('DEVICE_DETECTION_DEBUG'));
        $this->assign('moduleDebug', Kurogo::getSiteVar('MODULE_DEBUG'));
    }
    
    //
    // Display template for device and theme
    //
    
    function displayForDevice($page, $cacheID = null, $compileID = null) {
        $this->extendsTrackerReset();
    
        $this->display($this->getIncludeFile($page), $cacheID, $compileID);
    }
    
    //
    // Fetch template contents for device and theme
    //
    
    function fetchForDevice($page, $cacheID = null, $compileID = null) {
        $this->extendsTrackerReset();

        return $this->fetch($this->getIncludeFile($page), $cacheID, $compileID);
    }
}
