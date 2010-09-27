<?

$data = Array();

require_once LIBDIR . "/mit_calendar.php";
require_once LIBDIR . "/campus_map.php";

function ucwordswrapper($words) {
  $separated = str_replace('/', '/ ', $words);
  $ucwords = ucwords($separated);
  return str_replace('/ ', '/', $ucwords);
}

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

     return $event_dict;
}

function clean_up_event($event) {
  $event->title = trim(strip_tags($event->title));

     // save space by passing timestamps instead of dictionary
     if ($event->start) {
       $event->start = mktime($event->start->hour,
			      $event->start->minute,
			      0,
			      $event->start->month,
			      $event->start->day,
			      $event->start->year);
     }
     if ($event->end) {
       $event->end = mktime($event->end->hour,
			    $event->end->minute,
			    0,
			    $event->end->month,
			    $event->end->day,
			    $event->end->year);
     }

     if (!$event->shortloc) {
       $event->shortloc = $event->location;
     }
     $event->shortloc = preg_replace('/ \(.+\)/', '', $event->shortloc);
     if ($latlon = Buildings::get_lat_lon($event->shortloc)) {
       $event->coordinate = $latlon;
     }

     $event->description = str_replace("\r\n", '<br />', $event->description);

     if (strlen($event->infophone) == 6) {
       switch (substr($event->infophone, 0, 1)) {
       case '3': case '8':
	 $event->infophone = '617-25' . $event->infophone;
	 break;
       case '4':
	 $event->infophone = '617-32' . $event->infophone;
	 break;
       case '2':
	 $event->infophone = '617-45' . $event->infophone;
	 break;
       default:
	 break;
       }
     }

     if ($event->categories) {
       foreach ($event->categories as $category) {
	 $category->name = ucwordswrapper($category->name);
       }
     }

     return $event;
}

switch ($_REQUEST['command']) {
 case 'day':
   $type = $_REQUEST['type'];
   $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
   $date = date('Y/m/d', $time);
   $events = array();
   if ($type == 'Events') {
     $events = MIT_Calendar::TodaysEventsHeaders($date);
   } elseif ($type == 'Exhibits') {
     $events = MIT_Calendar::TodaysExhibitsHeaders($date);
   }

   foreach ($events as $event) {
     $data[] = clean_up_event($event);
   }
   break;

 case 'detail':
   if ($id = $_REQUEST['id']) {
     $event = MIT_Calendar::getEvent($id);
     $data = clean_up_event($event);
   }
   break;

 case 'category': // get events in a single category
   if ($id = $_REQUEST['id']) {
     $start = isset($_REQUEST['start']) ? $_REQUEST['start'] : time();
     $end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $start + 86400;
     $start = date('Y/m/d', $start);
     $end = date('Y/m/d', $end);
     $events = MIT_Calendar::HeadersByCatID($id, $start, $end);
   }

   foreach ($events as $event) {
     $data[] = clean_up_event($event);
   }
   break;

 /* getting events in a single category is the same
  * as using search with an additional category parameter
  */
 case 'search':
   require_once LIBDIR . "/AcademicCalendar.php";
   $searchTerms = isset(urldecode($_REQUEST['q'])) ? urldecode($_REQUEST['q']) : '';
   /*
   $category = isset($_REQUEST['category']) ? 
     MIT_Calendar($_REQUEST['category']) : NULL;
   */

   $offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 7;
   $data['span'] = "$offset days";

   $time = time() + 86400;
   $start = date('Y/m/d', $time);
   $end = date('Y/m/d', $time + 86400 * $offset);
   $event_data = array();
   $events = MIT_Calendar::fullTextSearch($searchTerms, $start, $end, $category);
   foreach ($events as $event) {
     $event_data[] = clean_up_event($event);
   }

   // search academic calendar
   $acadEvents = AcademicCalendar::search_events($searchTerms, date('m', $time), date('Y', $time));
   foreach ($acadEvents as $event) {
     $event_data[] = clean_up_ical_event($event);
   }

   $data['events'] = $event_data;

   break;

 case 'categories': // get full listing of categories
   // this structure just reflects what the web looks like
   // final structure will depend on how we present the UI
   $categories = MIT_Calendar::Categorys();
   foreach ($categories as $categoryObject) {
     $name = ucwordswrapper($categoryObject->name);
     $catid = $categoryObject->catid;
     $catData = array('name' => $name,
		      'catid' => $catid);
     $subcategories = MIT_Calendar::subCategorys($categoryObject);
     if (count($subcategories) > 0) {
       $catData['subcategories'] = array();
       foreach ($subcategories as $subcatObject) {
	 $catData['subcategories'][] = array('name' => ucwordswrapper($subcatObject->name),
					     'catid' => $subcatObject->catid);
       }
     }
     $data[] = $catData;
   }
   break;

 case 'holidays': case 'academic':
   // TODO: see whether any of this code can be consolidated with
   // mobi-web/calendar/academic.php and mobi-web/calendar/holidays.php
   require_once LIBDIR . "/AcademicCalendar.php";
   $month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('n');
   $year = isset($_REQUEST['year']) ? $_REQUEST['year'] : date('Y');

   if ($_REQUEST['command'] == 'holidays') {
     $events = AcademicCalendar::get_holidays($year);
   } else {
     $events = AcademicCalendar::get_events($month, $year);
   }

   foreach ($events as $event) {
     /*
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

     $data[] = $event_dict;
     */
     $data[] = clean_up_ical_event($event);
   }
   break;
}

echo json_encode($data);

?>
