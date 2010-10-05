<?php

function siteLibAutoloader($className) {
    $paths = array(LIB_DIR, SITE_LIB_DIR);
    
    foreach ($paths as $path) {
        $file = realpath_exists("$path/$className.php");
        if ($file) {
            require_once $file;
            return;
        }
    }
    return;
}
