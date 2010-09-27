<?php

require_once LIBDIR .'/harvard_calendar.php';


$data = array();

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


       $date = date('Ymd', time());
       $searchString = urlencode($searchString);
       $url = HARVARD_EVENTS_ICS_BASE_URL . "?startdate=" . $date ."&days=7" ."&search=" .$searchString;

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

   case 'academic':
		$data = get_academic_events($_REQUEST['year']);

                if ($data == null)
                    $data = array();
       	break;

   default:
       break;

 
}

echo json_encode($data);

?>