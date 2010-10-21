<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once 'GTFSReader.php';

//print_r("all routes:\n");
//print_r(ShuttleSchedule::getRouteList());

foreach (array(
  //'mit',
  'saferide',
  ) as $agency)
{
  $routes = array();
  foreach (ShuttleSchedule::getActiveRoutes($agency) as $route) {
    $routes[$route] = FALSE;
  }

  $activeroutes = ShuttleSchedule::getRunningRoutes($agency);
  foreach ($activeroutes as $route) {
    $routes[$route] = 'running';
  }
  print_r("$agency routes:\n");
  print_r($routes);

  foreach ($routes as $route_id => $status) {

    echo "\n";
    $route = ShuttleSchedule::getRoute($route_id);
    echo $route->long_name . ' (' . $route_id . "):\n";
    /*
    foreach (ShuttleSchedule::getNextLoop($route_id) as $stop_id => $times) {
      $rawtime = $times[0];
      $time = date('m/d G:i:s', $rawtime);
      echo "$stop_id $rawtime ($time)\n";
    }
    echo "\n";
    */
    $stops = ShuttleSchedule::list_stop_times($route_id);
    $lastIndex = count($stops) - 1;
    if ($stops[$lastIndex]['gps']) {
      $gps_active = TRUE;
    } else {
      $gps_active = FALSE;
    }
    unset($stops[$lastIndex]);

    echo 'NextBus GPS is ' 
      . ($gps_active ? '' : 'not ') . "avaliable on route $route_id\n";
    foreach ($stops as $stopData) {
      $rawtime = $stopData['next'];
      $time = date('m/d G:i:s', $rawtime);
      echo $stopData['id'] . ': ' . "$rawtime ($time)\n";
    }

    echo "\n";
  }
}

$stopTimes = ShuttleSchedule::getTimesForStop('kendsq_d');
foreach ($stopTimes as $route_id => $times) {
  var_dump($times);
  echo "$route_id: ";
  foreach ($times as $time) {
    var_dump($time);
  }
  echo "\n";
}

//$mit = ShuttleSchedule::getAgency('mit');
//$saferide = ShuttleSchedule::getAgency('saferide');

//$mit = NextBusReader::agency('mit');
//$saferide = NextBusReader::agency('saferide');
//$mbta = NextBusReader::agency('mbta');

//$mit->routeList();
//print_r($saferide->routeList());

//print_r($mit->getAllStops());
//print_r($mit->routeConfig('tech'));
//print_r($mit->predictionsForRoute('tech'));
//print_r($mit->vehicleLocations('tech'));
//print_r($mit->predictionsForStop('mass84_d'));

$route = ShuttleSchedule::getRoute('tech');

$trip = $route->anyTrip(time());
$tag = ShuttleSchedule::image_tag(200, $trip, array('kendsq_d'));
var_dump($tag);

?>
