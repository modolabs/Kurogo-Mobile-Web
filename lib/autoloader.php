<?php
/**
 * Auto Loader
 * @package Core
 */

 /**
 * This function defines a autoloader that is run when a class needs to be instantiated but the corresponding
 * file has not been loaded. Files MUST be named with the same name as its class
 * currently it will search:
 * 1. If the className has Module in it, it will search the MODULES_DIR
 * 2. The SITE_LIB_DIR  (keep in mind that some files may manually include the LIB_DIR class
 * 3. The LIB_DIR 
 * 
 */
 
$GLOBALS['libDirs'] = array();

function includePackage($packageName) {
    
    if (!preg_match("/^[a-zA-Z0-9]+$/", $packageName)) {
        throw new Exception("Invalid Package name $packageName");
    }

    $found = false;
    
    $dirs = array(LIB_DIR . "/$packageName");
    if (defined('SITE_LIB_DIR')) {  
        $dirs[] = SITE_LIB_DIR . "/$packageName";
    }

    foreach ($dirs as $dir) {
        if (in_array($dir, $GLOBALS['libDirs'])) {
            $found = true;
        }

        if (is_dir($dir)) {
            $found = true;
            $GLOBALS['libDirs'][] = $dir;

            if (is_file("$dir.php")) {
                include_once("$dir.php");
            }
        }
    }
    
    if (!$found) {
        throw new Exception("Unable to load package $packageName");
    }
    
}

function siteLibAutoloader($className) {
  $paths = $GLOBALS['libDirs'];
  

  // If the className has Module in it then use the modules dir
  if (defined('MODULES_DIR') && preg_match("/(.*)(Web|API)Module/", $className, $bits)) {
    $paths[] = MODULES_DIR . '/' . strtolower($bits[1]);
  }

  // use the site lib dir if it's been defined
  if (defined('SITE_LIB_DIR')) {
    $paths[] = SITE_LIB_DIR;
  }
  
  $paths[] = LIB_DIR;
      
  foreach ($paths as $path) {
    $file = realpath_exists("$path/$className.php");
    if ($file) {
      //error_log("Autoloader found $file for $className");
      include($file);
      return;
    }
  }
  return;
}
