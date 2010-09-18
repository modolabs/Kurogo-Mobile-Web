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
    
    $fileNames = array(
      "common",
      "$pagetype",
      "$pagetype-$platform",
      "$page-common",
      "$page-$pagetype",
      "$page-$pagetype-$platform",
    );
    
    $files = array();
    
    foreach ($dirs as $dir) {
      $dir .= '/'.$extDirs[$ext];

      if (realpath($dir)) {
        foreach ($fileNames as $file) {
          $path = "$dir/".$file.'.'.$ext;
          
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
      '/url\("?([^"\)]+)"?\)/', 'url("/device/'.$layout.'\1")', $content);
  }
  return $content;
}

