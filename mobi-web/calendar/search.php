<?php

require LIBDIR . "/harvard_calendar.php";

require "calendar_lib.inc";


$search_terms = $_REQUEST['filter'];

$GLOBALS['calendarExtraDetailParams'] = array(
  'back' => 'Search',
  'filter' => $_REQUEST['filter'],
);


//$dates = SearchOptions::search_dates($timeframe);

       // retrieve data for the week
$date = date('Ymd', time());
$url = HARVARD_EVENTS_ICS_BASE_URL . "?startdate=" .$date ."&days=7" ."&search=" .urlencode(stripslashes($search_terms));
 
 $events = makeIcalSearchEvents($url, $search_terms);

$content = new ResultsContent("items", "calendar", $page, array("timeframe" => 0), TRUE);

//$form = new CalendarForm($page, SearchOptions::get_options(0));
//$content->set_form($form);

require "$page->branch/search.html";
$page->output();

?>
