<?php

require LIBDIR . "/mit_calendar.php";

//defines all the variables related to being today
require "calendar_lib.inc";

$time = $_REQUEST['time'];
$current = day_info($time);
$next = day_info($time, 1);
$prev = day_info($time, -1);
$type = $_REQUEST['type'];
$Type = ucwords($type);

$methodName = "Todays{$Type}Headers";
$events = MIT_Calendar::$methodName($current['date']);

require "$page->branch/day.html";
$page->output();

?>
