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
  * This script handles all incoming requests
  * @package Core
  */

/**
  * The Kurogo library sets up the environment
  */
require_once realpath(dirname(__FILE__).'/../lib/Kurogo.php');
$Kurogo = Kurogo::sharedInstance();

//
// Configure web application
// modifies $path for us to strip prefix and device
//

$path = isset($_GET['_path']) ? $_GET['_path'] : '';

$Kurogo->initialize($path); 

function _phpFile($_file) {
    Kurogo::log(LOG_DEBUG, "Output php file $_file", 'kurogo');
    if ($file = realpath_exists($_file)) {
        require_once $file;
        exit;
    }
    
    _404();
}

function _outputFile($_file) {
    Kurogo::log(LOG_DEBUG, "Output file $_file", 'kurogo');
    if ($file = Watchdog::safePath($_file)) {
        CacheHeaders($file);
        header('Content-type: '.mime_type($file));
        readfile($file);
        exit;
    }

    _404();
}

function _outputSiteFile($matches) {
  _outputFile(SITE_DIR.'/'.$matches[1].'/'.$matches[2]);
}

function _outputTypeFile($matches) { 
  $file = $matches[3];
  $prefix = '';
  $bits = explode("/", $file);
  if (count($bits)>1) {
    $file = array_pop($bits);
    $prefix = trim(implode("/", $bits), "/") . '/';
  }

  $platform = Kurogo::deviceClassifier()->getPlatform();
  $pagetype = Kurogo::deviceClassifier()->getPagetype();
  $browser  = Kurogo::deviceClassifier()->getBrowser();
  
  $testDirs = array(
    THEME_DIR,
    SHARED_THEME_DIR,
  	SITE_APP_DIR,
  	SHARED_APP_DIR,
  	APP_DIR
  );
  
  $testFiles = array(
    "$prefix$pagetype-$platform-$browser/$file",
    "$prefix$pagetype-$platform/$file",
    "$prefix$pagetype/$file",
    "$prefix$file",
  );
  
  foreach ($testDirs as $dir) {
    //do not assume dirs have value set
    if ($dir) {
        $dir .= '/' . $matches[1] . $matches[2];
    
        foreach ($testFiles as $file) {
            Kurogo::log(LOG_DEBUG, "Looking for $dir/$file", 'index');
            if ($file = realpath_exists("$dir/$file")) {
                _outputFile($file);
            }
        }
    }
  }

  _404();
}

function _outputFileLoaderFile($matches) {
  _outputFile(FileLoader::load($matches[1]));
}

/**
  * Outputs a 404 error message
  */
function _404() {
    header("HTTP/1.1 404 Not Found");
    $url = $_SERVER['REQUEST_URI'];
    Kurogo::log(LOG_WARNING, "URL $url not found", 'kurogo');
    echo <<<html
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL $url was not found on this server.</p>
</body></html>

html;
    exit();
}

/**
  * Will see if there is a HTTP_IF_MODIFIED_SINCE header and if the dates match it will return a 304
  * otherwise will set the Last-Modified header
  */
function CacheHeaders($file)
{
    $fs = stat($file);
    $mtime = gmdate('D, d M Y H:i:s', $fs['mtime']) . ' GMT';

    //if we have inode then use etag. Windows does not have inodes.
    if (isset($fs['ino']) && $fs['ino'] > 0) {
        //use apache style etag 
        $etag = sprintf('%x-%x-%s', $fs['ino'], $fs['size'],base_convert(str_pad($fs['mtime'],16,"0"),10,16));
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if (str_replace('"','', $_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }
    
        header("ETag: \"$etag\"");

    } else {

        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $mtime) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }
        
        header("Last-Modified: $mtime");
    }    
    return;
}


//
// Handle page request
//

$url_patterns = array(
  array(
    'pattern' => ';^.*robots.txt$;', 
    'func'    => '_phpFile',
    'params'  => array(LIB_DIR.'/robots.php'),
  ),
  array(
    'pattern' => ';^.*favicon.ico$;', 
    'func'    => '_outputTypeFile',
    'params'  => array(array(null,'common','/images','favicon.ico'))
  ),
  array(
    'pattern' => ';^.*ga.php$;',
    'func'    => '_phpFile',
    'params'  => array(LIB_DIR.'/ga.php'),
  ),
  array(
    'pattern' => ';^.*(modules|common)(/.*images)/(.*)$;',
    'func'    => '_outputTypeFile',
  ),
  array(
    'pattern' => ';^.*'.FileLoader::fileDir().'/(.+)$;',
    'func'    => '_outputFileLoaderFile',
  ),
  array(
    'pattern' => ';^.*(media)(/.*)$;',
    'func'    => '_outputSiteFile',
  )
);
 
// try the url patterns. Run the path through each pattern testing for a match
foreach ($url_patterns as $pattern_data) {
    if (preg_match($pattern_data['pattern'], $path, $matches)) {
        $params = isset($pattern_data['params']) ? $pattern_data['params'] : array($matches);
        call_user_func_array($pattern_data['func'], $params);
    }
}

// No pattern matches. Attempt to load a module

$args = array_merge($_GET, $_POST);
unset($args['_path']);

// undo magic_quotes_gpc if set. It really shouldn't be. Stop using it.
if (get_magic_quotes_gpc()) {
    function deepStripSlashes($v) {
      return is_array($v) ? array_map('deepStripSlashes', $v) : stripslashes($v);
    }
    $args = deepStripslashes($args);
    $_COOKIE = deepStripSlashes($_COOKIE);
}
$Kurogo->setArgs($args);

/* if the path is "empty" route to the default page. Will search the config file in order:
 * DEFAULT-PAGETYPE-PLATFORM
 * DEFAULT-PAGETYPE
 * DEFAULT
 * home is the default
 */
if (!strlen($path) || $path == '/') {
    $url = Kurogo::defaultModule();
      
  if (!preg_match("/^http/", $url)) {
    $url = URL_PREFIX . $url . "/";
  }
  
  Kurogo::redirectToURL($url, Kurogo::REDIRECT_PERMANENT);
} 

$parts = explode('/', ltrim($path, '/'), 2);

if ($parts[0]==API_URL_PREFIX) {
    if (Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
        set_exception_handler("exceptionHandlerForProductionAPI");
    } else {
        set_exception_handler("exceptionHandlerForAPI");
    }

    $parts = explode('/', ltrim($path, '/'));

    switch (count($parts))
    {
        case 1:
            throw new KurogoUserException("Invalid API request: '{$_SERVER['REQUEST_URI']}'", 1);

        case 2: 
            $id = 'kurogo';
            $command = $parts[1];
		    $Kurogo->setRequest($id, $command, $args);
            if (!$module = KurogoAPIModule::factory($id, $command, $args)) {
                throw new KurogoException("Module $id cannot be loaded");
            }
            break;
            
        case 3:
            $id = isset($parts[1]) ? $parts[1] : '';
            $command = isset($parts[2]) ? $parts[2] : '';
		    $Kurogo->setRequest($id, $command, $args);
            if (!$module = APIModule::factory($id, $command, $args)) {
                throw new KurogoException("Module $id cannot be loaded");
            }
            break;

        default:
            throw new KurogoUserException("Invalid API request: '{$_SERVER['REQUEST_URI']}'", 1);
            break;
    }    

    $Kurogo->setCurrentModule($module);
    $module->executeCommand();

} elseif ($parts[0]=='min') { //used when minify is loaded when multi-site is on
    for ($i=1; $i<count($parts);$i++) {
        if (preg_match("^([a-z])=(.*)^", $parts[$i], $bits)) {
            $_GET[$bits[1]] = $bits[2];
        }
    }

    include('min/index.php');
} else {
    $id = $parts[0];
    $page = 'index';
    
    /* see if there's a redirect for this path */
    if ($url_redirects = Kurogo::getSiteSection('urls')) {
      if (array_key_exists($id, $url_redirects)) {
        if (preg_match("#^http(s)?://#", $url_redirects[$id])) {
           $url = $url_redirects[$id];
        } else {
          $parts[0] = $url_redirects[$id];
          $url = URL_PREFIX . implode("/", $parts);
          if ($args) {
          	$url .= "?" . http_build_query($args);
          }
        }
        Kurogo::log(LOG_NOTICE, "Redirecting to $url", 'kurogo');
        Kurogo::redirectToURL($url, Kurogo::REDIRECT_PERMANENT);
      }
    }
    
    // find the page part
    if (isset($parts[1])) {
      if (strlen($parts[1])) {
        $page = $parts[1];
      }
      
    } else {
      // redirect with trailing slash for completeness
      Kurogo::redirectToURL("./$id/", Kurogo::REDIRECT_PERMANENT);
    }

    $Kurogo->setRequest($id, $page, $args);

    if ($module = WebModule::factory($id, $page, $args)) {
        $Kurogo->setCurrentModule($module);
        $module->displayPage();
    } else {
        throw new KurogoException("Module $id cannot be loaded");
    }
}
exit;
