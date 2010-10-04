<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$now = time();

$reader = new TranslocReader();

$agencies = $reader->getAgenciesAndNames();

$runningRoutes = array();
foreach ($agencies as $agencyID => $agencyName) {
  $runningRoutes[$agencyID] = $reader->getRunningRoutesAndNamesForAgency($agencyID);
}
$nonRunningRoutes = array();
foreach ($agencies as $agencyID => $agencyName) {
  $nonRunningRoutes[$agencyID] = $reader->getNonRunningRoutesAndNamesForAgency($agencyID);
}

$announcements = $reader->getAnnouncements();
// print_r($announcements);

require "$page->branch/index.html";
$page->output();
?>
