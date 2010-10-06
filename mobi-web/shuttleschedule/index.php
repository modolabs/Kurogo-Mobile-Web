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

$agenciesAnnouncements = $reader->getAnnouncements();
$agencyAnnouncementsAndName = array();

foreach($agencies as $agencyID => $agencyName) {
    $tempAnnouncments = array('name' => $agencyID, 'long_name' => $agencyName);
    foreach($agenciesAnnouncements['agencies'] as $announcements) {

        if($announcements['name'] == $agencyID) {
           $tempAnnouncments = array_merge($tempAnnouncments, $announcements);
           break;
        }
    }
    $agenciesAnnouncementsAndName[] = $tempAnnouncments;
}

$tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'Running';

$tabs = new Tabs(selfURL(), "tab", array("Running", "Offline", "News", "Info"));

$tabs_html = $tabs->html($page->branch);


require "$page->branch/index.html";
$page->output();

function selfURL() {
  $params = $_GET;
  unset($params['tab']);
  return 'index.php?' . http_build_query($params);
}

function announcementURL($agency, $index) {
    return "announcement.php?agencyId=$agency&index=$index";
}

?>
