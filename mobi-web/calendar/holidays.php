<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require WEBROOT . "calendar/calendar_lib.php";

// this defines the text that will appear in the academica
// calendar
require LIBDIR . "holiday_data.php";

$year = (int) $_REQUEST['year'];
$next = $year + 1;
$prev = $year - 1;


$holidays = $holiday_data[$year];
if($religious_days[$year]) {
  $religious = $religious_days[$year];
} else {
  $religious = array();
}


if($_REQUEST['page'] == 'religious') {
  require "$page->branch/religious_text.html";
  $has_next = isset($religious_days[$next]);
  $has_prev = isset($religious_days[$prev]);
} else {
  $has_next = isset($holiday_data[$next]);
  $has_prev = isset($holiday_data[$prev]);
  require "$page->branch/holidays.html";
}

$page->output();

function weekday($day, $year) {
  return date('D', strtotime("$day, $year"));
}

?>
