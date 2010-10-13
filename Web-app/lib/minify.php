<?php

require_once LIB_DIR.'/DiskCache.php';
  
//
// Handle CSS and Javascript a little differently:
//

// CSS supports overrides so include all available CSS files.
function getCSSFileConfigForDirs($page, $pagetype, $platform, $dirs, $subDirs) {
  $config = array(
    'include' => 'all',
    'files' => array()
  );
  
  foreach ($dirs as $dir) {
    foreach ($subDirs as $subDir) {
      $config['files'][] = "$dir$subDir/css/common.css";
      $config['files'][] = "$dir$subDir/css/$pagetype.css";
      $config['files'][] = "$dir$subDir/css/$pagetype-$platform.css"; 
      $config['files'][] = "$dir$subDir/css/$page-common.css";
      $config['files'][] = "$dir$subDir/css/$page-$pagetype.css";
      $config['files'][] = "$dir$subDir/css/$page-$pagetype-$platform.css"; 
    }
  }
  return $config;
}

// Javascript does not support overrides so include common files
// and the most specific platform file.  Themes override js.
function getJSFileConfigForDirs($page, $pagetype, $platform, $dirs, $subDirs) {
  $config = array(
    'include' => 'all',
    'files' => array()
  );
  
  foreach ($subDirs as $subDir) {
    $dirConfig = array(
      'include' => 'any',
      'files' => array()
    );
    foreach ($dirs as $dir) {
      $dirConfig['files'][] =  array(
        'include' => 'all',
        'files'   => array(
          "$dir$subDir/javascript/common.js",
          array(
            'include' => 'any',
            'files'   => array(
              "$dir$subDir/javascript/$pagetype-$platform.js", 
              "$dir$subDir/javascript/$pagetype.js",
            ),
          ),
          "$dir$subDir/javascript/$page-common.js",
          array(
            'include' => 'any',
            'files'   => array(
              "$dir$subDir/javascript/$page-$pagetype-$platform.js", 
              "$dir$subDir/javascript/$page-$pagetype.js",
            ),
          ),
        ),
      );
    }
    $config['files'][] = $dirConfig;
  }
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
  list($ext, $module, $page, $pagetype, $platform, $pathHash) = explode('-', $key);

  $cache = new DiskCache(CACHE_DIR.'/minify', 30, true);
  $cacheName = "group_$key";
  
  if ($cache->isFresh($cacheName)) {
    $minifyConfig = $cache->read($cacheName);
    
  } else {
    // CSS includes all in order.  JS prefers theme
    $cssDirs = array(
      TEMPLATES_DIR, 
      THEME_DIR,
    );
    $jsDirs = array(
      THEME_DIR,
      TEMPLATES_DIR, 
    );
    
    if ($module == 'info') {
      // Info module does not inherit from common css files
      $subDirs = array(
        '/modules/'.$module,
      );
    } else {
      $subDirs = array(
        '/common',
        '/modules/'.$module,
      );
    }
    
    $checkFiles = array(
      'css' => getCSSFileConfigForDirs(
          $page, $pagetype, $platform, $cssDirs, $subDirs),
      'js'  => getJSFileConfigForDirs (
          $page, $pagetype, $platform, $jsDirs, $subDirs),
    );
    //error_log(print_r($checkFiles, true));
    
    $minifyConfig[$key] = buildFileList($checkFiles[$ext]);
    error_log(__FUNCTION__."($pagetype-$platform) scanned filesystem for $key");

    $cache->write($minifyConfig, $cacheName);
  }
  
  //error_log(__FUNCTION__."($pagetype-$platform) returning: ".print_r($minifyConfig, true));
  return $minifyConfig;
}

function minifyPostProcess($content, $type) {
  if ($type === Minify::TYPE_CSS) {
    $urlPrefix = URL_PREFIX;
          
    if ($GLOBALS['siteConfig']->getVar('DEVICE_DEBUG') && URL_PREFIX == URL_BASE) {
      // if device debugging is on, always append since minify
      $urlPrefix .= 'device/'.$GLOBALS['deviceClassifier']->getDevice().'/';
    }

    $content = "/* Adding url prefix '".$urlPrefix."' */\n\n".
      preg_replace(';url\("?\'?/([^"\'\)]+)"?\'?\);', 'url("'.$urlPrefix.'\1")', $content);
  }
  
  return $content;
}

