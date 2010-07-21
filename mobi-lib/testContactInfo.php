<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('harvard_ical_lib.php');


function clean_up_ical_event($event) {

     $event_dict = Array();
     // we'll give the event a random unique ID since there's nothing
     // useful we can do with it on the backend

     $event_dict['id'] = crc32($event->get_uid()) >> 1; // 32 bit unsigned before shift
     $event_dict['title'] = $event->get_summary();
     $event_dict['start'] = $event->get_start();
     $event_dict['end'] = $event->get_end();

     if ($urlLink = $event->get_url()) {
         $event_dict['infourl'] = $urlLink;
     }
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

$fileN = 'http://www.trumba.com/calendars/gazette.ics?startdate=20100701&months=1';

$ical = new ICalendar($fileN);

$time = mktime(0,0,0, 07,13,2010);
$events = $ical->get_day_events($time);

   foreach ($events as $event) {
     $data[] = clean_up_ical_event($event);
   }

   echo "\n";
   echo count($data);
   echo "\n";
   //echo json_encode($data);
?>
