<?php

require_once LIBDIR .'/lib_constants.inc';
require_once WEBROOT . "/api/api_header.inc";
PageViews::log_api('shuttles', 'iphone');

//require_once LIBDIR . "/GTFSReader.php";
require_once LIBDIR . "/TranslocReader.php";

$arrows = array(
    '1' => 'n',
    '2' => 'ne',
    '3' => 'e',
    '4' => 'se',
    '5' => 's',
    '6' => 'sw',
    '7' => 'w',
    '8' => 'nw',
  );


$transloc = new TranslocReader();

$data = Array();
$command = $_REQUEST['command'];

switch ($command) {
 case 'about':
   if ($_REQUEST['agency'] == 'harvard') {
     $data = array("html" => aboutHarvardShuttles());
   } elseif ($_REQUEST['agency'] == 'masco') {
     $data = array("html" => aboutMasco());
   }
   break;
 case 'calendar':
   $data = array("html" => shuttlesCalendar());
   break;
 case 'stops':
   $data = get_stops($transloc); // for Transloc, use $mockData
   break;

 case 'stopInfo':
   $stop_id = $_REQUEST['id'];
   $time = time();

   $stops = get_stops($transloc);

   $stopInfoToReturn = array();
   $routes = array();
   foreach($stops as $stop) {
    if (strval($stop['id']) == strval($stop_id)) {
       $routes = $stop['routes'];
       $lat = $stop['lat'];
       $lon = $stop['lon'];
    }
   }

   foreach($routes as $route_id) {
       $stopInfoToReturn[] = array('id'=>$stop_id,
                                  'route_id'=>$route_id,
                                  'lat'=> $lat,
                                  'lon'=> $lon,
                                  'next'=>1284934000,
                                  'gps'=>false);
   }

   $data['stops'] = $stopInfoToReturn;
   $data['now'] = $time;

   break;

   case 'routes': // static info about all routes

     if (!$_REQUEST['compact']) {
       $data = get_all_routes_info($transloc, 'NO');
     }
     else {
         $data = get_all_routes_info($transloc, 'YES');
     }

   break;
 case 'routeInfo': // live info for individual routes
   
   $route_id = $_REQUEST['id'];
   $time = time();
   if ($route_id) {

     $gpsActive = false;
     if ($_REQUEST['full'] == 'true') {
         $data = get_specific_routes_info($transloc, 'NO', $route_id); // for Transloc, use $mockData
         $gpsActive = $data['stops'][count($data['stops']) - 1]['gps'];
         unset($data['stops'][count($data['stops']) - 1]);
     }
     else {
         $data = get_specific_routes_info($transloc, 'YES', $route_id); // for Transloc, use $mockData
         $gpsActive = $data['stops'][count($data['stops']) - 1]['gps'];
         unset($data['stops'][count($data['stops']) - 1]);
     }

     $route = $transloc->getOneRouteInfo($route_id);
     $stopMarkerURL = HARVARD_TRANSLOC_MARKERS.'?'.urlencode(http_build_query(array(
        'm' => 'stop',
        'c' => $route['color'],
        'h' => '4',
      )));

     $data['stopMarkerUrl'] = urldecode($stopMarkerURL);

     $genericIconURL = HARVARD_TRANSLOC_MARKERS.'?'.urlencode(http_build_query(array(
        'm' => 'bus',
        'c' => $route['color'],
        'h' => $arrows[4],
      )));

     $data['genericIconUrl'] = urldecode($genericIconURL);


     if ($gpsActive == true) {
       $data['gpsActive'] = TRUE;     
       $vehicles = $transloc->getVehiclesForRoute($route_id);
       $vehiclesArray = array();
       foreach($vehicles as $vehicle) {
           $heading = $vehicle['h'];
          $arrowIndex = ($heading / 45) + 1.5;
            if ($arrowIndex > 8) { $arrowIndex = 8; }
            if ($arrowIndex < 0) { $arrowIndex = 0; }
            $arrowIndex = floor($arrowIndex);

       $iconURL = HARVARD_TRANSLOC_MARKERS.'?'.urlencode(http_build_query(array(
        'm' => 'bus',
        'c' => $route['color'],
        'h' => $arrows[$arrowIndex],
      )));

      $vehicleLat = $vehicle['ll'][0];
      $vehicleLon = $vehicle['ll'][1];
      $vechicleSecsSinceReport = 3600;
      $vehicleHeading = $vehicle['h'];
      $vehicleIconUrl = urldecode($iconURL);

      if (($vehicleLat) && ($vehicleLon)) {
        $vehicleData = array('id' => $vehicle['id'],
                             'lat'=>$vehicle['ll'][0],
                             'lon'=>$vehicle['ll'][1],
                             'secsSinceReport'=> 3600,
                             'heading'=>$vehicle['h'],
                             'iconURL'=> urldecode($iconURL));
        if (array_key_exists('s', $vehicle)) $vehicleData['speed'] = $vehicle['s'];
        $vehiclesArray[] = $vehicleData;
      }
     }

       $data['vehicleLocations'] = $vehiclesArray;
     }
     
     $data['now'] = $time;

   } else {
     $data = Array('error' => "no route parameter");
   }

   break;

 case 'announcements':
     $data = json_decode($transloc->getAnnouncementsJSON());
     break;

 /*
 case 'subscribe': case 'unsubscribe':
   require_once 'push/apns_lib.php';

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
*/
}

echo json_encode($data);

function get_stops($translocObj) {
    $stops = $translocObj->getStops();
    $allRoutes = $translocObj->getAllRoutesInfo();
    //print_r($allRoutes);

    $stopsToReturn = array();

    foreach($stops as $busStop) {
    $routesForThisStop = array();
       foreach ($allRoutes as $route) {
            $route_id = $route['id'];         
                if (in_array($busStop['id'], $route['stops'])) {
                    $routesForThisStop[] = $route['id'];
                }
        }
        $stopsToReturn[] = array('title'=>$busStop['name'], 
                                 'lon'=>$busStop['ll'][1], 'lat'=>$busStop['ll'][0],
                                 'id'=>$busStop['id'],'routes'=>$routesForThisStop);
    }

    return $stopsToReturn;
}

function get_all_routes_info($translocObj, $compact) {

    $routesInfoArray = $translocObj->getAllRoutesInfo();

    $routesToReturn = array();

    foreach($routesInfoArray as $routeInfo) {
      if ($compact == 'NO') {
           $routesToReturn[] =  array('route_id'=> $routeInfo['id'],
                                'color'=>$routeInfo['color'],
                                'title'=> $routeInfo['long_name'],
                                'description' => $translocObj->getBriefDescription($routeInfo['long_name']),
                                'agency' => $routeInfo['agency'],
                                'interval'=> 60,
                                'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']),
                                'summary'=> $translocObj->getSummary($routeInfo['long_name']),
                                'stops'=>$translocObj->getStopsForRoute($routeInfo['id']),
				'path'=>$translocObj->getPathForRoute($routeInfo['id']));
        }

        else {
        $routesToReturn[] = array('route_id'=> $routeInfo['id'],
                                'color'=>$routeInfo['color'],
                                'title'=> $routeInfo['long_name'],
                                'description' => $translocObj->getBriefDescription($routeInfo['long_name']),
                                'agency' => $routeInfo['agency'],
                                'interval'=> 60,
                                'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']),
                                'summary'=> $translocObj->getSummary($routeInfo['long_name']),);
        }

    }
    return $routesToReturn;
}


function get_specific_routes_info($translocObj, $compact, $route_id) {

    $routeInfo = $translocObj->getOneRouteInfo($route_id);

      if ($compact == 'NO') {
           $routeToReturn =  array('route_id'=> $routeInfo['id'],
                                'color'=>$routeInfo['color'],
                                'title'=> $routeInfo['long_name'],
                                'description' => $translocObj->getBriefDescription($routeInfo['long_name']),
                                'agency' => $routeInfo['agency'],
                                'interval'=> 60,
                                'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']),
                                'summary'=> $translocObj->getSummary($routeInfo['long_name']),
                                'stops'=>$translocObj->getStopsForRoute($routeInfo['id']),
				'path'=>$translocObj->getPathForRoute($routeInfo['id']));
        }

        else {
           /* $routeToReturn = array('stops'=>$translocObj->getStopsForRoute($routeInfo['id']),
                                   'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']));*/

          $routeToReturn =  array('route_id'=> $routeInfo['id'],
                                'color'=>$routeInfo['color'],
                                'title'=> $routeInfo['long_name'],
                                'description' => $translocObj->getBriefDescription($routeInfo['long_name']),
                                'agency' => $routeInfo['agency'],
                                'interval'=> 60,
                                'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']),
                                'summary'=> $translocObj->getSummary($routeInfo['long_name']),
                                'stops'=>$translocObj->getStopsForRoute($routeInfo['id']),
                                'isRunning'=> $translocObj->routeIsRunning($routeInfo['id']));
        }

    return $routeToReturn;
}

/*
function get_route_metadata($route_id) {
  $route = ShuttleSchedule::getRoute($route_id);
  $metadata = Array();
  $metadata['route_id'] = $route->id;
  $metadata['title'] = $route->long_name;
  $metadata['interval'] = $route->anyTrip(time())->duration() / 60;
  $metadata['isRunning'] = $route->isRunning(time());
  $metadata['summary'] = $route->desc;
  return $metadata;
}
*/

?>
