<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$reader = new TranslocReader();

$agenciesAnnouncements = $reader->getAnnouncements();
$agencyId = $_REQUEST['agencyId'];
$announcementIndex = $_REQUEST['index'];

foreach($agenciesAnnouncements['agencies'] as $agencyAnnouncements) {
    if($agencyAnnouncements['name'] == $agencyId) {
        $announcement = $agencyAnnouncements['announcements'][$announcementIndex];
        break;
    }
}

require "$page->branch/announcement.html";
$page->output();

?>
