<?php

require_once "ldap_config.php";

// Scenarios:
// 
// A: 1 search term. Could be given name, surname, or email. Query:
//
//  (|
//	  (|(givenName=hewitt*)(sn=hewitt*))
//    (mail=hewitt*)
//  )
//
// B: 2 search terms. Names could be in either order. Query:
//
//	(|
//	  (&(givenName=doug*)(sn=hall*))
//	  (&(givenName=hall*)(sn=doug*))
//	  (mail=doug*hall*)
//	)
//
// C: 3 or more search terms. Assume name order matches common name (cn). Query:
//
//  (|
//    (cn=susan*chan*tack*)
//    (mail=susan*chan*tack*)
//  )
//
// We've been having problems having too many matches returned, so we're 
// intentionally making this a little less forgiving:
//
// 1. It's assumed that anything you write comes at the beginning of a name or
//    email address.  So "ith" will not match against "Smith".
// 2. If there are more than two names written, we fall back to the common name,
//    which means it's assuming an ordering of first, middle, then last name, 
//    and not trying out every possible combination.

function sanitizeSearch($search)
{
	// We allow letters, numbers, underscores, spaces, and single quotes (for
	// names like "O'Reilly").  Everything else gets axed into a space.
	return preg_replace('/[^\w\'@.]/', " ", $search);
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
		return "(&(givenName=$names[0]*)(sn=$names[1]*))" . \
			   "(&(givenName=$names[1]*)(sn=$names[0]*))";
	}
	elseif ($nameCount > 2) {
		// Three or more names -- just string them all together with wildcards
		// and hope that it's a match on the common name.
		return "(cn=" . implode("*", $names) . "*)";
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

?>
