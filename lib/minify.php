<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package Minify
 */
 
//
// Handle CSS and Javascript a little differently:
//

// CSS supports overrides so include all available CSS files
// Javascript overrides appear to work for all the platforms we support
function getFileConfigForDirs($ext, $extFolder, $page, $pagetype, $platform, $browser, $dirs, $subDirs, $pageOnly=false) {
    $config = array(
        'include' => 'all',
        'files' => array()
    );
    
    foreach ($dirs as $dir) {
        foreach ($subDirs as $subDir) {
            $fullDir = "{$dir}{$subDir}/{$extFolder}/";
            
            if (!$pageOnly) {
                $commonFiles = array_reverse(DeviceClassifier::buildFileSearchList($pagetype, $platform, $browser, '', $ext, $fullDir));
                $config['files'] = array_merge($config['files'], $commonFiles);
            }
            
            $pageFiles = array_reverse(DeviceClassifier::buildFileSearchList($pagetype, $platform, $browser, $page, $ext, $fullDir));
            $config['files'] = array_merge($config['files'], $pageFiles);
        }
    }
    
    return $config;
}

function buildFileList($checkFiles) {
    $foundFiles = array();
  
    foreach ($checkFiles['files'] as $entry) {
        if (is_array($entry)) {
            $foundFiles = array_merge($foundFiles, buildFileList($entry));
        } else if ($entry && Watchdog::safePath($entry)) {
            $foundFiles[] = $entry;
        }
        if ($checkFiles['include'] == 'any' && count($foundFiles)) {
            break;
        }
    }
    
    return $foundFiles;
}

function getMinifyGroupsConfig() {
    $minifyConfig = array();
    
    $key = $_GET['g'];
    
    //
    // Check for specific file request
    //
    if (strpos($key, MIN_FILE_PREFIX) === 0) {
        // file path relative to either templates or the theme (check theme first)
        $path = substr($key, strlen(MIN_FILE_PREFIX));
        
        $config = array(
            'include' => 'all',
            'files' => array(
                THEME_DIR.$path,
                SITE_APP_DIR.$path,
                SHARED_APP_DIR.$path,
                APP_DIR.$path,
            ),
        );
        
        return array($key => buildFileList($config));
    }
    
    //
    // Page request
    //
    $pageOnly = isset($_GET['pageOnly']) && $_GET['pageOnly'];
    
    // if this is a copied module also pull in files from that module
    $configModule = isset($_GET['config']) ? $_GET['config'] : '';
  
    list($ext, $module, $page, $pagetype, $platform, $browser, $pathHash) = explode('-', $key);
  
    $cache = new DiskCache(CACHE_DIR.'/minify', Kurogo::getOptionalSiteVar('MINIFY_CACHE_TIMEOUT', 30), true);
    $cacheName = "group_$key";
    if ($configModule) {
        $cacheName .= "-$configModule";
    }
    if ($pageOnly) {
        $cacheName .= "-pageOnly";
    }
    
    if ($cache->isFresh($cacheName)) {
        $minifyConfig = $cache->read($cacheName);
      
    } else {
        $dirs = array(
            APP_DIR, 
            SHARED_APP_DIR,
            SITE_APP_DIR,
            THEME_DIR,
        );
        
        if ($pageOnly || (($pagetype=='tablet' || $platform=='computer') && in_array($module, array('info', 'admin')))) {
            // Info module does not inherit from common files
            $subDirs = array(
                '/modules/'.$module
            );
        } else {
            $subDirs = array(
                '/common',
                '/modules/'.$module,
            );
        }
        
        if ($configModule) {
            $subDirs[] = '/modules/' . $configModule;
        }
    
        $checkFiles = array(
            'css' => getFileConfigForDirs('css', 'css', 
                $page, $pagetype, $platform, $browser, $dirs, $subDirs, $pageOnly),
            'js'  => getFileConfigForDirs('js', 'javascript', 
                $page, $pagetype, $platform, $browser, $dirs, $subDirs, $pageOnly),
        );
        //error_log(print_r($checkFiles, true));
        
        $minifyConfig[$key] = buildFileList($checkFiles[$ext]);
        //error_log(__FUNCTION__."($pagetype-$platform-$browser) scanned filesystem for $key");
    
        $cache->write($minifyConfig, $cacheName);
    }
    
    // Add minify source object for the theme config.ini
    if ($ext == 'css') {
        $themeVarsFile = realpath_exists(THEME_DIR.'/config.ini');
        if ($themeVarsFile) {
            $minifyConfig[$key][] = new Minify_Source(array(
                'id' => 'themeConfigModTimeChecker',
                'getContentFunc' => 'minifyThemeConfigModTimeCheckerContent',
                'minifier' => '', // don't compress
                'contentType' => Minify::TYPE_CSS,
                'lastModified' => filemtime($themeVarsFile),
            ));
        }
    }
    
    //error_log(__FUNCTION__."($pagetype-$platform-$browser) returning: ".print_r($minifyConfig, true));
    return $minifyConfig;
}

function minifyThemeConfigModTimeCheckerContent() {
    return '';
}

function minifyGetThemeVars() {
    static $themeVars = null;
    
    if (!isset($themeVars)) {
        $config = ConfigFile::factory('config', 'theme', ConfigFile::OPTION_CREATE_EMPTY);
        
        $pagetype = Kurogo::deviceClassifier()->getPagetype();
        $platform = Kurogo::deviceClassifier()->getPlatform();
        $browser  = Kurogo::deviceClassifier()->getBrowser();
        $sections = array(
            'common',
            $pagetype,
            "$pagetype-$platform",
            "$pagetype-$platform-$browser",
        );
        
        $themeVars = array();
        foreach ($sections as $section) {
            if ($sectionVars = $config->getOptionalSection($section)) {
                $themeVars = array_merge($themeVars, $sectionVars);
            }
        }
    }
    
    return $themeVars;
}

function minifyThemeVarReplace($matches) {
    $themeVars = minifyGetThemeVars();
    if (isset($themeVars, $themeVars[$matches[1]])) {
        return $themeVars[$matches[1]];
    } else {
        Kurogo::log(LOG_WARNING, "theme variable '{$matches[1]}' not set", 'minify');
        return $matches[0]; // variable not set, do nothing
    }
}

function minifyPostProcess($content, $type) {
  if ($type === Minify::TYPE_CSS) {
      $urlPrefix = URL_PREFIX;
            
      if (Kurogo::getSiteVar('DEVICE_DEBUG') && URL_PREFIX == URL_BASE) {
          // if device debugging is on, always append device classification
          $urlPrefix .= 'device/'.Kurogo::deviceClassifier()->getDevice().'/';
      }
      // Theme variable replacement
      $themeVars = minifyGetThemeVars();
      if ($themeVars) {
          $content = preg_replace_callback(';@@@([^@]+)@@@;', 'minifyThemeVarReplace', $content);
      }
  
      $content = "/* Adding url prefix '".$urlPrefix."' */\n\n".
          preg_replace(';url\("?\'?/([^"\'\)]+)"?\'?\);', 'url("'.$urlPrefix.'\1")', $content);
  }
  
  return $content;
}
