<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "AcademicCalendar.php";
require_once "calendar_lib.php";

$year = $_REQUEST['year'];
if (!$year) {
  $year = date('Y', time());
  if (date('n', time()) > 6) {
    $year += 1;
  }
}
$next = $year + 1;
$prev = $year - 1;

$holidays = AcademicCalendar::get_holidays($year);
$nav_links = Array();

// $year corresponds to fiscal year
// which is the later year of the school year
$year_text = "$prev-&shy;$year";

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
    $prev_text = $prev - 1 . '-' . $prev;
    $nav_links[] = "<a href=\"$prev_url\">&lt; $prev_text</a>"; 
  }
  $next_holidays = AcademicCalendar::get_holidays($next);
  if (count($next_holidays) > 0) {
    $next_url = holidaysURL($next);
    $next_text = $next - 1 . '-' . $next;
    $nav_links[] = "<a href=\"$next_url\">$next_text &gt;</a>"; 
  }

  require "$page->branch/holidays.html";
}

$page->output();

?>
