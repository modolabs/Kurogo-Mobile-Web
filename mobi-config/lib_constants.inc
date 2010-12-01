<?php

define('MOBILE_DEV_SERVER', TRUE);
define('MOBILE_STAGE_SERVER', FALSE);

define('TIMEZONE', 'America/New_York');

// paths
define('CACHE_DIR', '/opt/mitmobile/cache');
define('DATADIR', '/opt/mitmobile/static');
define('TMP_DIR', '/tmp/'); // cache files that we'll allow OS to clean out

// database parameter placeholders -- no, these values are not real
define('MYSQL_USER', 'mysql_user');
define('MYSQL_PASS', 'mysql_pass');
define('MYSQL_DBNAME', 'mysql_db');
define('MYSQL_HOST', 'localhost');

// Google Analytics Account
define('GA_ACCOUNT', 'UA-2923555-23');

// directory
define("LDAP_SERVER", "phonebook.harvard.edu");
define("LDAP_PATH", 'o=Harvard University,c=US');
define('LDAP_SEARCH_TIMELIMIT', 30);
define('LDAP_READ_TIMELIMIT', 30);

// campus_map
define('MAP_SEARCH_URL', 'http://map.harvard.edu/mapserver/campusmap_search.cfm');
define('BLDG_DATA_XML', DATADIR . '/bldg_data.xml');
define('ARCGIS_SERVICE_JSON_FILE', '/opt/mitmobile/maptiles/service.json');
define('ARCGIS_SERVER_URL', 'http://upo-srv2.cadm.harvard.edu/ArcGIS/rest/services/CampusMap/MapServer');
define('ARCGIS_CACHE', CACHE_DIR . '/ARCGIS');
define("MAP_TILE_CACHE_DIR", '/opt/mitmobile/maptiles/raw/');
define("MAP_TILE_CHECKSUM_FILE", '/opt/mitmobile/maptiles/export.md5');
define("MAP_TILE_CHECKSUM_FILE_TEMP", '/opt/mitmobile/maptiles/temp-export.md5');
define("MAP_TILE_CACHE_DATE", '/opt/mitmobile/maptiles/tiles_updated.txt');

// dining
define('PATH_TO_DINING_HRS', '/opt/mitmobile/static/DiningHours');

//libraries
define('URL_LIBRARIES_INFO', 'http://faulkner.hul.harvard.edu:9020/rest/lib/info');
define('URL_LIB_DETAIL_BASE','http://faulkner.hul.harvard.edu:9020/rest/lib/library/');
define('URL_ARCHIVE_DETAIL_BASE', 'http://faulkner.hul.harvard.edu:9020/rest/lib/archive/');
define('LIB_DIR', CACHE_DIR . '/LIBRARIES_CACHE');
// how long to keep cached subject files
define('LIB_DIR_CACHE_TIMEOUT', 60*60);

// news_office
define('NEWS_SEARCH_URL', 'http://news.harvard.edu/gazette/');

// events_calendar
define('PATH_TO_EVENTS_CAT', DATADIR . '/event_cat');
define('HARVARD_EVENTS_ICS_BASE_URL', 'http://www.trumba.com/calendars/gazette.ics');
define('HARVARD_ACADEMIC_ICS_BASE_URL', 'http://www.trumba.com/calendars/harvard_academic_calendar.ics');
define('TRUMBA_CALENDAR_CACHE_DIR', CACHE_DIR . '/TRUMBA_CALENDAR/');
define('TRUMBA_CALENDAR_CACHE_LIFESPAN', 60*15); //retrieve info from Trumba if the data is more than 15 mins old

define("ACADEMIC_CALENDAR_CACHE_DIR", CACHE_DIR . "/ACADEMIC_CALENDAR/");
define("ACADEMIC_CALENDAR_RSS", "/opt/mitmobile/static/academic_location.rss");
define("ACADEMIC_CALENDAR_CACHE_LIFESPAN", 86400 * 30);

// mobile about
define("WHATS_NEW_PATH", DATADIR . "/whatsnew.xml");

// shuttles
// define('SHUTTLE_GTFS_FEED', DATADIR . '/gtfs.zip');
// define('NEXTBUS_FEED_URL', 'http://www.nextbus.com/s/xmlFeed?');
// define('NEXTBUS_AGENCY', 'mit');
// define('NEXTBUS_ROUTE_CACHE_TIMEOUT', 86400); // max age, routeConfig data
// define('NEXTBUS_PREDICTION_CACHE_TIMEOUT', 20); // max age, predictions
// define('NEXTBUS_VEHICLE_CACHE_TIMEOUT', 10); // max age, vehicle locations
// define('NEXTBUS_CACHE_MAX_TOLERANCE', 90); // when to revert to pub schedule
// define('NEXTBUS_CACHE', CACHE_DIR . '/NEXTBUS/');
// define('NEXTBUS_DAEMON_PID_FILE', CACHE_DIR . '/NEXTBUS_DAEMON_PID');

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

// stellar
define('STELLAR_BASE_URL', 'http://services.isites.harvard.edu/course_catalog/api/v1/search?');
define('STELLAR_RSS_URL', '');

// emergency
define('EMERGENCY_RSS_URL', '');

?>
