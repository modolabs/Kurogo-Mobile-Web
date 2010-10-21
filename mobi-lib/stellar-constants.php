<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";

// directory for mapping files
define("STELLAR_COURSE_DIR", CACHE_DIR . 'STELLAR_COURSE/');
// how long to keep cached mapping files
define("STELLAR_COURSE_CACHE_TIMEOUT", 86400);

define("STELLAR_FEED_DIR", CACHE_DIR . 'STELLAR_FEEDS/');
define("STELLAR_FEED_CACHE_TIMEOUT", 10);
define("STELLAR_SUBSCRIPTIONS_FILE", CACHE_DIR . 'STELLAR_SUBSCRIPTIONS');

?>