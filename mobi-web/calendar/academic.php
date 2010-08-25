<?php

require_once LIBDIR . "/harvard_calendar.php";
require_once "calendar_lib.inc";

$time = time();
$year = date('Y', $time);

if (isset($_REQUEST['year'])) {
	$year = $_REQUEST['year'];	
}

// Passing false as the last param to get ICalEvents instead of cleaned dict representations of them.
$events = get_academic_events($year, FALSE); 
$days = Array();

foreach ($events as $event) {
  $dateTitle = formatDayTitleForAcademicEvents($event);
  $summary = $event->get_summary();
  // bolden all-caps at the beginning of the string
  $summary = preg_replace('/^([^a-z]{2,})/', "<b>$1</b>", $summary, 1);

  if (!array_key_exists($summary, $days)) {
    $days[$summary][] = $dateTitle;
  }
}

$prev_yr = $year - 1;
$next_yr = $year + 1;
$next_next_yr = $year + 2;

// expensive way to see if there are past/future events
$nav_links = Array();
$prev_events = get_academic_events($prev_yr, FALSE);
if (count($prev_events) > 0) {
  $prev_url = academicURL($prev_yr);
  $nav_links[] = "<a href=\"$prev_url\">&lt; $prev_yr-$year</a>";
}

$next_events = get_academic_events($next_yr, FALSE);
if (count($next_events) > 0) {
  $next_url = academicURL($next_yr);
  $nav_links[] = "<a href=\"$next_url\">$next_yr-$next_next_yr &gt;</a>";
}

require "$page->branch/academic.html";
$page->output();


?>
