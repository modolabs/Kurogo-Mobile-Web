<?php
/*************** LDAP server, filters and mappings **************
 *
 */
define("LDAP_SERVER", "phonebook.harvard.edu");
define("LDAP_PATH", 'o=Harvard University,c=US'); 
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

// TODO: clean this up
define("ERROR_LOGFILE",  "../mobi-config/logs/mobile_error_log");
?>
