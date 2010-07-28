<?php

define('ZOOM_FACTOR', 2);
define('MOVE_FACTOR', 0.40);

//session_id($_REQUEST['sess']);
//session_start();
//$places = $_SESSION['places'];
$name = $_REQUEST['selectvalues'];

//$details = $places[$name];
$details = $_REQUEST['info'];
if (!isset($_REQUEST['tab']))
  $_REQUEST['tab'] = 'Map';

$tab = $_REQUEST['tab'];

if ($tab == 'Map') {
  require_once LIBDIR . '/WMSServer.php';
  $wms = new WMSServer(WMS_SERVER);
  $bbox = isset($_REQUEST['bbox']) ? $_REQUEST['bbox'] : NULL;

  switch ($page->branch) {
   case 'Webkit':
     $imageWidth = 270; $imageHeight = 270;
     break;
   case 'Touch':
     $imageWidth = 200; $imageHeight = 200;
     break;
   case 'Basic':
     $imageWidth = 200; $imageHeight = 200;
     break;
  }

  if (!$bbox) {
    require_once LIBDIR . '/ArcGISServer.php';

    $searchResults = ArcGISServer::search($name);
    if ($searchResults && $searchResults->results) {
      $result = $searchResults->results[0];
      foreach ($result as $field => $value) {
        if (is_string($value)) {
          $details[$field] = $value;
        }
      }
      $rings = $result->geometry->rings;
      $xmin = PHP_INT_MAX;
      $xmax = 0;
      $ymin = PHP_INT_MAX;
      $ymax = 0;
      foreach ($rings[0] as $point) {
        if ($xmin > $point[0]) $xmin = $point[0];
        if ($xmax < $point[0]) $xmax = $point[0];
        if ($ymin > $point[1]) $ymin = $point[1];
        if ($ymax < $point[1]) $ymax = $point[1];
      }
    
      $minBBox = array(
        'xmin' => $xmin,
        'xmax' => $xmax,
        'ymin' => $ymin,
        'ymax' => $ymax,
        );
  
      $bbox = $wms->calculateBBox($imageWidth, $imageHeight, $minBBox);
    }
  }

  if ($bbox) {
    $imageUrl = $wms->getMap($imageWidth, $imageHeight, 'EPSG:2249', $bbox);

    // build urls for panning/zooming
    $params = $_GET;

    $params['bbox'] = shiftBBox($bbox, 0, -1, 0);
    $scrollNorth = 'detail.php?' . http_build_query($params);
    $params['bbox'] = shiftBBox($bbox, 0, 1, 0);
    $scrollSouth = 'detail.php?' . http_build_query($params);
    $params['bbox'] = shiftBBox($bbox, 1, 0, 0);
    $scrollEast = 'detail.php?' . http_build_query($params);
    $params['bbox'] = shiftBBox($bbox, -1, 0, 0);
    $scrollWest = 'detail.php?' . http_build_query($params);
    $params['bbox'] = shiftBBox($bbox, 0, 0, 1);
    $zoomInUrl = 'detail.php?' . http_build_query($params);
    $params['bbox'] = shiftBBox($bbox, 0, 0, -1);
    $zoomOutUrl = 'detail.php?' . http_build_query($params);
  }

}

$selectvalue = $_REQUEST['selectvalues'];

$tabs = new Tabs(selfURL(), "tab", array("Map", "Details"));

if(!$details) {
  $tabs->hide("Details");
}

$tabs_html = $tabs->html($page->branch);

require "$page->branch/detail.html";

$page->output();

function selfURL() {
  $params = $_GET;
  unset($params['tab']);
  return 'detail.php?' . http_build_query($params);
}

// all args can be -1, 0, or 1
function shiftBBox($bbox, $east, $south, $in) {
  $xrange = $bbox['xmax'] - $bbox['xmin'];
  $yrange = $bbox['ymax'] - $bbox['ymin'];
  if ($east != 0) {
    $bbox['xmin'] += $east * $xrange * MOVE_FACTOR;
    $bbox['xmax'] += $east * $xrange * MOVE_FACTOR;
  }
  if ($south != 0) {
    $bbox['ymin'] += $south * $yrange * MOVE_FACTOR;
    $bbox['ymax'] += $south * $yrange * MOVE_FACTOR;
  }
  if ($in != 0) {
    if ($in == 1)
      $inset = (ZOOM_FACTOR - 1) / ZOOM_FACTOR;
    else
      $inset = -2 / ZOOM_FACTOR;

    $bbox['xmin'] += ($xrange / 2) * $inset;
    $bbox['xmax'] -= ($xrange / 2) * $inset;
    $bbox['ymin'] += ($yrange / 2) * $inset;
    $bbox['ymax'] -= ($yrange / 2) * $inset;
  }

  return $bbox;
}

?>