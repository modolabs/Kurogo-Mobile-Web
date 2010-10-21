<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "ShuttleSchedule.php";
require_once LIBDIR . "NextBusReader.php";

NextBusReader::init();

$route = $_REQUEST['route'];
if (!in_array($route, ShuttleSchedule::get_active_routes())) {
  $routeName = ucwords(str_replace('_', ' ', $_REQUEST['route']));

  $not_found_text = '<p>The route ' . $routeName . ' is not currently in service.  Please update your bookmarks accordingly.  For more information see the <a href="help.php">help page</a>.</p>';

  $page->prepare_error_page('Shuttle Schedule', 'shuttle', $not_found_text);

} else {

  $now = time();
  $routeName = ShuttleSchedule::get_title($route);
  $interval = ShuttleSchedule::get_interval($route);
  $loop_time = $interval / 60;
  $summary = ShuttleSchedule::get_summary($route);
  if ($page->branch != 'Basic') {
    // format: 9:05AM -> 9:05<span class="ampm">AM</span>
    $summary = preg_replace('/(\d)(AM|PM)/','$1<span class="ampm">$2</span>', $summary);
  }

  // get scheduled stops first because we need to figure out
  // the stop names to query nextbus with
  $schedStops = ShuttleSchedule::get_next_scheduled_loop($route, $now);

  $stops = Array();

  // this array will contain the stops to be highlighted
  $upcoming_stops = Array();
  $previous_time = $time + $interval;

  // get nextbus data, if available
  $nbRoute = ShuttleSchedule::get_nextbus_id($route);

  // don't poll the GPS if the shuttle isn't running anyway
  $gps_active = (ShuttleSchedule::is_running($route)
		 && NextBusReader::gps_active($nbRoute));

  if ($gps_active) {
    $nbPredictions = NextBusReader::get_predictions($nbRoute, $now);
    $last_refreshed = NextBusReader::get_last_refreshed($nbRoute);
  }

  // this gives an array of seconds until next arrival
  // at each stop
  foreach ($schedStops as $index => $stop) {
    $stopData = Array();
    $stopData['title'] = $stop['title'];
    if ($gps_active) {
      $nbId = $stop['nextBusId'];
      // predictions comes in an array
      // we want the prediciton closest to current time
      $stopData['next'] = $now + min($nbPredictions[$nbId]);
    } else {
      $stopData['next'] = $stop['nextScheduled'];
    }

    if ($stopData['next'] < $previous_time 
	&& $stopData['next'] - $now < $interval)
      $upcoming_stops[] = $index;
    $stops[] = $stopData;
    $previous_time = $stopData['next'];
  }
  if ($stops[0]['next'] < $stops[count($stops) - 1]['next']
      && $stops[0]['next'] - $now < $interval)
    array_unshift($upcoming_stops, 0);

  // determine size of route map to display on each device
  switch ($page->branch) {
  case 'Webkit':
    $size = 270;
    break;
  case 'Touch':
    $size = 200;
    break;
  case 'Basic':
    $size = 200;
    break;
  }
  $image_tag = image_tag($size, $route, $upcoming_stops);  

  // device-dependent time formatting function
  if ($page->branch == 'Basic') {
    function format_shuttle_time($tstamp) {
      if ($tstamp == 'finished') return $tstamp;
      return date('g:i', $tstamp) . substr(date('a', $tstamp), 0, 1);
    }
  } else {
    function format_shuttle_time($tstamp) {
      if ($tstamp == 'finished') return $tstamp;
      return date('g:i', $tstamp) . '<span class="ampm">' . date('A', $tstamp) . '</span>';
    }
  }

  require "$page->branch/times.html";
}

$page->output();

function selfURL() {
  return "times.php?route={$_REQUEST['route']}";
}

function image_tag($size, $route, $upcoming_stops) {
  if ($route == 'boston_all' || $route == 'cambridge_all') {
    // these don't have maps
    // should save this info somewhere else instead of hard coding here
    return '';
  }  
  $base = $route;

  if(count($upcoming_stops) > 0) {
    $base .= '-';
    foreach($upcoming_stops as $nextStop) { 
      $base .= strtolower(num2letter($nextStop));
    }
  }

  return '<img src="images/' . $size . 
    '/' . $base . '.gif" width="' . $size . 
    '" height="' . $size . '" id="mapimage" alt="Map" />';
}

function num2letter($number) {
  return chr($number + ord('A'));
}

?>
