<?php

require_once LIBDIR ."/harvard_ical_lib.php";

define('HARVARD_EVENTS_ICS_BASE_URL', 'http://www.trumba.com/calendars/gazette.ics');

$data = array();

function clean_up_ical_event($event) {

     $event_dict = Array();
     // we'll give the event a random unique ID since there's nothing
     // useful we can do with it on the backend

     $event_dict['id'] = crc32($event->get_uid()) >> 1; // 32 bit unsigned before shift
     $event_dict['title'] = $event->get_summary();
     $event_dict['start'] = $event->get_start();
     $event_dict['end'] = $event->get_end();
     // location and description are always blank but just for completeness...
     if ($location = $event->get_location()) {
       $event_dict['location'] = $location;
     }
     if ($description = $event->get_description()) {
       $event_dict['description'] = $description;
     }

     if ($custom = $event->get_customFields()) {
     	$event_dict['custom'] = $custom;
     }

     return $event_dict;
}



function retrieveData($urlLink, $dateString) {

     $yr = substr($dateString, 0, 4);
     $mth = substr($dateString, 4, 2);
     $filename = ACADEMIC_CALENDAR_CACHE_DIR . $yr . $mth . '.ics';

/*
     if (!file_exists($filename)) {
          $fh = fopen($filename, 'w');
          fwrite($fh, file_get_contents($urlLink));
          fclose($fh);
        }
*/

}


function makeICAL($icsURL, $dateString)
{
	//retrieveData($icsURL, $dateString);
	$ical = new ICalendar($icsURL);
	
	$yr = (int)substr($dateString, 0, 4);
	$mth = (int)substr($dateString, 4, 2);
	$day = (int)substr($dateString, 6, 2);

	$time = mktime(0,0,0, $mth,$day,$yr); 

	return  $ical->get_day_events($time);

	
}

switch ($_REQUEST['command']) {
  case 'day':
   $type = $_REQUEST['type'];
   $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
   
   $date = date('Ymd', $time);
   $events = array();

   $url = HARVARD_EVENTS_ICS_BASE_URL ."?startdate=" .$date;

   if ($type == 'Events') {
     $events = makeICAL($url, $date);
   }

    elseif ($type == 'Exhibits') {
     $events = MIT_Calendar::TodaysExhibitsHeaders($date);
    }

  // $events = makeICAL($url, $date);

   foreach ($events as $event) {
     $data[] = clean_up_ical_event($event);
   }
   break;

  default:
   break;
}

echo json_encode($data);

?>