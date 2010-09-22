<?php

require_once LIB_DIR.'/DiskCache.php';
  
//
// Handle CSS and Javascript a little differently:
//

// CSS supports overrides so include all available CSS files.
function getCSSFileConfigForDirs($page, $pagetype, $platform, $dirs) {
  $config = array(
    'include' => 'all',
    'files' => array()
  );
  
  foreach ($dirs as $dir) {
    $config['files'][] = array(
      'include' => 'all',
      'files'   => array(
        "$dir/css/common.css",
        "$dir/css/$pagetype.css",
        "$dir/css/$pagetype-$platform.css", 
        "$dir/css/$page-common.css",
        "$dir/css/$page-$pagetype.css",
        "$dir/css/$page-$pagetype-$platform.css", 
      ),
    );
  }
  return $config;
}

// Javascript does not support overrides so include common files
// and the most specific platform file.  Themes override js.
function getJSFileConfigForDirs($page, $pagetype, $platform, $dirs) {
  $config = array(
    'include' => 'any',
    'files' => array()
  );
  
  foreach ($dirs as $dir) {
    $config['files'][] =  array(
      'include' => 'all',
      'files'   => array(
        "$dir/javascript/common.js",
        array(
          'include' => 'any',
          'files'   => array(
            "$dir/javascript/$pagetype-$platform.js", 
            "$dir/javascript/$pagetype.js",
          ),
        ),
        "$dir/javascript/$page-common.js",
        array(
          'include' => 'any',
          'files'   => array(
            "$dir/javascript/$page-$pagetype-$platform.js", 
            "$dir/javascript/$page-$pagetype.js",
          ),
        ),
      ),
    );
  }
  return $config;
}

function buildFileList($checkFiles) {
  $foundFiles = array();
  foreach ($checkFiles['files'] as $entry) {
    if (is_array($entry)) {
      $result = buildFileList($entry);
      if ($checkFiles['include'] == 'any') {
        if (count($result)) {
          $foundFiles = $result;
          break; // break on first result we find in this list
        }
      } else {
        $foundFiles = array_merge($foundFiles, $result);
      }
    } else if (realpath($entry)) { 
      $foundFiles[] = $entry;
      break; 
    }
  }
  return $foundFiles;
}

function getMinifyGroupsConfig() {
  $minifyConfig = array();
  
  $key = $_GET['g'];
  list($ext, $module, $page) = explode('-', $key);

  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
  $platform = $GLOBALS['deviceClassifier']->getPlatform();

  $cache = new DiskCache($GLOBALS['siteConfig']->getVar('MINIFY_CACHE_DIR'), 30, true);
  $cacheName = "minifyGroups_$key-$pagetype-$platform";
  
  if ($cache->isFresh($cacheName)) {
    $minifyConfig = $cache->read($cacheName);
    
  } else {
    if ($module == 'info') {
      // Info module does not inherit from common css files
      $dirs = array(
        TEMPLATES_DIR.'/modules/'.$module, 
        $GLOBALS['siteConfig']->getVar('THEME_DIR').'/modules/'.$module,
      );
    } else {
      $dirs = array(
        TEMPLATES_DIR.'/common', 
        TEMPLATES_DIR.'/modules/'.$module, 
        $GLOBALS['siteConfig']->getVar('THEME_DIR').'/common',
        $GLOBALS['siteConfig']->getVar('THEME_DIR').'/modules/'.$module,
      );
    }
    $checkFiles = array(
      'css' => getCSSFileConfigForDirs($page, $pagetype, $platform, $dirs),
      'js'  => getJSFileConfigForDirs ($page, $pagetype, $platform, array_reverse($dirs)),
    );
    
    $minifyConfig[$key] = buildFileList($checkFiles[$ext]);

    //error_log(__FUNCTION__.'('.$pagetype.'-'.$platform.') scanned filesystem');

    $cache->write($minifyConfig, $cacheName);
  }
  
  //error_log(__FUNCTION__."($pagetype-$platform) returning: ".print_r($minifyConfig, true));
  return $minifyConfig;
}

function minifyPostProcess($content, $type) {
  error_log(__FUNCTION__."() post processing $type (".$GLOBALS['deviceClassifier']->layoutForced().")");
  
  if ($GLOBALS['deviceClassifier']->layoutForced() && $type === Minify::TYPE_CSS) {    
    $layout = $GLOBALS['deviceClassifier']->getForcedLayout();
    $content = preg_replace(
      ';url\("?\'?([^"\'\)]+)"?\'?\);', 'url("/device/'.$layout.'\1")', $content);
  }
  return $content;
}

