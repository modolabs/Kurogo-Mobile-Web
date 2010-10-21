<?

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/security.php";
require_once LIBDIR . "GTFSReader.php";

ssl_required();



GTFSReader::init(SHUTTLE_GTFS_FEED);

if (!isset($_REQUEST['command'])) {
  $agency = 'MIT';
  $host = "maps.google.com";
  $key = 'ABQIAAAApyfLzTs2-U_CbnVoDede9xSJplgjjz27mB55Xrh5YRVdw8_i1hTDV6sshuADgq5rJyOaY_NwiNmnxQ';
  $forbid_editing = 'true';
  $min_lat = 42.3465611;
  $min_lon = -71.1238392;
  $max_lat = 42.3719808;
  $max_lon = -71.0832;
  $urlbase = HTTPROOT . "shuttleschedule/gtfs/index.php";

  require "template.html";
}

switch ($_REQUEST['command']) {
 case 'boundboxstops':
   $nbound = $_REQUEST['n'];
   $ebound = $_REQUEST['e'];
   $sbound = $_REQUEST['s'];
   $wbound = $_REQUEST['w'];
   $limit = $_REQEUST['limit']; // not doing anything with this yet
   $result = GTFSReader::get_bbox_stops($nbound, $ebound, $sbound, $wbound, $limit);
   echo json_encode($result);
   break;
 case 'routes':
   $result = array();
   $route_ids = GTFSReader::get_active_routes();
   foreach ($route_ids as $route_id) {
     $route = GTFSReader::get_route($route_id);
     $result[$route->short_name] = array(
       $route_id,
       $route->short_name,
       $route->long_name,
       );
     ksort($result);
   }
   echo json_encode(array_values($result));
   break;
 case 'routepatterns':
   $route_id = $_REQUEST['route'];
   $route = GTFSReader::get_route($route_id);
   $atrip = $route->trips[0];

   $stops = $atrip->stop_times;
   $first_stop_id = $stops[0][0];
   $last_stop_id = $stops[count($stops) - 1][0];
   $first_stop = GTFSReader::get_stop($first_stop_id);
   $last_stop = GTFSReader::get_stop($last_stop_id);
   $name = $first_stop->name . ' to ' . $last_stop->name . ', '
     . count($stops) . ' stops';

   $time = $_REQUEST['time'];
   $result = array();

   $trip_type = 0; // not sure what this is
   $pattern_id = 991371929; // not sure what this is either...
   $start_sample_index = 0; // work on these later
   $num_after_sample = 0; 

   $sample = array();
   foreach ($route->trips as $trip) {
     // we want to sort trips by the one that is coming up soonest
     $next_start = $trip->next_trip_start($time);
     $time_until = $next_start - $time;
     if ($time_until < 0) {
       $time_until += 86400;
     }
     $sample[$time_until] = array($trip->next_trip_start($time), $trip->id);
   }

   ksort($sample);
   $sample = array_values($sample);
   
   $result[] = array($name, $pattern_id, $start_sample_index, $sample, $num_after_sample, $trip_type);

   echo json_encode($result);
   break;
 case 'tripstoptimes':
   $trip_id = $_REQUEST['trip'];
   $trip = GTFSReader::get_trip($trip_id);
   $stop_arr = array();
   $time_arr = array();
   foreach ($trip->stop_times as $stop_time) {
     $stop_id = $stop_time[0];
     $stop = GTFSReader::get_stop($stop_id);
     $stop_arr[] = array($stop_id, $stop->name, $stop->lat, $stop->lon, 0);
     
     $stop_offset = $stop_time[1];
     $time_arr[] = $stop_offset; // plus something?
   }

   $result = array($stop_arr, $time_arr);
   echo json_encode($result);
   break;
 case 'triprows':
   $trip_id = $_REQUEST['trip'];
   $result = GTFSReader::trip_rows($trip_id);
   echo json_encode($result);
   break;
 case 'tripshape':
   $trip_id = $_REQUEST['trip'];
   $trip = GTFSReader::get_trip($trip_id);
   $result = $trip->shape->points;
   echo json_encode($result);
   break;
 case 'stoptrips':
   $stop_id = $_REQUEST['stop'];
   $time = $_REQUEST['time'];
   $result = GTFSReader::get_trips_for_stop($stop_id, $time);
   echo json_encode($result);
   break;
 case 'stopsearch':
   $query = $_REQUEST['q'];
   break;
 case 'setstoplocation':
   $stop_id = $_REQUEST['id'];
   $lat = $_REQUEST['lat'];
   $lon = $_REQUEST['lng'];
   break;
 case 'savedata':
   break;
 default:
   break;
}

?>
