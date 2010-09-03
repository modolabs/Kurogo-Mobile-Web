<?php

$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/GTFSReader.php";

$route_id = $_REQUEST['route'];

if (!in_array($route_id, ShuttleSchedule::getActiveRoutes())) {
  if (!in_array($route_id, ShuttleSchedule::getRouteList())) {
    $routeName = $route_id;
    $routeError = "does not exist";
  } else {
    $routeName = ShuttleSchedule::getRoute($route_id)->long_name;
    $routeError = "is not currently in service";
  }

  $not_found_text = '<p>The route ' . $routeName . ' ' . $routeError . '.  Please update your bookmarks accordingly.  For more information see the <a href="help.php">help page</a>.</p>';

  $page->prepare_error_page('Shuttle Schedule', 'shuttle', $not_found_text);

} else {

  $now = time();

  $route = ShuttleSchedule::getRoute($route_id);
  $stops = ShuttleSchedule::list_stop_times($route_id);
  $lastIndex = count($stops) - 1;
  if ($stops[$lastIndex]['gps']) {
    $gps_active = TRUE;
  } else {
    $gps_active = FALSE;
  }
  unset($stops[$lastIndex]);

  if ($page->branch != 'Basic') {
    // format: 9:05AM -> 9:05<span class="ampm">AM</span>
    $summary = preg_replace('/(\d)(AM|PM)/','$1<span class="ampm">$2</span>', $summary);
  }

  $upcoming_stops = Array();
  $highlighted_stops = Array();
  foreach ($stops as $index => $stop) {
    if ($stop['upcoming']) {
      $upcoming_stops[] = $index;
      $highlighted_stops[] = $stop['id'];
    }
  }
  $trip = $route->anyTrip($now);

  // fields to display in html template
  $routeName = $route->long_name;
  $interval = $trip->duration();
  $loop_time = $interval / 60;
  $summary = $route->desc;
  $last_refreshed = $now;

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

  // produce url to google static maps image
  $image_tag = ShuttleSchedule::image_tag($size, $trip, $highlighted_stops);  

  // device-dependent time formatting function
  if ($page->branch == 'Basic') {
    function format_shuttle_time($tstamp) {
      if ($tstamp === 0) return 'finished';
      return date('g:i', $tstamp) . substr(date('a', $tstamp), 0, 1);
    }
  } else {
    function format_shuttle_time($tstamp) {
      if ($tstamp === 0) return 'finished';
      return date('g:i', $tstamp) . '<span class="ampm">' . date('A', $tstamp) . '</span>';
    }
  }

  require "$page->branch/times.html";
}

$page->prevent_caching($pagetype);
$page->output();

function selfURL() {
  return "times.php?route={$_REQUEST['route']}&now=" . time() . "&rand=" . rand();
}

function num2letter($number) {
  return chr($number + ord('A'));
}

?>
