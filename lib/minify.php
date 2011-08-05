<?php
/**
 * @package Minify
 */
 
//
// Handle CSS and Javascript a little differently:
//

// CSS supports overrides so include all available CSS files.
function getCSSFileConfigForDirs($page, $pagetype, $platform, $dirs, $subDirs, $pageOnly=false) {
  $config = array(
    'include' => 'all',
    'files' => array()
  );
  
  foreach ($dirs as $dir) {
    foreach ($subDirs as $subDir) {
      if (!$pageOnly) {
        $config['files'][] = "$dir$subDir/css/common.css";
        $config['files'][] = "$dir$subDir/css/$pagetype.css";
        $config['files'][] = "$dir$subDir/css/$pagetype-$platform.css"; 
      }
      $config['files'][] = "$dir$subDir/css/$page-common.css";
      $config['files'][] = "$dir$subDir/css/$page-$pagetype.css";
      $config['files'][] = "$dir$subDir/css/$page-$pagetype-$platform.css"; 
    }
  }
  return $config;
}

// Javascript overrides appear to work for all the platforms we support
// Code to do overrides commented out below
function getJSFileConfigForDirs($page, $pagetype, $platform, $dirs, $subDirs, $pageOnly=false) {
  $config = array(
    'include' => 'all',
    'files' => array()
  );
  
  foreach ($dirs as $dir) {
    foreach ($subDirs as $subDir) {
      if (!$pageOnly) {
        $config['files'][] = "$dir$subDir/javascript/common.js";
        $config['files'][] = "$dir$subDir/javascript/$pagetype.js";
        $config['files'][] = "$dir$subDir/javascript/$pagetype-$platform.js";
      }
      $config['files'][] = "$dir$subDir/javascript/$page-common.js";
      $config['files'][] = "$dir$subDir/javascript/$page-$pagetype.js";
      $config['files'][] = "$dir$subDir/javascript/$page-$pagetype-$platform.js";
    }
  }
  /*
  foreach ($subDirs as $subDir) {
    $files = array(
      "common.js",
      "$pagetype-$platform.js",
      "$pagetype.js",
    );
    
    foreach ($files as $file) {
      if (!$pageOnly) {
        $dirConfig = array(
          'include' => 'any',
          'files' => array()
        );
        foreach ($dirs as $dir) {
          $dirConfig['files'][] = "$dir$subDir/javascript/$file";
        }
      }
      $config['files'][] = $dirConfig;
      
      $dirConfig = array(
        'include' => 'any',
        'files' => array()
      );
      foreach ($dirs as $dir) {
        $dirConfig['files'][] = "$dir$subDir/javascript/$page-$file";
      }
      $config['files'][] = $dirConfig;
    }
  }
  */
  return $config;
}

function buildFileList($checkFiles) {
  $foundFiles = array();

  foreach ($checkFiles['files'] as $entry) {
    if (is_array($entry)) {
      $foundFiles = array_merge($foundFiles, buildFileList($entry));
    } else if (realpath_exists($entry)) { 
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

  list($ext, $module, $page, $pagetype, $platform, $pathHash) = explode('-', $key);

  $cache = new DiskCache(CACHE_DIR.'/minify', 30, true);
  $cacheName = "group_$key";
  if ($configModule) {
    $cacheName .= "-$configModule";
  }
  
  if ($cache->isFresh($cacheName)) {
    $minifyConfig = $cache->read($cacheName);
    
  } else {
    $dirs = array(
      APP_DIR, 
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
      'css' => getCSSFileConfigForDirs(
          $page, $pagetype, $platform, $dirs, $subDirs, $pageOnly),
      'js'  => getJSFileConfigForDirs (
          $page, $pagetype, $platform, $dirs, $subDirs, $pageOnly),
    );
    //error_log(print_r($checkFiles, true));
    
    $minifyConfig[$key] = buildFileList($checkFiles[$ext]);
    //error_log(__FUNCTION__."($pagetype-$platform) scanned filesystem for $key");

    $cache->write($minifyConfig, $cacheName);
  }
  
  //error_log(__FUNCTION__."($pagetype-$platform) returning: ".print_r($minifyConfig, true));
  return $minifyConfig;
}

function minifyPostProcess($content, $type) {
  if ($type === Minify::TYPE_CSS) {
    $urlPrefix = URL_PREFIX;
          
    if (Kurogo::getSiteVar('DEVICE_DEBUG') && URL_PREFIX == URL_BASE) {
      // if device debugging is on, always append device classification
      $urlPrefix .= 'device/'.Kurogo::deviceClassifier()->getDevice().'/';
    }

    $content = "/* Adding url prefix '".$urlPrefix."' */\n\n".
      preg_replace(';url\("?\'?/([^"\'\)]+)"?\'?\);', 'url("'.$urlPrefix.'\1")', $content);
  }
  
  return $content;
}

