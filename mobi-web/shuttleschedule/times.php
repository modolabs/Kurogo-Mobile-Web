<?php

$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$routeID = $_REQUEST['route'];

$reader = new TranslocReader();

if (!in_array($routeID, $reader->getRoutes())) {
  $routeName = $routeID;
  $routeError = "does not exist";

  $not_found_text = '<p>The route ' . $routeName . ' ' . $routeError . '.  Please update your bookmarks accordingly.  For more information see the <a href="help.php">help page</a>.</p>';

  $page->prepare_error_page('Shuttle Schedule', 'shuttle', $not_found_text);

} else {

  $now = time();

  $routeName = $reader->getNameForRoute($routeID);
  $foundVehicles = $reader->routeIsRunning($routeID);
  $vehicles = $reader->getVehiclesForRoute($routeID);
  $vehicleCount = count($vehicles);
  $stops = $reader->getStopsForRoute($routeID);

  $summary = $vehicleCount.' shuttle'.($vehicleCount != 1 ? 's':'').' found.';
  $loopTime = 'N';
  $lastUpdated = $reader->getVehiclesLastUpdateTime($routeID);
  
  //error_log(print_r($vehicles, true));
  
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
  $imageTag = '<img src="'.$reader->getImageURLForRoute($routeID, $size).
    '" width="'.$size.'" height="'.$size.'" id="mapimage" alt="Map" />';

  /*print_r($routeName);
  print " >< ";
  print_r($routeID);
  print " >< ";
  print_r($foundVehicles);
  print " >< ";
  print_r($vehicles);
  print " >< ";
  print_r($vehicleCount);
  print " >< ";
  print_r($summary);
  print " >< ";
  print_r($lastUpdated);*/
  
  require "$page->branch/times.html";
}

function selfURL() {
  return "times.php?route={$_REQUEST['route']}&now=" . time() . "&rand=" . rand();
}

// device-dependent time formatting function
function formatShuttleTime($page, $tstamp) {
  if ($page->branch == 'Basic') {
    if ($tstamp === 0) return 'finished';
    return date('g:i', $tstamp) . substr(date('a', $tstamp), 0, 1);
  } else {
    if ($tstamp === 0) return 'finished';
    return date('g:i', $tstamp) . '<span class="ampm">' . date('A', $tstamp) . '</span>';
  }
}

//$page->prevent_caching($pagetype);
$page->output();

?>
