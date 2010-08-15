<?php
/*************** LDAP server, filters and mappings **************
 *
 */

/*  for the $personDisplayMapping array:
 *  index 0: display name,
 *  index 1: ldap attribute,
 *  index 2: href,
 *  index 3: class,
 *  index 4: set to TRUE if to be displayed on the mobile device, otherwise FALSE,
 *  index 5: group,
 *  index 6: has_link are for Touch,
 *  index 7: set to TRUE if needed for processing (non display), otherwise FALSE
*/
$appError = 0;

// All of LDAP attributes will come to mit_ldap as all lowercase, even if that is not the case when 
// querying the LDAP service directly, so make them all lowercase here.
$personDisplayMapping = array(array("surname", "sn", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("givenname", "givenname", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("name", "cn", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("title", "title", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("unit", "ou", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("description", "description", null, null, TRUE, TRUE, FALSE, FALSE),
                     array("id", "uid", null, null, FALSE, FALSE, FALSE, TRUE),
                     array("phone", "telephonenumber", "phoneHREF", "phone", TRUE, TRUE, TRUE, FALSE),
                     array("fax", "facsimiletelephonenumber", "phoneHREF", "phone", TRUE, TRUE, TRUE, FALSE),
                     array("email", "mail", "mailHREF", "email", TRUE, FALSE, TRUE, FALSE),
                     array("office", "postaladdress", "mapURL", "map", TRUE, FALSE, TRUE, FALSE),
                 );

/*************** exception logging **************
 *
 */
define("LOG_ALL_ERRORS", FALSE);
define("LOG_PEOPLE_DIRECTORY_ERRORS", TRUE);

// Error messages.
define("LDAP_SEARCH_ERROR", "Too many results to display. Please refine your search.");
define("DIRECTORY_UNAVAILABLE", "Your request cannot be processed at this time.\nPlease try again later....");
define("INVALID_TELEPHONE_NUMBER", "Invalid telephone number....");
define("INVALID_EMAIL_ADDRESS","Invalid email address....");

// TODO: clean this up
define("ERROR_LOGFILE",  "../mobi-config/logs/mobile_error_log");
?>
