<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'harvard_calendar.php';

function clean_up_ical_event($event) {

     $event_dict = Array();
     // we'll give the event a random unique ID since there's nothing
     // useful we can do with it on the backend

     $event_dict['id'] = crc32($event->get_uid()) >> 1; // 32 bit unsigned before shift
     $event_dict['title'] = $event->get_summary();
     $event_dict['start'] = $event->get_start();
     $event_dict['end'] = $event->get_end();

     if ($urlLink = $event->get_url()) {
         $event_dict['url'] = $urlLink;
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

//require_once 'harvard_ical_lib.php';


        $date = '20100901';
        $academic = 'academic';
        $academic_ics_url = 'http://www.trumba.com/calendars/harvard_academic_calendar.ics?startdate=20100901&months=1';
        $fileN = TrumbaCache::retrieveData($academic_ics_url, $date, NULL, $academic);

        $ical = new ICalendar($fileN);

        $events = array();
        $events = $ical->search_events(NULL, NULL);

           foreach ($events as $event) {
     $data[] = clean_up_ical_event($event);
   }
       //echo json_encode($ical->search_events(NULL, NULL));
?>
