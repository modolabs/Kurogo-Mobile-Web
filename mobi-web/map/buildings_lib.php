<?php

function loadXML($filename) {
  $xml_obj = new DOMDocument('1.0', 'iso-8859-1');
  $xml_obj->load($filename);
  return $xml_obj;
}

function makeArray($filename) {
  $xml_obj = loadXML($filename);
  $objects = $xml_obj->documentElement->getElementsByTagName('object');

  //all buildings by names
  $names = array();
  foreach($objects as $object) {
    $names[ getName($object) ] = getID($object);
  }
  ksort($names);
  return $names;
}
  
function getName($object) {
    return trim($object->getElementsByTagName('name')->item(0)->nodeValue);
}

function getID($object) {
    return substr($object->getAttribute('id'), 7);
}

function id_compare($id1, $id2) {
  preg_match('/^([A-Z]*)(\d+)([A-Z]*)/', $id1, $match1);
  preg_match('/^([A-Z]*)(\d+)([A-Z]*)/', $id2, $match2);

  if($match1[1] > $match2[1]) {
    return 1;
  }

  if($match2[1] > $match1[1]) {
    return -1;
  }

  if((int)$match1[2] > (int)$match2[2]) {
    return 1;
  }

  if((int)$match1[2] < (int)$match2[2]) {
    return -1;
  }

  if($match1[3] > $match2[3]) {
    return 1;
  }

  if($match2[3] > $match1[3]) {
    return -1;
  }

  return 0;
}

function is_type($building, $type) {
  foreach($building->getElementsByTagName('category') as $node) {
    if(trim($node->nodeValue) == $type) {
      return true;
    }
  }
  return false;
}

function find_objects($type, $building_nodes) {
  $found = array();

  foreach($building_nodes as $building) {
    if(is_type($building, $type)) {
      $found[ getName($building) ] = getID($building);
      foreach($building->getElementsByTagName('altname') as $node) {
	$found[ trim($node->nodeValue) ] = getID($building);
      }
    }
  }
  ksort($found);
  return $found;
}

function find_contents($type, $building_nodes) {
  $found = array();

  foreach($building_nodes as $building) {
    foreach($building->getElementsByTagName('contents') as $content) {
      if(is_type($content, $type)) {
	$found[ getName($content) ] = getID($building);
      }
    }
  }
  ksort($found);
  return $found;
}

function find_objects_ll($type, $building_nodes) {
  $found = array();

  foreach($building_nodes as $building) {
    if(is_type($building, $type)) {
      $bldg_id = getID($building);
      $lat = $building->getElementsByTagName('lat_wgs84')->item(0)->nodeValue;
      $lon = $building->getElementsByTagName('long_wgs84')->item(0)->nodeValue;

      $found[] = Array('name' => getName($building), 
        'building' => $bldg_id, 'lat' => $lat, 'lon' => $lon);

      foreach($building->getElementsByTagName('altname') as $node) {
	$found[] = Array('name' => trim($node->nodeValue),
	  'building' => $bldg_id, 'lat' => $lat, 'lon' => $lon );
      }
    }
  }
  return $found;
}

function find_contents_ll($type, $building_nodes) {
  $found = array();

  foreach($building_nodes as $building) {
    foreach($building->getElementsByTagName('contents') as $content) {
      if(is_type($content, $type)) {
	$lat = $building->getElementsByTagName('lat_wgs84');
	$lon = $building->getElementsByTagName('long_wgs84');
	$found[] = Array(
	  'name' => getName($content),
	  'building' => getID($building),
	  'lon' => $lon->item(0)->nodeValue,
	  'lat' => $lat->item(0)->nodeValue,
	  );
      }
    }
  }
  return $found;
}

function hasField($node, $field) {
  return ($node->getElementsByTagName($field)->length > 0);
}

function getField($node, $field) {
  return $node->getElementsByTagName($field)->item(0)->nodeValue;
}

?>
