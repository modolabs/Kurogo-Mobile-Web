<?
/* mobi-config/lib_constants_prod.php
 * Per-server settings used by mobi-lib (Mobile Web backend).
 * Separate from web_constants because other systems (e.g. SMS) can use this.
 * Production version.
 */

// stellar
define('STELLAR_BASE_URL', 'http://services.isites.harvard.edu/course_catalog/api/v1/search?');
define('STELLAR_RSS_URL', 'http://stellar.mit.edu/SRSS/rss');

// emergency
define('EMERGENCY_RSS_URL', 'http://emergency.mit.net/emergency/mobirss');

?>
