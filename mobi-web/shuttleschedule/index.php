<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$now = time();

$reader = new TranslocReader();

$agencies = $reader->getAgenciesAndNames();

// this method is only called so that we make a call back
// to transloc, so transloc can tabulate accurate statistics
header("refresh: 180;url=index.php?autorefreshed=true");
if(!$_REQUEST['autorefreshed']) {
    $reader->refreshSetup();
}

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

$contacts = array(
    "Shuttle Service" => array(
        array("name" => "Shuttle Bus and Van Service", "number" => "617-495-0400"),
        array("name" => "Parking Service",             "number" => "617-495-3772"),
        array("name" => "CommuterChoice",              "number" => "617-384-7433"),
        array("name" => "Motorist Assistance Program", "number" => "617-496-4375"),
        array("name" => "M2 Shuttle",                  "number" => "617-632-2800"),
     ),
    "Emergency Phone Numbers" => array(
         array("name" => "University Police",          "number" => "617-495-1212"),
         array("name" => "Health Services",            "number" => "617-495-5711"),
     ),
);


// this populates the $infoItems data
require "shuttle_info.inc";

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

function infoURL($infoItem) {
    return "info.php?id={$infoItem['id']}";
}

function phoneURL($phoneItem) {
    return 'tel:+1' . str_replace('-', '', $phoneItem['number']);
}


?>
