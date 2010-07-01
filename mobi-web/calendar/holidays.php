<?php


require_once LIBDIR . "/AcademicCalendar.php";
require_once "calendar_lib.inc";

/*
// this defines the text that will appear in the academica
// calendar
require LIBDIR . "/holiday_data.php";

$year = (int) $_REQUEST['year'];
$next = $year + 1;
$prev = $year - 1;

$holidays = $holiday_data[$year];
if($religious_days[$year]) {
  $religious = $religious_days[$year];
} else {
  $religious = array();
}
*/

$year = $_REQUEST['year'];
if (!$year) {
  $year = date('Y', time());
}
$next = $year + 1;
$prev = $year - 1;

$holidays = AcademicCalendar::get_holidays($year);
$nav_links = Array();



if($_REQUEST['page'] == 'religious') {
  require "$page->branch/religious_text.html";
  //$has_next = isset($religious_days[$next]);
  //$has_prev = isset($religious_days[$prev]);


} else {

  // we should save this data as session variables if we don't
  // want to calculate 3 sets of dates for each page load
  $prev_holidays = AcademicCalendar::get_holidays($prev);
  if (count($prev_holidays) > 0) {
    $prev_url = holidaysURL($prev);
    $nav_links[] = "<a href=\"$prev_url\">&lt; $prev</a>"; 
  }
  $next_holidays = AcademicCalendar::get_holidays($next);
  if (count($next_holidays) > 0) {
    $next_url = holidaysURL($next);
    $nav_links[] = "<a href=\"$next_url\">$next &gt;</a>"; 
  }

  require "$page->branch/holidays.html";
}

$page->output();

?>
