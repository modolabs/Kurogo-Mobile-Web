<?

require_once "../../config/mobi_web_constants.php";
require_once WEBROOT . "shuttleschedule/shuttle_lib.php";
$APIROOT = WEBROOT . "/api";
require_once $APIROOT . "/api_header.php";
PageViews::log_api('shuttles', 'iphone');

$data = Array();
$command = $_REQUEST['command'];

switch ($command) {
 case 'stops':
   $stops = NextBusReader::get_all_stops();
   $data = array_values($stops);
   break;
 case 'stopInfo':
   $stops = NextBusReader::get_all_stops();
   $time = time();
   $stopId = $_REQUEST['id'];
   $stopInfo = $stops[$stopId];
   if (!$stopInfo) {
     error_log("failed to get stop info for $stopId");
     $data = Array('error' => 'could not get stop info');
   }

   $data['stops'] = Array();
   foreach ($stopInfo['routes'] as $route) {
     $gpsActive = ShuttleSchedule::is_running($route) && NextBusReader::gps_active($route);
     $stopTimes = list_stop_times($route, $time, $gpsActive, $stopId);

     $routeInfoForStop = $stopTimes;
     $routeInfoForStop['route_id'] = $route;
     $routeInfoForStop['gps'] = $gpsActive;

     $data['stops'][] = $routeInfoForStop;
   }
   $data['now'] = $time;

   break;
 case 'routes': // static info about all routes
   $route_ids = ShuttleSchedule::get_active_routes();
   foreach ($route_ids as $route) {
     $routeInfo = get_route_metadata($route);

     if ($_REQUEST['compact'] == 'true') {
       unset($routeInfo['summary']);
     } else {
       $routeInfo['stops'] = Array();
       if ($stops = NextBusReader::get_route_info($route)) {
	 foreach ($stops as $stop_id => $stopInfo) {
	   $stopInfo['stop_id'] = $stop_id;
	   $routeInfo['stops'][] = $stopInfo;
	 }
       }
     }

     $data[] = $routeInfo;
   }
   break;
 case 'routeInfo': // live info for individual routes
   $route = $_REQUEST['id'];
   $time = time();
   if ($route) {

     $gpsActive = NextBusReader::gps_active($route);
     $stopTimes = list_stop_times($route, $time, $gpsActive);

     if ($_REQUEST['full'] == 'true') {
       $data = get_route_metadata($route);

       $stops = Array();
       if ($stops = NextBusReader::get_route_info($route)) {
	 foreach ($stopTimes as $index => $stopTimeInfo) {
	   $stopInfo = $stops[$stopTimeInfo['id']];
	   foreach ($stopInfo as $property => $value) {
	     if ($property == 'title')
	       continue;
	     $stopTimes[$index][$property] = $value;
	   }
	 }
       }
     }

     $data['stops'] = $stopTimes;

     if ($gpsActive) {
       $data['gpsActive'] = TRUE;
       $data['vehicleLocations'] = NextBusReader::get_coordinates($route);
     }

     $data['now'] = $time;

   } else {
     $data = Array('error' => "no route parameter");
   }

   break;
 case 'subscribe': case 'unsubscribe':
   require_once $APIROOT . '/push/apns_lib.php';

   $data = Array('error' => "could not perform $command");
   if ($sub = APNSSubscriber::create()) {
     $route = $_REQUEST['route'];
     $params = Array(
       'route_id' => $route,
       'stop_id' => $_REQUEST['stop'],
       );

     // unsubscribe any existing subscriptions for the same route/stop
     if ($sub->unsubscribe("ShuttleSubscription", $params) && $command == 'unsubscribe') {
       $data = Array('success' => $command);

     } else {
       $request_time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();

       $interval = ShuttleSchedule::get_interval($route);
       if (($numshuttles = ShuttleSchedule::count_shuttles_running($route, $request_time)) > 1) {
	 $interval /= $numshuttles;
       }

       $start_time = $request_time - intval($interval / 2);
       $expire_time = $start_time + intval($interval * 1.5);

       $params['start_time'] = $start_time;
       if ($sub->subscribe("ShuttleSubscription", $params))
	 $data = Array('success' => $command,
		       'start_time' => $start_time,
		       'expire_time' => $expire_time);
     }
   }
   break;
}

echo json_encode($data);

function get_route_metadata($route) {
  $metadata = Array();
  $metadata['route_id'] = $route;
  $metadata['title'] = ShuttleSchedule::get_title($route);
  $metadata['interval'] = ShuttleSchedule::get_interval($route) / 60;
  $metadata['isSafeRide'] = ShuttleSchedule::is_safe_ride($route);
  $metadata['isRunning'] = ShuttleSchedule::is_running($route);
  $metadata['summary'] = ShuttleSchedule::get_summary($route);
  return $metadata;
}

?>
