<?php



require_once "../config/mobi_web_constants.php";
require_once PAGE_HEADER;
require_once("shuttle_lib.php");

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

  $gps_active = (ShuttleSchedule::is_running($route)
		 && NextBusReader::gps_active($route));

  $stops = list_stop_times($route, $now, $gps_active);

  $upcoming_stops = Array();
  foreach ($stops as $index => $stop) {
    if ($stop['upcoming']) {
      $upcoming_stops[] = $index;
    }
  }

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
  $last_refreshed = $now;

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
