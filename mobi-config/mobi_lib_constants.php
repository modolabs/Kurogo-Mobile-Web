<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once($docRoot . '/mobi-config/mobi_lib_config.php');

/*
 * use this file for storing constants that are used in multiple files
 * DO NOT STORE sensitive info like passwords
 * those go in config.php
 * which should not be committed
 */

// file/directory locations
if (version_compare(phpversion(), '5.3') == -1) {
  define("CACHE_DIR", '/var/www/html/mobi-lib/cache/');
  define("LIBDIR", '/var/www/html/mobi-lib/');
} else {
  define("CACHE_DIR", __DIR__ . '/cache/');
  define("LIBDIR", __DIR__);
}

/* debug preferences
 * not really all constants but whatever
 */
define("DEBUG_LEVEL_ERROR", 0);
define("DEBUG_LEVEL_WARNING", 1);
define("DEBUG_LEVEL_DEBUG", 2);
define("DEBUG_LEVEL", DEBUG_LEVEL_ERROR);

function warn($mesg) {
  if (DEBUG_LEVEL >= DEBUG_LEVEL_WARNING) echo $mesg . "\n";
}

function debug($mesg) {
  if (DEBUG_LEVEL >= DEBUG_LEVEL_DEBUG) echo $mesg . "\n";
}

/* misc */
define("TIMEZONE", "America/New_York");

/* SHUTTLESCHEDULE */
define("NEXTBUS_FEED_URL", 'http://www.nextbus.com/s/xmlFeed?');
define("NEXTBUS_AGENCY", 'mit');
define("NEXTBUS_ROUTE_CACHE_TIMEOUT", 86400); // max age, routeConfig data
define("NEXTBUS_PREDICTION_CACHE_TIMEOUT", 20); // max age, predictions
define("NEXTBUS_VEHICLE_CACHE_TIMEOUT", 10); // max age, vehicle locations
define("NEXTBUS_CACHE_MAX_TOLERANCE", 90); // when to revert to pub schedule
define("NEXTBUS_DAEMON_PID_FILE", CACHE_DIR . 'NEXTBUS_DAEMON_PID');

/* STELLAR */
define("STELLAR_COURSE_DIR", CACHE_DIR . 'STELLAR_COURSE/'); // dir for subject listing files
define("STELLAR_COURSE_CACHE_TIMEOUT", 86400); // how long to keep cached subject files
define("STELLAR_FEED_DIR", CACHE_DIR . 'STELLAR_FEEDS/'); // dir for cached rss data
define("STELLAR_FEED_CACHE_TIMEOUT", 10); // how long to keep cached rss files
define("STELLAR_SUBSCRIPTIONS_FILE", CACHE_DIR . 'STELLAR_SUBSCRIPTIONS');
define("STELLAR_USE_PRODUCTION", True);
if(STELLAR_USE_PRODUCTION) {
  define("STELLAR_BASE_URL", "http://stellar.mit.edu/courseguide/course/");
  define("STELLAR_RSS_URL", "http://stellar.mit.edu/SRSS/rss");
} else {
  define("STELLAR_BASE_URL", "http://stellar-dev.mit.edu/courseguide/course/");
  define("STELLAR_RSS_URL", "http://stellar-dev.mit.edu/SRSS/rss");
}

/* LIBRARIES */
define("ICS_CACHE_LIFESPAN", 900);

// EMERGENCY
define("EMERGENCY_USE_PRODUCTION", True);
if(EMERGENCY_USE_PRODUCTION) {
  define("EMERGENCY_RSS_URL", 'http://emergency.mit.net/emergency/mobirss');
} else {
  define("EMERGENCY_RSS_URL", 'http://emergency.mit.net/emtest/mobirss');
}

// 3DOWN
define("THREEDOWN_RSS_URL", 'http://3down.mit.edu/3down/index.php?rss=1');

/* 
// these aren't being used, just keeping a record of what may

// PEOPLE DIRECTORY
define("LDAP_SERVER", 'ldap.mit.edu');

*/


?>
