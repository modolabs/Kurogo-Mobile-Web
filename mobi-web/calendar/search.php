<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "mit_calendar.php";

require WEBROOT . "calendar/calendar_lib.php";

$search_terms = $_REQUEST['filter'];

$timeframe = isset($_REQUEST['timeframe']) ? $_REQUEST['timeframe'] : 0;
$dates = SearchOptions::search_dates($timeframe);

if ($search_terms) {
  $events = MIT_Calendar::fullTextSearch($search_terms, $dates['start'], $dates['end']);
} else {
  $events = MIT_Calendar::eventsInDateRange($dates['start'], $dates['end']);
}


$content = new ResultsContent("items", "calendar", $page, array("timeframe" => $timeframe));

$form = new CalendarForm($page, SearchOptions::get_options($timeframe));
$content->set_form($form);

require "$page->branch/search.html";
$page->output();

?>
