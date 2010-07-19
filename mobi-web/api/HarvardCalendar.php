<?php

require_once LIBDIR ."/harvard_ical_lib.php";
require_once LIBDIR ."/harvard_calendar.php";

define('PATH_TO_EVENTS_CAT', DATADIR . '/event_cat');


define('HARVARD_EVENTS_ICS_BASE_URL', 'http://www.trumba.com/calendars/gazette.ics');

define("ACADEMIC_CALENDAR_CACHE_DIR", CACHE_DIR . "/ACADEMIC_CALENDAR/");
define("ACADEMIC_CALENDAR_CACHE_LIFESPAN", 60*15); //retrieve info from Trumba if the data is more than 15 mins old

$data = array();

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



function retrieveData($urlLink, $dateString, $searchField=NULL, $category=NULL) {

     $yr = substr($dateString, 0, 4);
     $mth = substr($dateString, 4, 2);

     if (($searchField == NULL) &&($category == NULL))
        $filename = ACADEMIC_CALENDAR_CACHE_DIR . $yr . $mth . '.ics';

     else if (($searchField != NULL) && ($category == NULL))
         $filename = ACADEMIC_CALENDAR_CACHE_DIR . $dateString .'search=' .$searchField .'.ics';

     else if (($searchField == NULL) && ($category != NULL))
         $filename = ACADEMIC_CALENDAR_CACHE_DIR . $yr . $mth .'category=' .$category .'.ics';


     if (!file_exists($filename) ||
                filemtime($filename) < time() - ACADEMIC_CALENDAR_CACHE_LIFESPAN) {
          $fh = fopen($filename, 'w');
          fwrite($fh, file_get_contents($urlLink));
          fclose($fh);
        }

        return $filename;
}


function makeIcalDayEvents($icsURL, $dateString, $category=NULL)
{
	$fileN = retrieveData($icsURL, $dateString, NULL, $category);
        $ical = new ICalendar($fileN);
	
	$yr = (int)substr($dateString, 0, 4);
	$mth = (int)substr($dateString, 4, 2);
	$day = (int)substr($dateString, 6, 2);

	$time = mktime(0,0,0, $mth,$day,$yr); 

	return  $ical->get_day_events($time);

	
}


function makeIcalSearchEvents($icsURL, $terms)
{
	$time = time();
        $date = date('Ymd', $time);
        
        $fileN = retrieveData($icsURL, $date, $terms, NULL);
        $ical = new ICalendar($fileN);
	//$ical = new ICalendar($icsURL);

	return  $ical->search_events($terms, NULL);
}





switch ($_REQUEST['command']) {
  case 'day':
   $type = $_REQUEST['type'];
   $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();

   $date1 = date('Ym', $time);
   $month = "?startdate=" .$date1 ."01&months=1";

   $date = date('Ymd', $time);
   $events = array();

  // $url = HARVARD_EVENTS_ICS_BASE_URL ."?startdate=" .$date;
   // extracting data for one month at a time
    $url = HARVARD_EVENTS_ICS_BASE_URL .$month; 
   if ($type == 'Events') {
       $events = makeIcalDayEvents($url, $date, NULL);
   }


   foreach ($events as $event) {
     $data[] = clean_up_ical_event($event);
   }
   break;


   case 'search':
     $searchString = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';

       // retrieve data for the week
       $url = HARVARD_EVENTS_ICS_BASE_URL ."?days=7" ."&search=" .$id ."&filterfield1=15202";
     
       $events = makeIcalSearchEvents($url, $searchString);

       foreach ($events as $event) {
            $event_data[] = clean_up_ical_event($event);
        }

         $data['events'] = $event_data;
   break;

   case 'category':
    if ($id = $_REQUEST['id']) {
     $start = isset($_REQUEST['start']) ? $_REQUEST['start'] : time();
     $end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $start + 86400;

     $date1 = date('Ym', $start);
     $start = date('Ymd', $start);
     $end = date('Ymd', $end);
     //retrieve data for the whole month at a time
     $month = "?startdate=" .$date1 ."01&months=1";
     $url = HARVARD_EVENTS_ICS_BASE_URL .$month ."&filter1=" .$id ."&filterfield1=15202";

    $events = makeIcalDayEvents($url, $start, $id);

    foreach ($events as $event) {
        $data[] = clean_up_ical_event($event);
        }
    }
   break;

   case 'categories':
    $categories = Harvard_Calendar::get_categories(PATH_TO_EVENTS_CAT);

       foreach ($categories as $categoryObject) {
     
           $name = ucwords($categoryObject->get_name());
           $catid = $categoryObject->get_cat_id();
           $url = $categoryObject->get_url();

           $catData = array('name' => $name,
		      'catid' => $catid,
                      'url' => $url);

           $data[] = $catData;
           }
   break;

   default:
       break;

 
}

echo json_encode($data);

?>