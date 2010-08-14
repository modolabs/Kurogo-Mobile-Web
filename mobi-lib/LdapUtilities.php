<?php

require_once "ldap_config.php";

// search string templates
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

// Scenarios:
// 
// A: 1 search term. Could be given name, surname, or email. Query for "hewitt":
//
//  (|
//	  (|(givenName=hewitt*)(sn=hewitt*))
//    (mail=hewitt*)
//  )
//
// B: 2 search terms. Names could be in either order. Query for "doug hall":
//
//	(|
//	  (&(givenName=doug*)(sn=hall*))
//	  (&(givenName=hall*)(sn=doug*))
//	  (mail=doug*hall*)
//	)
//
// C: 3 or more search terms. Query for "Prof Maria R. Garcia Castillo" (queries
//     this long can come from Courses, where names are entered freeform): 
//
//  (|
//    (|
//      (&(givenName=Prof*)(sn=Castillo*)) # First {...} Last
//      (&(givenName=Prof*)(sn=Garcia Castillo*)) # First {...} Last1 Last2
//      (&(givenName=Maria*)(sn=Castillo*)) # Title First {...} Last
//      (&(givenName=Maria*)(sn=Garcia Castillo*)) # Title First {...} Last1 Last2
//      (cn=Prof*Maria*R.*Garcia*Castillo*) # Try everything, sometimes gets nicknames
//    )
//    (mail=Prof*Maria*R.*Garcia*Castillo*)
//  )
//
// We've been having problems having too many matches returned, so we're 
// intentionally making this a little less forgiving:
//
// 1. It's assumed that anything you write comes at the beginning of a name or
//    email address.  So "ith" will not match against "Smith".
// 2. If there are more than two names written, we fall back to assuming an 
//    ordering of first, middle, then last name, and not trying out every 
//    possible combination.

function sanitizeSearch($search)
{
	// We allow letters, numbers, underscores, spaces, and single quotes (for
	// names like "O'Reilly").  Everything else gets axed into a space.
	return trim(preg_replace('/[^\w\'@.]/', " ", $search));
}

function queryForFirstLastName($firstName, $lastName) {
    return "(&(givenName=$firstName*)(sn=$lastName*))";
}

function queryForNames($names)
{
	$nameCount = count($names);

	if ($nameCount == 1) {
		// Just one name -- could be given or surname.
		return "(|(givenName=$names[0]*)(sn=$names[0]*))";
	}
	elseif ($nameCount == 2) {
		// Two names, assume one is given, one is surname.  Assume that they 
		// start the names correctly, but we wildcard the end.
		return queryForFirstLastName($names[0], $names[1]) . 
		       queryForFirstLastName($names[1], $names[0]);
	}
	elseif ($nameCount > 2) {
	    $queries = array();
	    
	    // Either the first word is the first name, or it's a title and the 
	    // second word is the first name.
	    $possibleFirstNames = array($names[0], $names[1]);
	    
	    // Either the last word is the last name, or the last two words taken
	    // together are the last name.
	    $possibleLastNames = array($names[$nameCount - 1],
	                               $names[$nameCount - 2] . " " . $names[$nameCount - 1]);

	    foreach ($possibleFirstNames as $i => $firstName) {
	        foreach ($possibleLastNames as $j => $lastName) {
	            $queries[] = queryForFirstLastName($firstName, $lastName);
	        }
	    }

		// Kitchen sink -- just string them all together with wildcards
		// and hope that it's a match on the common name.
		$queries[] = "(cn=" . implode("*", $names) . "*)";
		
		return "(|" . implode($queries) . ")";
	}
}

function queryForEmail($words)
{
	return "(mail=" . implode("*", $words) . "*)";
}

function buildNameAndEmailLDAPQuery($search)
{
	$safeSearch = sanitizeSearch($search);
	$words = preg_split("/\s+/", $safeSearch);
	$query = "(|" . queryForNames($words) . queryForEmail($words) . ")";

	// error_log("SAFE SEARCH: " . $safeSearch);
	// error_log("QUERY: " . $query);

	// Put the gathered clauses in the person search template.
    $searchFilter = str_replace("%s", $query, NAME_SEARCH_FILTER);
    return($searchFilter);	
}

// common ldap error codes
define("LDAP_INSUFFICIENT_ACCESS", 0x32);
define("LDAP_PARTIAL_RESULTS", 0x09);
define("LDAP_TIMELIME_EXCEEDED", 0x03);

function generateErrorMessage($ldap_resource) {
   $error_code = ldap_errno($ldap_resource);
   $error_codes = array(
       LDAP_PARTIAL_RESULTS => "Partial result only available",
       LDAP_INSUFFICIENT_ACCESS => "Insufficient access",
       LDAP_TIMELIMIT_EXCEEDED => "Search timed out",
    );
    if(isset($error_codes[$error_code])) {
        return $error_codes[$error_code];
    }
}

?>
