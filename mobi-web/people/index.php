<?php

$errorMessage = "";

require LIBDIR . "/LdapWrapper.php";

$failed_search = FALSE;

$phoneNumbers = array(
    'General Directory' => '6174951000',
    'Student Directory' => '6174931000',
    'Faculty/Staff Directory' => '6174955000',
    );

if (isset($_REQUEST['username']) && $username = $_REQUEST['username']) {
  $ldapWrapper = new LdapWrapper();
  $person = $ldapWrapper->lookupUser($username);
  if ($person) {
    require "$page->branch/detail.html";
  } else {
    $errorMessage = $ldapWrapper->getError();
    require "$page->branch/detail.html";
  }

} elseif (isset($_REQUEST['filter'])
    && $searchTerms = stripslashes(trim($_REQUEST['filter']))) {

  $ldapWrapper = new LdapWrapper();
  if ($ldapWrapper->buildQuery($searchTerms)
      && ($people = $ldapWrapper->doQuery()) !== FALSE) {

    $total = count($people);
    switch ($total) {
      case 0:
        $failed_search = TRUE;
        require "$page->branch/index.html";
        break;
      case 1:
        $person = $people[0];
        require "$page->branch/detail.html";
        break;
      default:
        $content = new ResultsContent("items", "people", $page);
        require "$page->branch/results.html";
        break;
    }

  } else {
    $errorMessage = $ldapWrapper->getError();
    require "$page->branch/index.html";
  }

} else {

  $page->cache();
  require "$page->branch/index.html";
}

$page->output();

function addSilentHyphens($number) {
  return substr($number, 0, 3) . '&shy;-'
       . substr($number, 3, 3) . '&shy;-'
       . substr($number, 6);
}

function detail_url($person)
{
    return $_SERVER['SCRIPT_NAME'] . '?username=' . urlencode($person->getId()) . '&filter=' . urlencode($_REQUEST['filter']);
}

function phoneHREF($number)
{
    // check if number already starts with "+1"
    if(strpos($number, "+1") != 0) {
        $number = "+1" . $number;
    }
    return 'tel:' . str_replace('-', '', $number);
}

function mailHREF($email)
{
    return "mailto:$email";
}

function has_phone($person)
{
  return ($person->getField('homephone')
    || $person->getField('facsimiletelephonenumber')
    || $person->getField('telephonenumber'));
}

function officeURL($address) {
    // Only send the next-to-last line of the address to the map search.
    $addressLines = explode("$", $address);
    $lineCount = count($addressLines);
    if ($lineCount > 1) {
	$linkAddress = $addressLines[$lineCount - 2];
    } else {
        $linkAddress = $address;
    }

    return mapURL($linkAddress);
}

?>
