<?
require_once "../config/mobi_web_constants.php";
require_once("api_header.php");

$module = $_REQUEST['module'];
PageViews::log_api($module, 'iphone');

switch ($module) {
 case 'emergency':
   if (isset($_REQUEST['command']) && $_REQUEST['command'] == 'contacts') {
     $data = json_decode(file_get_contents(LIBDIR . "EmergencyContacts.json"));
   } else {
     require LIBDIR . "rss_services.php";
     $Emergency = new Emergency();
     $data = $Emergency->get_feed_html();
     if($data === False) {
       $data = array('Emergency information is currently not available');
     }
   }
   echo json_encode($data);
   break;

 case 'people':
   require LIBDIR . 'mit_ldap.php';
   $raw_people = mit_search($_REQUEST['q']);
   $people = Array();
   foreach ($raw_people as $raw_person) {
     $person = Array();
     foreach ($raw_person as $attribute => $value) {
       if ($value) $person[$attribute] = $value;
     }
     $people[] = $person;
   }
   echo json_encode($people);
   break;

 case 'shuttleschedule':
   /* no parameters: return list of routes and associated top-level info
    * &route=routeName: return list of stops and associated info
    * &route=routeName&stop=stopName: return multiple predictions
    * &route=routeName& ... blah
    */
   require LIBDIR . "ShuttleSchedule.php";
   require LIBDIR . "NextBusReader.php";
   NextBusReader::init();

   $now = time();
   $data = Array();
   if (!$_REQUEST['route']) {
     $routes = ShuttleSchedule::get_active_routes();
     foreach ($routes as $route) {
       $data[$route] = Array();
       $data[$route]['title'] = ShuttleSchedule::get_title($route);
       $data[$route]['summary'] = ShuttleSchedule::get_summary($route);
       $data[$route]['interval'] = ShuttleSchedule::get_interval($route) / 60;
       $data[$route]['isSafeRide'] = ShuttleSchedule::is_safe_ride($route);
       $isRunning = ShuttleSchedule::is_running($route);
       $data[$route]['isRunning'] = $isRunning;
       if ($isRunning) {
	 $nbRoute = ShuttleSchedule::get_nextbus_id($route);
	 $data[$route]['gpsActive'] = NextBusReader::gps_active($nbRoute);
       }
     }
   } else {
     $route = $_REQUEST['route'];

     $data['routeTag'] = $route;
     $data['title'] = ShuttleSchedule::get_title($route);
     $data['summary'] = ShuttleSchedule::get_summary($route);
     $data['interval'] = ShuttleSchedule::get_interval($route) / 60;
     $data['isSafeRide'] = ShuttleSchedule::is_safe_ride($route);
     $data['isRunning'] = ShuttleSchedule::is_running($route);
     $data['stops'] = Array();

     $schedStops = ShuttleSchedule::get_next_scheduled_loop($route, $now);

     $nbRoute = ShuttleSchedule::get_nextbus_id($route);
     $nbStopsInfo = NextBusReader::get_route_info($nbRoute);
     $data['gpsActive'] = NextBusReader::gps_active($nbRoute);
     if ($data['gpsActive']) {
       $data['vehicleLocations'] = NextBusReader::get_coordinates($nbRoute);
       $nbPredictions = NextBusReader::get_predictions($nbRoute);
     }

     $stopData = Array();
     foreach ($schedStops as $stop) {
       $stopData['title'] = $stop['title'];
       $stopData['nextScheduled'] = $stop['nextScheduled'];
       $nbId = $stop['nextBusId'];
       $stopData['lat'] = $nbStopsInfo[$nbId]['lat'];
       $stopData['lon'] = $nbStopsInfo[$nbId]['lon'];
       $stopData['path'] = $nbStopsInfo[$nbId]['path'];
       if ($data['gpsActive']) {
	 $stopData['predictions'] = $nbPredictions[$nbId];
       }

       $data['stops'][] = $stopData;
     }
   }
   echo json_encode($data);

   break;

 case 'stellar':
   require LIBDIR . 'StellarData.php';

   if (isset($_REQUEST['command'])) {
     $data = Array();
     switch ($_REQUEST['command']) {
     case 'courses':
       $courses = StellarData::get_courses();
       foreach ($courses as $short => $course) {
	 if ($short == 99)
	   continue;
	 $is_course = ($course['is_course']) ? 1 : 0;
	 $data[] = Array(
	   'short' => sprintf('%s', $short),
	   'name' => $course['name'],
	   'is_course' => $is_course,
	   );
       }
       break;
     case 'subjectList':
       $courseId = urldecode($_REQUEST['id']);
       $subjectList = StellarData::get_subjects_with_xref($courseId);
       foreach ($subjectList as $subjectId => $info) {
	 $info['term'] = StellarData::get_term();
	 $data[] = $info;
       }
       break;
     case 'subjectInfo':
       $subjectId = urldecode($_REQUEST['id']);
       $data = StellarData::get_subject_info($subjectId);
       if($data) {
         $data['announcements'] = StellarData::get_announcements($subjectId);
       
         // some classes dont have stellar announcements
         if($data['announcements'] === False) {
	   unset($data['announcements']);
         }

         $data['term'] = StellarData::get_term();
       } else {
	 $data = array('error' => 'SubjectNotFound', 'message' => 'Stellar could not find this subject'); 
       }
       break;
     case 'search':
       $query = urldecode($_REQUEST['query']);
       $data = StellarData::search_subjects($query);
       $term = StellarData::get_term();
       foreach($data as $index => $value) {
         $data[$index]['term'] = $term;
       }
       break;
     case 'term':
       $data = array('term' => StellarData::get_term());
       break;

     case 'myStellar':
       require_once 'push/apns_lib.php';
       $pass_key = intval($_REQUEST['pass_key']);
       $device_id = intval($_REQUEST['device_id']);       
       $device_type = $_REQUEST['device_type'];       
       $subject = $_REQUEST['subject'];
       $term = $_REQUEST['term'];

       if($device_type == 'apple') {
         if(!APNS_DB::verify_device_id($device_id, $pass_key)) {
           Throw new Exception("invalid {$pass_key} for {$device_id}");
         }
       } else {
	 Throw new Exception("Device type='${device_type}' not yet supported");
       }

       switch($_REQUEST['action']) {
         case 'subscribe':
	   StellarData::push_subscribe($subject, $term, $device_id, $device_type);
           $data = array('success' => True);
           break;
         case 'unsubscribe':
	   StellarData::push_unsubscribe($subject, $term, $device_id, $device_type);
           $data = array('success' => True);
           break;
       }

     default:
       break;
     }
     echo json_encode($data);
   }
   break;

 case 'help':
   $help = Array(
    'people' => Array('q', 'Get results for search term'),
    'map' => Array('q', 'Get results for search term'),
    'shuttleschedule' => Array(
      'no args' => 'List of routes',
      'route' => 'Get predictions for this route'),
    'emergency' => Array('no arge' => 'Show current emergency status'),
    );
   echo json_encode($help);
   break;

 case 'push':
   switch ($_REQUEST['device_type']) {
     case 'apple':
       require "push/apns_lib.php";

       $command = ($_REQUEST['command']);
       if($command == 'register') {
         $identifiers = APNS_DB::create_device_pass_key();
         if(isset($_REQUEST['device_token'])) {
	   APNS_DB::register_device_token($_REQUEST['device_token'], $identifiers['device_id'], $_REQUEST['app_id']);
         }
         echo json_encode($identifiers);
       
       } else {
         $device_id = $_REQUEST['device_id'];

	 if(!APNS_DB::verify_device_id($device_id, $_REQUEST['pass_key'])) {
	   throw new Exception('invalid pass_key');
	 }
         
         if($command == 'newDeviceToken') {
	   APNS_DB::register_device_token($_REQUEST['device_token'], $device_id, $_REQUEST['app_id']);
           echo json_encode(array('success' => True));

         } elseif($command == 'moduleSetting') {
           $enabled = (bool)intval($_REQUEST['enabled']);
           $module = $_REQUEST['module_name'];
	   APNS_DB::set_module_setting($device_id, $module, $enabled);
           echo json_encode(array('success' => True, 'module' => $module, 'enabled' => $enabled));

         } else {
           $device = APNS_DB::get_device($device_id);
           $unreads = $device['unread_notifications'];

           if($command == 'getUnreadNotifications') {
	     echo json_encode($unreads);
                    
           } elseif($command == 'markNotificationsAsRead') {
             
             foreach(json_decode($_REQUEST['tags']) as $readNotification) {
               $position = array_search($readNotification, $unreads);
               if($position !== False) {
                 array_splice($unreads, $position, 1);
               }
	     }
	     APNS_DB::save_unread_notifications($device_id, $unreads);
             echo json_encode($unreads);
           }
         }
       }
       break;

     default:
       echo 'device type not supported';
       break;
   }
   break;

 default:
   echo 'not a valid query';
   break;
}

?>
