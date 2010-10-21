<?php

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "api/api_header.php";

log_api('shuttles');

require_once LIBDIR . "GTFSReader.php";
$data = Array();
$command = $_REQUEST['command'];

switch ($command) {
 case 'stops':
   $data = ShuttleSchedule::getAllStops();
   break;
 case 'stopInfo':
   $stop_id = $_REQUEST['id'];
   $time = time();

   $data['stops'] = ShuttleSchedule::getTimesForStop($stop_id);
   $data['now'] = $time;

   break;
 case 'routes': // static info about all routes
   $route_ids = ShuttleSchedule::getActiveRoutes();
   foreach ($route_ids as $route_id) {
     $routeInfo = get_route_metadata($route_id);

     if (!$_REQUEST['compact']) {
       $routeInfo['stops'] = ShuttleSchedule::list_stop_times($route_id);
       $route = ShuttleSchedule::getRoute($route_id);
       $path = array();
       foreach ($route->anyTrip(time())->shape->points as $point) {
	 $path[] = array('lat' => $point[0], 'lon' => $point[1]);
       }
       // we had each stop's path segment appended to the stop
       // we can split it out later if needed
       $routeInfo['stops'][0]['path'] = $path;
     }

     $data[] = $routeInfo;
   }
   break;
 case 'routeInfo': // live info for individual routes
   $route_id = $_REQUEST['id'];
   $time = time();
   if ($route_id) {

     $stopTimes = ShuttleSchedule::list_stop_times($route_id);
     $gpsActive = $stopTimes[count($stopTimes) - 1]['gps'];
     unset($stopTimes[count($stopTimes) - 1]);

     if ($_REQUEST['full'] == 'true') {
       $data = get_route_metadata($route_id);
       $route = ShuttleSchedule::getRoute($route_id);
       $path = array();
       foreach ($route->anyTrip(time())->shape->points as $point) {
	 $path[] = array('lat' => $point[0], 'lon' => $point[1]);
       }
       // see comment above
       $stopTimes[0]['path'] = $path;
     }

     $data['stops'] = $stopTimes;

     if ($gpsActive) {
       $data['gpsActive'] = TRUE;
       foreach (ShuttleSchedule::getVehicleLocations($route_id) as $id => $location) {
	 if ($id != 'lastUpdate')
	   $data['vehicleLocations'][] = $location;
       }
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
     $route_id = $_REQUEST['route'];
     $params = Array(
       'route_id' => $route_id,
       'stop_id' => $_REQUEST['stop'],
       );

     $route = ShuttleSchedule::getRoute($route_id);

     // unsubscribe any existing subscriptions for the same route/stop
     if ($sub->unsubscribe("ShuttleSubscription", $params) && $command == 'unsubscribe') {
       $data = Array('success' => $command);

     } else {
       $request_time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
       $trip = $route->anyTrip($request_time);
       $interval = $trip->duration();
       $numShuttles = $trip->numShuttlesRunning($request_time);
       if ($numShuttles > 1)
	 $interval /= $numShuttles;

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

function get_route_metadata($route_id) {
  $route = ShuttleSchedule::getRoute($route_id);
  $metadata = Array();
  $metadata['route_id'] = $route->id;
  $metadata['title'] = $route->long_name;
  $metadata['interval'] = $route->anyTrip(time())->duration() / 60;
  $metadata['isSafeRide'] = $route->agency_id == 'saferide';
  $metadata['isRunning'] = $route->isRunning(time());
  $metadata['summary'] = $route->desc;
  return $metadata;
}

?>
