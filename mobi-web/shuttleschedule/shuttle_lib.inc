<?php
require_once LIBDIR . "/ShuttleSchedule.php";
require_once LIBDIR . "/NextBusReader.php";

NextBusReader::init();

function list_stop_times($route, $time, $gps_active, $singleStopId=NULL) {

  $stops = Array();

  $schedStops = ShuttleSchedule::get_next_scheduled_loop($route, $time);
  $nbStopsInfo = NextBusReader::get_route_info($route);
  $interval = ShuttleSchedule::get_interval($route);
  $end_time = ShuttleSchedule::get_last_run($route, $time) + $interval;

  // this array will contain the stops to be highlighted
  $upcoming_stops = Array();
  $previous_time = 0;

  if ($gps_active) {
    $nbPredictions = NextBusReader::get_predictions($route, $time);
  }

  if (!$nbPredictions) {
    // this condition might occur because we have a delay in 
    // deciding the gps is offline after the bus has actually
    // gone out of service
    $gps_active = FALSE;
  }

  // this gives an array of seconds until next arrival
  // at each stop
  foreach ($schedStops as $index => $stop) {
    $stopData = Array();
    $stopId = $stop['nextBusId'];
    $stopData['id'] = $stopId;
    $stopData['title'] = $stop['title'];
    if (isset($nbStopsInfo[$stopId])) {
      $stopData['lat'] = $nbStopsInfo[$stopId]['lat'];
      $stopData['lon'] = $nbStopsInfo[$stopId]['lon'];
    } else {
      $stopData['lat'] = 0; // Atlantic Ocean - won't crash apps but noticeable
      $stopData['lon'] = 0;
      error_log("No coordinates available for stop '$stopId' on route '$route'.");
    }
    $stopData['next'] = $stop['nextScheduled'];
    if ($gps_active && $stopData['next'] !== 0) {
      $stopData['predictions'] = Array();
      if (!$nbPredictions[$stopId]) {
	error_log("no predictions for $stopId on route $route. predictions:" . serialize($nbPredictions));
	continue;
      }
      sort($nbPredictions[$stopId]);
      $firstPrediction = array_shift($nbPredictions[$stopId]);
      $stopData['next'] = $time + $firstPrediction;
      foreach ($nbPredictions[$stopId] as $prediction) {
	if ($time + (int) $prediction > $end_time) {
	  break;
	}
	$stopData['predictions'][] = $prediction - $firstPrediction;
      }
    }

    // short circuit if a single stop is requested
    if ($stopId == $singleStopId) {
      return $stopData;
    }

    if ($stopData['next'] < $previous_time && $stopData['next'] - $time < $interval) {
      $stopData['upcoming'] = TRUE;
    }

    $stops[] = $stopData;
    $previous_time = $stopData['next'];
  }
  if ($stops[0]['next'] < $stops[count($stops) - 1]['next'] && $stops[0]['next'] - $time < $interval) {
    $stops[0]['upcoming'] = TRUE;
  }
  return $stops;
}

?>
