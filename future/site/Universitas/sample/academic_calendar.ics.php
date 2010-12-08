<?php

/* sample academic calendar server */
ini_set('display_errors','no');

$tzid = date_default_timezone_get();

require_once realpath(LIB_DIR.'/ICalendar.php');

$calendar = new ICalendar();
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date('Ymd');
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date('Ymd');
$year = substr($startdate, 0,4);
$uid_base = 'http://imobileu.org/events/academic/';

$sample_events = array(
	array('summary'=>'First Day of Class', 'range'=>new DayRange(mktime(0,0,0,9,1,$year), $tzid), 'uid'=>sprintf("%s%s1", $uid_base, $year)),
	array('summary'=>'Last Day of Class', 'range'=>new DayRange(mktime(0,0,0,12,15,$year), $tzid), 'uid'=>sprintf("%s%s2", $uid_base, $year)),
	array('summary'=>'First Day of Finals Week', 'range'=>new DayRange(mktime(0,0,0,12,1,$year), $tzid), 'uid'=>sprintf("%s%s3", $uid_base, $year)),
	array('summary'=>'Last Day of Finals Week', 'range'=>new DayRange(mktime(0,0,0,12,8,$year), $tzid), 'uid'=>sprintf("%s%s4", $uid_base, $year)),
	array('summary'=>'A special Academic Event', 'range'=>new TimeRange(mktime(8,0,0,10,10,$year),mktime(12,0,0,10,10,$year)), 'uid'=>sprintf("%s%s5", $uid_base, $year)),
	array('summary'=>'Another special Academic Event', 'range'=>new TimeRange(mktime(8,0,0,11,10,$year),mktime(12,0,0,11,10,$year)), 'uid'=>sprintf("%s%s6", $uid_base, $year))
);

foreach ($sample_events as $event_data) {
	$event = new ICalEvent($event_data['summary'], $event_data['range']);
	$event->set_attribute('UID', $event_data['uid']);
	$event->set_attribute('TZID', $tzid);
	$calendar->add_event($event);
}


header('Content-type: text/plain');
print($calendar->outputICS());

?>