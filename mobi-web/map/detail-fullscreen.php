<?

$selectvalue = $_REQUEST['selectvalues'];
$bbox = split(',', $_REQUEST['bbox']);
$minx = $bbox[0];
$miny = $bbox[1];
$maxx = $bbox[2];
$maxy = $bbox[3];

$bbox = split(',', $_REQUEST['bboxSelect']);
$minxSelect = $bbox[0];
$minySelect = $bbox[1];
$maxxSelect = $bbox[2];
$maxySelect = $bbox[3];

$field = $_REQUEST['selectfield'];
$layer = $_REQUEST['selectlayer'];
$layers = $_REQUEST['layers'];

$onorientationchange = "scrollTo(0,1); rotateScreen(); setTimeout('rotateMap()',500)";
$extra_onload = $onorientationchange;

require "$page->branch/detail-fullscreen.html";

?>
