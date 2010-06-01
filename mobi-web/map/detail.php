<?
define("WMS_URL", "http://ims.mit.edu/WMS_MS/WMS.asp");


require_once "../mobi-config/mobi_web_constants.php";
require_once PAGE_HEADER;

//set zoom scale
define('ZOOM_FACTOR', 2);

switch ($page->branch) {
 case 'Touch':
   define('INIT_FACTOR', 3);
   break;
 case 'Basic':
   define('INIT_FACTOR', 2);
   break;
}

//set the offset parameter
define('MOVE_FACTOR', 0.40);

function determine_type() {
  $types = array(
    "G"  => "Courtyards",
    "P"  => "Parking",
    "L"  => "Landmarks"
  );

  if(preg_match("/^(P|G|L)\d+/", select_value(), $match)) {
    return array("type" => $types[ $match[1] ], "field" => "Loc_ID");
  } else {
    return array("type" => "Buildings", "field" => "facility");
  }
}

function layers($fontsize=NULL) {
  $layers = array(
    'Towns', 'Hydro', 'Greenspace', 'Sport', 'Roads', 
    'Rail', 'Parking' ,'Other Buildings', 'Landmarks', 
    'Buildings', 'Courtyards'
  ); 

  $fsizes = Array(
    'bldg' => 12,
    'road' => 10,
    'greens' => 10,
    'landmarks' => 10
    );

  $type = determine_type();
  $layer = $type['type'];  
  $layer_index = array_search($layer, $layers);

  $iden_layers = Array();
  foreach ($fsizes as $iden => $fsize) {
    if ($fontsize === NULL) {
      $iden_layers[] = $iden . '-iden-' . $fsize;
    } else {
      $iden_layers[] = $iden . '-iden-' . $fontsize;
    }
  }

  $new_layers = array_merge( 
    array_slice($layers, 0, $layer_index), 
    array_slice($layers, $layer_index + 1),
    array($layer),
    $iden_layers
  );

  return implode(",", $new_layers); 
}

function isID($id) {
  preg_match("/^([A-Z]*)/", $id, $match);
  return $match[0];
}

/*
function mapURL() {
  return "http://ims.mit.edu/WMS_MS/WMS.asp";
}
*/

function getServerBBox() {
  return CacheIMS::$server_BBox;
}

class CacheIMS {
  public static $server_BBox;

  public function init() {

    $type = determine_type();

    $query1 = array(
      "request" => "getselection",
      "type"    => "query",
      "layer"   => $type["type"],
      "idfield" => $type["field"],
      "query"   => $type["field"] ." in ('" . select_value() . "')"
    );

    $error_reporting = intval(ini_get('error_reporting'));
    error_reporting($error_reporting & ~E_WARNING);
      $xml = file_get_contents(WMS_URL . '?' . http_build_query($query1));
    error_reporting($error_reporting);
    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException(WMS_URL . '?' . http_build_query($query1) . " is experiencing problems");
    }



    $xml_obj = new DOMDocument();
    $xml_obj->loadXML($xml);
    $extent = $xml_obj->firstChild->firstChild;

    if(!$extent) {
      //IMS server does not seem to be able to find anything
      return;
    }

    $bbox = array();
    foreach(array('minx','miny','maxx','maxy') as $key) {
      $bbox[$key] = (int) $extent->getAttribute($key);
    }

    self::$server_BBox = $bbox;
  }
}
CacheIMS::init();

function iPhoneBBox() {
  if(isset($_REQUEST['bbox'])) {
    $values = explode(",", $_REQUEST['bbox']);
    return array(
      "minx" => $values[0],
      "miny" => $values[1],
      "maxx" => $values[2],
      "maxy" => $values[3]
    );
  } else {
    return iPhoneSelectBBox();
  }
}
 
function iPhoneSelectBBox() {
  return zoom_box(getServerBBox(), 2.6);
}

function zoom_box(array $box, $zoom) {
  $width = $zoom * ($box["maxx"] - $box["minx"]);
  $height = $zoom * ($box["maxy"] - $box["miny"]);
  $x_center = ($box["maxx"] + $box["minx"])/2;
  $y_center = ($box["maxy"] + $box["miny"])/2;
  return array(
    "minx" => (int) ($x_center - $width/2),
    "miny" => (int) ($y_center - $height/2),
    "maxx" => (int) ($x_center + $width/2),
    "maxy" => (int) ($y_center + $height/2)
  );
}

function photoURL() {
  $url = "http://web.mit.edu/campus-map/objimgs/object-" . select_value() . ".jpg";

  //need to turn off warnings temporialy  (want to suppress url not found warning)
  $error_reporting = ini_get('error_reporting');
  error_reporting($error_reporting & ~E_WARNING);
     $result = file_get_contents($url, FILE_BINARY, NULL, 0, 100);
  error_reporting($error_reporting);

  if($result) {
    return $url;
  } else {
    return "";
  }
}
   
function bbox($x_pix, $y_pix) {
  $bbox = getServerBBox();
  
  //calculate center, width and height
  $x_center = ($bbox["maxx"]+$bbox["minx"])/2;
  $y_center = ($bbox["maxy"]+$bbox["miny"])/2;
  $width    = ($bbox["maxx"]-$bbox["minx"]);
  $height   = ($bbox["maxy"]-$bbox["miny"]);

  //need to determine if we need to add 
  //vertically or horizontally to the bounding box
  $image_ratio = $y_pix/$x_pix;
  $bbox_ratio  = $height/$width;
  if($bbox_ratio >= $image_ratio) {
    $width = $height/$image_ratio;
  } else {
    $height = $width*$image_ratio;
  } 

  //calculate width, height and the bounding box to use
  $width = $width * INIT_FACTOR * pow(ZOOM_FACTOR, zoom());
  $height = $height * INIT_FACTOR * pow(ZOOM_FACTOR, zoom());
  
  //move center by offsets
  $x_center += x_off() * MOVE_FACTOR * $width;
  $y_center += y_off() * MOVE_FACTOR * $height;

  return array(
    "minx" => (int) ($x_center - $width/2),
    "maxx" => (int) ($x_center + $width/2),
    "miny" => (int) ($y_center - $height/2),
    "maxy" => (int) ($y_center + $height/2)
  );
}

function imageURL($x_pix, $y_pix, $fontsize=10) {
  $bbox = bbox($x_pix, $y_pix);
  $type = determine_type();

  $query2 = array(
    "request"      => "getmap",
    "version"      => "1.1.1", 
    "width"        => $x_pix,
    "height"       => $y_pix,
    "selectvalues" => select_value(),
    "bbox"         => $bbox["minx"].','.$bbox["miny"].','.$bbox["maxx"].','.$bbox["maxy"],
    "layers"       => layers($fontsize),
    "selectfield"  => $type['field'],
    "selectlayer"  => $type['type']
  );

  return WMS_URL . '?' . http_build_query($query2);
}

function zoom() {
  return isset($_REQUEST['zoom']) ? $_REQUEST['zoom'] : 0;
}


function x_off() {
  return isset($_REQUEST['xoff']) ? $_REQUEST['xoff'] : 0;
}

function y_off() {
  return isset($_REQUEST['yoff']) ? $_REQUEST['yoff'] : 0;
}

function tab() {
  return isset($_REQUEST['tab']) ? $_REQUEST['tab'] : "Map";
}

function select_value() {
  return $_REQUEST['selectvalues'];
}

function snippets() {
  $data = Data::$values;
  $snippets = $_REQUEST['snippets'];

  // we do not want to display snippets
  // if snippets just repeats the building number
  // or building name
  if($snippets == trim($data['bldgnum'])) {
    return NULL;
  } 

  if($snippets == trim($data['name'])) {
    return NULL;
  } 

  return $snippets;
}

function scrollURL($dir) {
  $dir_arr = array(
    "E" => array(1,0),
    "W" => array(-1,0),
    "N" => array(0,1),
    "S" => array(0,-1)
  );
  $dir_vector = $dir_arr[$dir];
  return moveURL(x_off()+$dir_vector[0], y_off()+$dir_vector[1], zoom());
}

function zoomInURL() {
  return moveURL(x_off()*ZOOM_FACTOR, y_off()*ZOOM_FACTOR, zoom()-1);
}

function zoomOutURL() {
  return moveURL(x_off()/ZOOM_FACTOR, y_off()/ZOOM_FACTOR, zoom()+1);
}

Data::init();
$data = Data::$values;
function anything_here() {
  $data = Data::$values;
  return (count($data['whats_here']) > 0);
}


$tabs = new Tabs(selfURL(), "tab", array("Map", "Photo", "What's Here"));

if(!photoURL()) {
    $tabs->hide("Photo");
}

if(!anything_here()) {
    $tabs->hide("What's Here");
}

$tabs_html = $tabs->html($page->branch);
$tab = $tabs->active(); 

function selfURL() {
  return moveURL(x_off(), y_off(), zoom());
}

function moveURL($xoff, $yoff, $zoom) {
  $params = array(
    "selectvalues" => select_value(),
    "zoom" => $zoom,
    "xoff" => $xoff,
    "yoff" => $yoff,
    "snippets" => snippets()
  );
  return "detail.php?" . http_build_query($params);
}

$selectvalue = select_value();
$photoURL = photoURL();
$tab = tab();
/*
$width = $page->centered_image_width;
$height = $page->centered_image_height;
$fontsize = $page->centered_image_font_size;
*/
switch ($page->branch) {
 case 'Touch':
   $width = 200;
   $height = 200;
   break;
 case 'Basic':
   $width = 160;
   $height = 160;
   break;
}
$fontsize = 10;
$snippets = snippets();
$types = determine_type();
$layers = layers($fontsize);

class Data {
  public static $values;

  public static function init() {
    require "buildings.php";
    self::$values = $building_data[select_value()];
  }
}

if(isset($data['whats_here'])) {
  $whats_here = array_keys($data['whats_here']);
} else {
  $whats_here = array();
}

$anything_here = anything_here();
if($anything_here) {
  sort($whats_here);
}

if($num = trim($data['bldgnum'])) {
  $building_title = "Building $num";
  if( ($name = trim($data['name'])) && ($name !== $building_title) ) {
    $building_title .= " ($name)";
  }
} else {
  $building_title = $data['name'];
}

/**
 * this function makes the street address
 * more readable by google maps
 */
function cleanStreet($data) {    
  // remove things such as '(rear)' at the end of an address
  $street = preg_replace('/\(.*?\)$/', '', $data['street']);

  //remove 'Access Via' that appears at the begginning of some addresses
  return preg_replace('/^access\s+via\s+/i', '', $street);
} 

if(getServerBBox()) {
  require "$page->branch/detail.html";
} else {
  require "$page->branch/not_found.html";
}



$page->output();

?>
