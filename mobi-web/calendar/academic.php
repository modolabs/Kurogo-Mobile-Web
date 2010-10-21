<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "AcademicCalendar.php";
require_once "calendar_lib.php";

$month = $_REQUEST['month'];
$year = $_REQUEST['year'];
$time = time();
if(!$month) {
  $month = date('n', $time);
}
if(!$year) {
  $year = date('Y', $time);
}
$time = mktime(0, 0, 0, $month, 1, $year);

$events = AcademicCalendar::get_events($month, $year);
$days = Array();

foreach ($events as $event) {
  $dateTitle = formatDayTitle($event);

  if (!array_key_exists($dateTitle, $days)) {
    $days[$dateTitle] = Array();
  }
  $summary = $event->get_summary();

  // bolden all-caps at the beginning of the string
  $summary = preg_replace('/^([ A-Z0-9:\.\-]{2,})/', "<b>$1</b>", $summary, 1);
  $days[$dateTitle][] = $summary;
}

$prev = increment_month($time, -1);
$next = increment_month($time, 1);

$prev_month = date('n', $prev);
$prev_yr = date('Y', $prev);

$next_month = date('n', $next);
$next_yr = date('Y', $next);

// expensive way to see if there are past/future events
$nav_links = Array();
$prev_events = AcademicCalendar::get_events($prev_month, $prev_yr);
if (count($prev_events) > 0) {
  $prev_title = date('F Y', $prev);
  $prev_url = academicURL($prev_yr, $prev_month);
  $nav_links[] = "<a href=\"$prev_url\">&lt; $prev_title</a>";  
}

$next_events = AcademicCalendar::get_events($next_month, $next_yr);
if (count($next_events) > 0) {
  $next_title = date('F Y', $next);
  $next_url = academicURL($next_yr, $next_month);
  $nav_links[] = "<a href=\"$next_url\">$next_title &gt;</a>";
}

require "$page->branch/academic.html";
$page->output();


?>
