<?php

require LIBDIR . "/harvard_calendar.php";
require "calendar_lib.inc";

function requestString($paramName, $defaultValue = "")
{
	return isset($_REQUEST[$paramName]) ? $_REQUEST[$paramName] : $defaultValue;
}

$id = requestString('id');
$name = requestString('name');

$GLOBALS['calendarExtraDetailParams'] = array(
  'back'    => 'Category',
  'catName' => $name,
  'catID'   => $id,
);


if (strlen($id) > 0) {
	
	// These variables are used for the next day/previous day links in the html.
	$time = requestString('time', time());
	$dayRange = new DayRange(time());
	$isToday = $dayRange->contains(new TimeRange($time));
	
	$current = day_info($time);
	$next = day_info($time, 1);
	$prev = day_info($time, -1);	

	// Construct events query.
	$start = date('Ymd', $time);
	$startdate = "?startdate=" . $start ."&days=1";
	$url = HARVARD_EVENTS_ICS_BASE_URL . $startdate ."&filter1=" .$id ."&filterfield1=15202";
        
	// We're setting up $events because it will be picked up in the html file and printed.
	$events = array();
        $events = makeIcalDayEvents($url, $start, $id);
}

// Content is used by the html file to print the events.
$content = new ResultsContent(
  	"items", 
	"calendar", 
	$page,
	array(
	  "id"        => $id,
	  "timeframe" => 0,
	  "from" => "category",
	),
	FALSE // Do not include the search form.
);

require "$page->branch/category.html";
$page->output();

?>
