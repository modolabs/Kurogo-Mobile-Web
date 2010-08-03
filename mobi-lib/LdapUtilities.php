<?php

require_once "ldap_config.php";

// Build a query clause like this: (|(cn=*firstword*secondword*)(cn=*secondword*firstword*)(mail=*firstword*secondword*)(mail=*secondword*firstword*))
// Match should contain both words, either in the cn or the mail.
// $search should contain the search string, which is assumed to contain one or more words.

function buildNameAndEmailLDAPQuery($search)
{
	$cnTargets = array();
	$mailTargets = array();

	# Gather cn and mail search clauses for each word.
    foreach(preg_split("/\s+/", $search) as $word)
    {
        if($word != "")
        {
            if(strlen($word) == 1)
            {
				array_push($cnTargets, str_replace("%s", $word, NAME_SINGLE_CHARACTER_FILTER));
            }
            else
            {
				array_push($cnTargets, $word);
				array_push($mailTargets, $word);
            }
        }
    }

	// Assemble the gathered targets into the search clauses.
	$joinedClauses = 
		// Make two cn search clauses using $cnTargets: one in normal order, one backward.
		"(|(cn=*" . implode("*", $cnTargets) . "*)(cn=*" . implode("*", array_reverse($cnTargets)) . 
		// Make two mail search clauses using $mailTargets: one in normal order, one backward.
		"*)(mail=*" . implode("*", $mailTargets) . "*)(mail=*" . implode("*", array_reverse($mailTargets)) . "*))";
		
	// Put the gathered clauses in the person search template.
    $searchFilter = str_replace("%s", $joinedClauses, NAME_SEARCH_FILTER);
    return($searchFilter);	
}

?>
