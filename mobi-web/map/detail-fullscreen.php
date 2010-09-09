<?php

require_once LIBDIR . "/WMSServer.php";

$selectvalue = $_REQUEST['selectvalues'];
$bbox = explode(',', $_REQUEST['bbox']);
$minx = $bbox[0];
$miny = $bbox[1];
$maxx = $bbox[2];
$maxy = $bbox[3];

$bbox = explode(',', $_REQUEST['bboxSelect']);
$minxSelect = $bbox[0];
$minySelect = $bbox[1];
$maxxSelect = $bbox[2];
$maxySelect = $bbox[3];

$field = isset($_REQUEST['selectfield']) ? $_REQUEST['selectfield'] : null;
$layer = isset($_REQUEST['selectlayer']) ? $_REQUEST['selectlayer'] : null;
$layers = isset($_REQUEST['layers']) ? $_REQUEST['layers'] : null;

$onorientationchange = "scrollTo(0,1); rotateScreen(); setTimeout('rotateMap()',500)";
$extra_onload = $onorientationchange;

$wms = new WMSServer(WMS_SERVER);

$mapInitURL = $wms->getMapBaseURL(); // js variable
$urlParts = parse_url($mapInitURL);
parse_str($urlParts['query'], $queryParts);
$mapLayers = $queryParts['layers'];

$wms->disableAllLayers();
//$mapBaseURL = $wms->getMapBaseUrl();
//$wms->enableAllLayers();

// extract url components and remove the 'layers' param
$mapInitURL = $wms->getMapBaseUrl();
$urlParts = parse_url($mapInitURL);
parse_str($urlParts['query'], $queryParts);
$baseLayers = $queryParts['layers'];
$layers = explode(',', $mapLayers);
$titles = $wms->getLayerTitles(); // to be encoded into a js var

unset($queryParts['layers']);
unset($queryParts['styles']);
$urlParts['query'] = http_build_query($queryParts);

$mapBaseURL = $urlParts['scheme'] . '://'
            . $urlParts['host']
            . $urlParts['path'] . '?'
            . $urlParts['query']; // js variable

$detailUrlOptions = http_build_query(array(
  'info' => $_REQUEST['info'],
  'selectvalues' => $_REQUEST['selectvalues'],
  ));

$mapOptions = '&' . http_build_query(array(
  'crs' => 'EPSG:2249',
  ));

require "$page->branch/detail-fullscreen.html";

?>
