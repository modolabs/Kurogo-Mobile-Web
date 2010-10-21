<?
/*
 * the prodction error handler has a pretty html page dispalying a brief error message
 * it also emails detailed information to the developer email address
 */
define("USE_PRODUCTION_ERROR_HANDLER", True);

/*
 * DEVELOPER_EMAIL is a comma-separated string
 * of the people the server should email when something
 * goes wrong
 */
define("DEVELOPER_EMAIL", "mobile-project-errors@mit.edu");

/** log file locations
 *
 */
define("API_LOG_FILE", "/usr/local/etc/was/logs/mobi_api_log");
define("API_CURRENT_LOG_FILE", "/usr/local/etc/was/tmp/mobi_api_log");
define("WEB_LOG_FILE", "/usr/local/etc/was/logs/mobi_web_log");
define("WEB_CURRENT_LOG_FILE", "/usr/local/etc/was/tmp/mobi_web_log");
define("LOG_DATE_FORMAT", '[D m d H:i:s Y]');
define("LOG_DATE_PATTERN", '/^.{5}(\d{2}) (\d{2}).{10}(\d{4})/');

/* mysql table names */
define("PAGE_VIEWS_TABLE", 'mobi_web_page_views');
define("API_STATS_TABLE", 'mobi_api_requests');

/*************** LDAP server, filters and mappings **************
 *
 */
define("LDAP_SERVER", "ldap-staging.mit.edu");
define("SEARCH_TIMELIMIT", 30);
define("READ_TIMELIMIT", 30);
define("TELEPHONE_FILTER", "(telephonenumber=*%s*)");
define("TELEPHONE_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("EMAIL_FILTER", "(mail=*%s*)");
define("EMAIL_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("NAME_SINGLE_CHARACTER_FILTER", "(|(cn=%s*)(cn=* %s*)(cn=*-%s*))");
define("NAME_MULTI_CHARACTER_FILTER", "(|(cn=*%s*)(mail=*%s*))");
define("NAME_SEARCH_FILTER", "(&(objectClass=person)%s)");
define("UID_FILTER", "(uid=%s)");
define("UID_SEARCH_FILTER", "(&(objectClass=person)%s)");
/*  for the $personDisplayMapping array:
 *  index 0: display title,
 *  index 1: ldap attribute,
 *  index 2: href,
 *  index 3: class,
 *  index 4: set to TRUE if to be displayed on the mobile device, otherwise FALSE,
 *  index 5: group,
 *  index 6: has_link are for Touch,
 *  index 7: set to TRUE if needed for processing (non display), otherwise FALSE
*/
$appError = 0;
global $personDisplayMapping;
$personDisplayMapping = array(array("surname", "sn", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("givenname", "givenname", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("name", "cn", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("title", "title", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("dept", "ou", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("affiliation", "edupersonaffiliation", null, null, FALSE, FALSE, FALSE, FALSE),
                     array("address", "street", null, null, FALSE, FALSE, FALSE, FALSE),
                     array("homephone", "homephone", "phoneHREF", "phone", FALSE, TRUE, TRUE, FALSE),
                     array("room", "roomnumber", "mapURL", "map", FALSE, FALSE, TRUE, TRUE),
                     array("initials", "initials", null, null, FALSE, FALSE, TRUE, TRUE),
                     array("id", "uid", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("phone", "telephonenumber", "phoneHREF", "phone", TRUE, TRUE, TRUE, FALSE),
                     array("fax", "facsimiletelephonenumber", "phoneHREF", "phone", TRUE, TRUE, TRUE, FALSE),
                     array("email", "mail", "mailHREF", "email", TRUE, FALSE, TRUE, FALSE),
                     array("office", "physicaldeliveryofficename", "mapURL", "map", TRUE, FALSE, TRUE, FALSE),
                 );

/*************** exception loggin **************
 *
 */
define("LOG_ALL_ERRORS", FALSE);
define("LOG_PEOPLE_DIRECTORY_ERRORS", TRUE);
define("ERROR_LOGFILE", "/usr/local/etc/was/logs/mobile_error_log");

/*************** directory locations **************
 *
 * make sure there is a trailing slash at the end of
 * all the directory locations
 * 
 *
 * LIBDIR is the location of required files
 * that are shared between the Mobile Web and
 * SMS services.
 */
define("LIBDIR", '/var/www/html/mobi-lib/');

/* 
 * WEBROOT specifies the root directory of the Mobile Web
 * in relation to THIS COMPUTER.
 * on red hat machines this is usually somewhere in /var/www/
 */
define("WEBROOT", '/var/www/html/mobi-web/');

/*
 * HTTPROOT specifies the root directory of the Mobile Web
 * seen by BROWSER CLIENTS in relation to YOUR DOMAIN.
 * usually this is /
 * but if the website is hosted on (for example)
 * http://yourdomain.com/foo
 * then you should assign it to /foo/
 */
define("HTTPROOT", '/');

/*********** url locations ***************/

/* 
 * MOBI_SERVICE_URL is the URL that can be called via HTTP
 * with a user agent string to get information about
 * device characteristics
 */
define("MOBI_SERVICE_URL", 'http://mobile-service-dev.mit.edu/mobi-service/');

/* mqp searchserver */
define("MAP_SEARCH_URL", 'http://whereis.mit.edu/search');
define("MAP_TILE_CACHE_DATE", 'tiles_last_updated.txt');

// cookie expire times
define("MODULE_ORDER_COOKIE_LIFESPAN", 160 * 86400);
define("LAYOUT_COOKIE_LIFESPAN", 16 * 86400);
define("CERTS_COOKIE_LIFESPAN", 86400);

// show device detection info
define("SHOW_DEVICE_DETECTION", False);

/* Apple Push Notifications Service */
// are these sensitive? (not sure)
// Judged them as being non-sensitive since they can't be used without a valid certificate
define("APNS_CERTIFICATE_DEV", '/usr/local/etc/was/certs/apns_dev.pem');
define("APNS_CERTIFICATE_DEV_PASSWORD", '');
define("APNS_CERTIFICATE_PROD", '/usr/local/etc/was/certs/apns_prod.pem');
define("APNS_CERTIFICATE_PROD_PASSWORD", '');
define("APNS_SANDBOX", False);
define("APPLE_RELEASE_APP_ID", "edu.mit.mitmobile");
define("APNS_CONNECTIONS_LIMIT", 100);

?>
