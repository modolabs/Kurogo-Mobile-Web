<?php

require LIBDIR . "/harvard_calendar.php";

//defines all the variables related to being today
require "calendar_lib.inc";

$time = $_REQUEST['time'];
$current = day_info($time);
$next = day_info($time, 1);
$prev = day_info($time, -1);
$type = $_REQUEST['type'];
$Type = ucwords($type);

$GLOBALS['calendarExtraDetailParams'] = array(
  'back' => 'Day',
  'type' => $_REQUEST['type'],
);


// copied from api/HarvardCalendar.php
$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
$date1 = date('Ym', $time);
$month = "?startdate=" .$date1 ."01&months=1";
$date = date('Ymd', $time);
$url = HARVARD_EVENTS_ICS_BASE_URL .$month; 
$events = makeIcalDayEvents($url, $date, NULL);

require "$page->branch/day.html";
$page->output();

?>
