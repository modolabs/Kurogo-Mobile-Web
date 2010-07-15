<?php

define('TIMEZONE', 'America/New_York');

// paths
define('CACHE_DIR', '/opt/mitmobile/cache');
define('DATADIR', '/opt/mitmobile/static');
define('TMP_DIR', '/tmp/'); // cache files that we'll allow OS to clean out

// database parameters
define('MYSQL_USER', 'mysql_user');
define('MYSQL_PASS', 'mysql_pass');
define('MYSQL_DBNAME', 'mysql_db');
define('MYSQL_HOST', 'localhost');

// directory
//define('LDAP_SERVER', 'ldap.mit.edu');
//define('LDAP_PATH', 'dc=mit, dc=edu');
define('LDAP_SEARCH_TIMELIMIT', 30);
define('LDAP_READ_TIMELIMIT', 30);

// campus_map
define('MAP_SEARCH_URL', 'http://whereis.mit.edu/search');
define('BLDG_DATA_XML', DATADIR . '/bldg_data.xml');
define('ARCGIS_SERVICE_JSON_FILE', '/opt/mitmobile/maptiles/service.json');
define('ARCGIS_SERVER_URL', 'http://upo-srv2.cadm.harvard.edu/ArcGIS/rest/services/CampusMap/MapServer');
define('ARCGIS_CACHE', CACHE_DIR . '/ARCGIS');
define("MAP_TILE_CACHE_DIR", '/opt/mitmobile/maptiles/raw/');
define("MAP_TILE_CHECKSUM_FILE", '/opt/mitmobile/maptiles/export.md5');
define("MAP_TILE_CHECKSUM_FILE_TEMP", '/opt/mitmobile/maptiles/temp-export.md5');

// news_office
define('NEWSOFFICE_FEED_URL', 'http://web.mit.edu/newsoffice/feeds/iphone.php');

// events_calendar
define('EVENTS_CALENDAR_API', 'http://events.mit.edu/MITEventsFull.wsdl');
define("ACADEMIC_CALENDAR_CACHE_DIR", CACHE_DIR . "/ACADEMIC_CALENDAR/");
define("ACADEMIC_CALENDAR_RSS", "/opt/mitmobile/static/academic_location.rss");
define("ACADEMIC_CALENDAR_CACHE_LIFESPAN", 86400 * 30);

// mobile about
define("WHATS_NEW_PATH", DATADIR . "/whatsnew.xml");

// shuttles
define('SHUTTLE_GTFS_FEED', DATADIR . '/gtfs.zip');
define('NEXTBUS_FEED_URL', 'http://www.nextbus.com/s/xmlFeed?');
define('NEXTBUS_AGENCY', 'mit');
define('NEXTBUS_ROUTE_CACHE_TIMEOUT', 86400); // max age, routeConfig data
define('NEXTBUS_PREDICTION_CACHE_TIMEOUT', 20); // max age, predictions
define('NEXTBUS_VEHICLE_CACHE_TIMEOUT', 10); // max age, vehicle locations
define('NEXTBUS_CACHE_MAX_TOLERANCE', 90); // when to revert to pub schedule
define('NEXTBUS_CACHE', CACHE_DIR . '/NEXTBUS/');
define('NEXTBUS_DAEMON_PID_FILE', CACHE_DIR . '/NEXTBUS_DAEMON_PID');

// stellar -- see dev/stage/prod for base url

// dir for subject listing files
define('STELLAR_COURSE_DIR', CACHE_DIR . '/STELLAR_COURSE');
// how long to keep cached subject files
define('STELLAR_COURSE_CACHE_TIMEOUT', 86400);
// dir for cached rss data
define('STELLAR_FEED_DIR', CACHE_DIR . '/STELLAR_FEEDS');
define('STELLAR_FEED_CACHE_TIMEOUT', 10); // how long to keep cached rss files
define('STELLAR_SUBSCRIPTIONS_FILE', CACHE_DIR . '/STELLAR_SUBSCRIPTIONS');

// libraries
define('LIBRARY_OFFICE_RSS', DATADIR . '/library_office.xml');
define('ICS_CACHE_LIFESPAN', 900);

// emergency -- see dev/stage/prod files

// 3down
define('THREEDOWN_RSS_URL', 'http://3down.mit.edu/3down/index.php?rss=1');

// TECHCASH
define('ORACLE_HOME', '/oracle/product/10.2.0/client');
define('TECHCASH_ORACLE_USER', 'techcash_oracle_user');
define('TECHCASH_ORACLE_PASS', 'techcash_oracle_pass');
define('TECHCASH_ORACLE_DB', 'techcash_oracle_db');
define('WAREHOUSE_ORACLE_USER', 'warehouse_oracle_user');
define('WAREHOUSE_ORACLE_PASS', 'warehouse_oracle_pass');
define('WAREHOUSE_ORACLE_DB', 'warehouse_oracle_db');

require_once('lib_constants_prod.inc');

?>
