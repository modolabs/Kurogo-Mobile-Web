<?php


require_once "../config/mobi_web_constants.php";
require PAGE_HEADER;
require LIBDIR . "mit_calendar.php";

require "calendar_lib.php";

$search_terms = $_REQUEST['filter'];

$timeframe = isset($_REQUEST['timeframe']) ? $_REQUEST['timeframe'] : 0;
$dates = SearchOptions::search_dates($timeframe);
$events = MIT_Calendar::fullTextSearch($search_terms, $dates['start'], $dates['end']);

$content = new ResultsContent("items", "calendar", $page, array("timeframe" => $timeframe));

$form = new CalendarForm($page, SearchOptions::get_options($timeframe));
$content->set_form($form);

require "$page->branch/search.html";
$page->output();

?>
