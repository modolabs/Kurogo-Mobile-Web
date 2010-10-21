<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";

function replace_bool($arr) {
  foreach ($arr as $key => $value) {
    if (is_array($value)) {
      $arr[$key] = replace_bool($value);
    } else {
      if ($value === TRUE) {
	$arr[$key] = 1;
      } elseif ($value === FALSE) {
	$arr[$key] = 0;
      }
    }
  }
  return $arr;
}

switch ($_REQUEST['module']) {
 case 'emergency':
   $docRoot = getenv("DOCUMENT_ROOT");
   require_once $docRoot . "/mobi-config/mobi_web_constants.php";
   require LIBDIR . "rss_services.php";
   $Emergency = new Emergency();
   $emergency = $Emergency->get_feed();
   if($emergency === False) {
     $text = array('Emergency information is currently not available');
   } else {
     $text = $emergency[0]['text'];
   }
   echo json_encode($text);
   break;

 case 'people':
   $docRoot = getenv("DOCUMENT_ROOT");
   require_once $docRoot . "/mobi-config/mobi_web_constants.php";
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
   $total = count($people);
   $result = Array(
     'resultSet' => Array(
       'totalResultsAvailable' => $total,
       'totalResultsReturned' => $total,
       'firstResultPosition' => 1,
       'result' => $people),
     );
   echo json_encode($result);
   break;

 case 'map':
   $json = file_get_contents('http://map-dev.mit.edu/search?type=query&q=' . $_REQUEST['q'] . '&output=json');
   echo $json;
   break;

 case 'shuttleschedule':
   /* no parameters: return list of routes and associated top-level info
    * &route=routeName: return list of stops and associated info
    * &route=routeName&stop=stopName: return multiple predictions
    * &route=routeName& ... blah
    */
   $docRoot = getenv("DOCUMENT_ROOT");
   require_once $docRoot . "/mobi-config/mobi_web_constants.php";
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
   $docRoot = getenv("DOCUMENT_ROOT");
   require_once $docRoot . "/mobi-config/mobi_web_constants.php";
   require LIBDIR . 'StellarData.php';
   StellarData::init();
   $query = urldecode($_REQUEST['q']);
   $subjects = StellarData::search_subjects($query);
   $doc = new DOMDocument('1.0');
   $doc->formatOutput = TRUE;

   $root = $doc->createElement('results');
   $root = $doc->appendChild($root);

   $results = Array();
   foreach ($subjects as $subjectid => $subjectData) {
     $subject = $doc->createELement('subject');
     $subject = $root->appendChild($subject);

     $name = $doc->createElement('name');
     $name = $subject->appendChild($name);
     $nameText = $doc->createTextNode($subjectData['name']);
     $nameText = $name->appendChild($nameText);

     $masterId = $doc->createElement('masterId');
     $masterId = $subject->appendChild($masterId);
     $masterIdText = $doc->createTextNode($subjectData['masterId']);
     $masterIdText = $masterId->appendChild($masterIdText);

     $course = $doc->createElement('course');
     $course = $subject->appendChild($course);
     $course_parts = explode('.', $subjectData['masterId']);
     $courseText = $doc->createTextNode($course_parts[0]);
     $courseText = $course->appendChild($courseText);

     $title = $doc->createElement('title');
     $title = $subject->appendChild($title);
     $titleText = str_replace('&', ' and ', $subjectData['title']);
     $titleText = str_replace('  ', ' ', $titleText);
     $titleText = $doc->createTextNode($titleText);
     $titleText = $title->appendChild($titleText);
   }
   echo $doc->saveXML();
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

 default:
   echo 'not a valid query';
   break;
}

?>