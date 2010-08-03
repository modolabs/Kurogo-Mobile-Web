<?php

require_once "ldap_config.php";

// Build a query clause like this: (|(&(cn=*firstword*)(cn=*secondword*))(&(mail=*firstword*)(mail=*secondword*)))
// Should match both words, either in the cn or the mail.
// $search should contain the search string, which is assumed to contain one or more words.
function buildNameAndEmailLDAPQuery($search)
{
	$cnClauses = array();
	$mailClauses = array();

	# Gather cn and mail search clauses for each word.
    foreach(preg_split("/\s+/", $search) as $word)
    {
        if($word != "")
        {
            if(strlen($word) == 1)
            {
				array_push($cnClauses, str_replace("%s", $word, NAME_SINGLE_CHARACTER_FILTER));
            }
            else
            {
				array_push($cnClauses, str_replace("%s", $word, "(cn=*%s*)"));
				array_push($mailClauses, str_replace("%s", $word, "(mail=*%s*)"));
            }
        }
    }

	// Assemble the gathered clauses.
	$joinedClauses = "(|(&" . implode($cnClauses) . ")(&" . implode($mailClauses) . "))";
	// Put the gathered clauses in the person search template.
    $searchFilter = str_replace("%s", $joinedClauses, NAME_SEARCH_FILTER);
    return($searchFilter);	
}

?>
