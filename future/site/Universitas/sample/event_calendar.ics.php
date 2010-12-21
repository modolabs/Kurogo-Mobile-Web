<?php

/* sample event calendar generator. Will generate random events in ICS format
	keep in mind that because the events are generated randomly you will receive different results for each request
	This is used primarily for testing
*/

require_once realpath(LIB_DIR.'/ICalendar.php');

function getTomorrow($timestamp)
{
	$timestamp = mktime(0,0,0,date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)) + 100800;
	return mktime(0,0,0,date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
}

$uid_base = 'http://imobileu.org/events/';
$tzid = date_default_timezone_get();

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date('Ymd');

if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $startdate, $bits)) {
	$year = $bits[1];
	$month = $bits[2];
	$day = $bits[3];
	$startDay = mktime(0,0,0,$month,$day,$year);
} else {
	die("invalid startdate $startdate");
}

if (isset($_GET['filter1'])) {
	die('filter1');
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

if (isset($_GET['days'])) {
	$count = intval($_GET['days']);
	$current_day = mktime(0,0,0,$month,$day,$year);
} elseif (isset($_GET['months'])){
	$count = intval(date('t', $startdate))-1;
	$current_day = mktime(0,0,0,$month,1,$year);
} else { 
	$count = intval(date('t', $startdate))-1;
	$current_day = mktime(0,0,0,$month,1,$year);
}

/* random durations and titles */
$durations = array(1800,2700,3600, 5400, 7200);
$titles = array(
	'Important Lecture',
	'Special Event',
	'Student Group Meeting',
	'Faculty Meeting',
	'Presentation',
	'Concert'
);

$sample_events = array();

for ($i=1;$i<=$count; $i++) {
	$numEvents = rand(1,4);
	
	for ($e=0;$e<$numEvents; $e++) {
        $start = $current_day+(rand(8,18)*3600);
        $end = $start + $durations[array_rand($durations)];
        $event = array('summary'=>$titles[array_rand($titles)], 'range'=>new TimeRange($start,$end), 'uid'=>sprintf("%s%s%s%d%d", $uid_base, $year, $month, $i, $e));
	
        if ($startDay>=$startdate) {
            if (empty($search) || stripos($event['summary'], $search)!== false) { //very crude search
                $sample_events[] = $event;
            }
        }
    }
	$current_day = getTomorrow($current_day);
}

$calendar = new ICalendar();

foreach ($sample_events as $event_data) {
	$event = new ICalEvent($event_data['summary'], $event_data['range']);
	$event->set_attribute('UID', $event_data['uid']);
	$event->set_attribute('TZID', $tzid);
	$calendar->add_event($event);
}


header('Content-type: text/plain');
print($calendar->outputICS());

?>