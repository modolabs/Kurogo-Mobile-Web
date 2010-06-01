<?


require_once "../mobi-config/mobi_web_constants.php";
require_once PAGE_HEADER;

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

if ($page->delta == 'iphone') {
  $extra_onload = 'rotateScreen(); rotateMap(); scrollTo(0,1)';
  $extra_body_tag = 'id="body" onorientationchange="rotateScreen(); rotateMap();"';
} else {
  $extra_onload = "loadImage(getMapURL(mapBaseURL),'mapimage')";
  $extra_body_tag = 'class="portrait"';
}

require "$page->branch/detail-fullscreen.html";

?>
