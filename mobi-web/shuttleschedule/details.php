<?php

$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$routeId = $_REQUEST['routeId'];
$stopId = $_REQUEST['stopId'];

$reader = new TranslocReader();

$allStops = $reader->getStops();
$allRoutes = $reader->getAllRoutesInfo();

foreach($reader->getStops() as $aStop) {
    if($aStop["id"] == $stopId) {
        $stop = $aStop;
        break;
    }
}

$runningRoutes = array();
$offlineRoutes = array();

foreach($reader->getAllRoutesInfo() as $aRoute) {
    if(in_array($stopId, $aRoute["stops"])) {
        if($reader->routeIsRunning($aRoute["id"])) {
           $runningRoutes[] = $aRoute;
        } else {
           $offlineRoutes[] = $aRoute;
        }
    }
}
usort($runningRoutes, "cmpRouteTitle");
usort($offlineRoutes, "cmpRouteTitle");

$runningTitle = "Currently serviced by:";
$offlineTitle = "Services at other times by:";


// determine size of stop map to display on each device
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


// generate the url and image tag for the stop map image
$mapWidth = $size;
$mapHeight = intval($size/2);
$imageURL = $reader->getImageURLForStop($routeId, $stop, $mapWidth, $mapHeight);

function routeURL($routeId) {
    return "times.php?route=" . $routeId;
}

function cmpRouteTitle($routeA, $routeB) {
    return strcmp($routeA['long_name'], $routeB['long_name']);
}

require "$page->branch/details.html";

$page->output();

?>
