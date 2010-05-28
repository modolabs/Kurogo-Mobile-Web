<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "/mit_calendar.php";
require "calendar_lib.php";

$category = MIT_Calendar::Category($_REQUEST['id']);
$timeframe = isset($_REQUEST['timeframe']) ? $_REQUEST['timeframe'] : 0;
$search_terms = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : "";

if(isset($_REQUEST['filter'])) {
  $dates = SearchOptions::search_dates($timeframe);
  $events = MIT_Calendar::fullTextSearch($search_terms, $dates['start'], $dates['end'], $category);
} else {
  $today = day_info(time());
  $events = MIT_Calendar::CategoryEventsHeaders($category, $today['date']);
}

$content = new ResultsContent(
  "items", "calendar", $page,
  array(
    "id" => $category->catid,
    "timeframe" => $timeframe
  )
);

$form = new CalendarForm($page, SearchOptions::get_options($timeframe), $category->catid);
$content->set_form($form);

require "$page->branch/category.html";
$page->output();

?>
