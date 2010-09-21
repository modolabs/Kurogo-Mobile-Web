<?php

require_once LIB_DIR.'/DiskCache.php';

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
      
    $extDirs = array(
      'css' => 'css', 
      'js'  => 'javascript',
    );
  
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
    
    // Handle CSS and Javascript a little differently:
    //
    // CSS supports overrides so include all available CSS files.
    // Javascript does not support overrides so include common files
    // and the most specific platform file.
    //
    $fileNames = array(
      'css' => array(
        "common",
        "$pagetype",
        "$pagetype-$platform", 
        "$page-common",
        "$page-$pagetype",
        "$page-$pagetype-$platform", 
      ),
      'js' => array(
        "common",
        array(
          "$pagetype-$platform", 
          "$pagetype",
        ),
        "$page-common",
        array(
          "$page-$pagetype-$platform", 
          "$page-$pagetype",
        ),
      ),
    );
    
    $files = array();
    
    foreach ($dirs as $dir) {
      $dir .= '/'.$extDirs[$ext];

      if (realpath($dir)) {
        foreach ($fileNames[$ext] as $file) {
          $path = '';
          
          if (is_array($file)) {  
            // files that override each other
            foreach ($file as $override) {
              $path = "$dir/".$override.'.'.$ext;
              if (realpath($path)) { break; }
            }
          } else {
            $path = "$dir/".$file.'.'.$ext;
          }
          
          if (realpath($path)) {
            $files[] = realpath($path);
          }        
        }
      }
            
      $minifyConfig[$key] = $files;
    }
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
      '/url\("?\'?([^"\'\)]+)"?\'?\)/', 'url("/device/'.$layout.'\1")', $content);
  }
  return $content;
}

