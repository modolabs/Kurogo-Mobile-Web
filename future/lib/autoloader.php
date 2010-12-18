<?php

function siteLibAutoloader($className) {
    $paths = array(LIB_DIR);
    if (defined('SITE_LIB_DIR')) {
        $paths[] = SITE_LIB_DIR;
    }
    
    if (defined('MODULES_DIR') && preg_match("/(.*)Module/", $className, $bits)) {
        $paths[] = MODULES_DIR . '/' . strtolower($bits[1]);
    }
        
    foreach ($paths as $path) {
        $file = realpath_exists("$path/$className.php");
        if ($file) {
            require_once $file;
            return;
        }
    }
    return;
}
