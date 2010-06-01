<?php


require_once "../config/mobi_web_constants.php";
require_once "buildings_lib.php";

$xmlfile = WEBROOT . 'map/xml/bldg_data.xml';
$bldg_data = loadXML($xmlfile);
$campus_map = $bldg_data->documentElement;
$building_nodes = $bldg_data->documentElement->getElementsByTagName('object');

//all buildings with MIT building numbers
$buildings = array();
foreach($building_nodes as $building) {
  $number_node = $building->getElementsByTagName('bldgnum');
  if($number_node->length) {
    $buildings[] = trim($number_node->item(0)->nodeValue);
  }
}
usort($buildings, 'id_compare');

$names = makeArray($xmlfile);

$rooms = find_contents('room', $building_nodes);
$food = find_contents('food', $building_nodes);
$library = find_contents('library', $building_nodes);
$residences = find_objects('residence', $building_nodes);
$parking = find_objects('parking', $building_nodes);
$landmarks = find_objects('landmark', $building_nodes);
$courts_green = find_objects('green', $building_nodes);
$museum_gallery = find_objects('museum_gallery', $building_nodes);
$hotel = find_objects('hotel', $building_nodes);

$building_data = array();
foreach ($building_nodes as $building) {
  if (!$building_data[ getID($building) ]) {
    $building_data[ getID($building) ] = Array();
    $building_data[ getID($building) ]['whats_here'] = Array();
  }

  foreach(array('name', 'street', 'viewangle', 'architect', 'bldgnum') as $field) {
    if(hasField($building, $field)) {
      $building_data[ getID($building) ][$field] = getField($building, $field);
    }
  }

  //fill in the city field or set it to default of Cambridge, MA
  if(hasField($building, 'city')) {
    $building_data[ getID($building) ]['city'] = getField($building, 'city');
  } else {
    $building_data[ getID($building) ]['city'] = "Cambridge, MA";
  }

  //fill in the beginning of the what's here entry
  foreach($building->getElementsByTagName('contents') as $content) {
    $building_data[ getID($building) ]['whats_here'][ getField($content, 'name') ]= True;
  }
}

?>
