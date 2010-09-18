<?

require_once "lib_constants.inc";

Buildings::init();

class Buildings {

  public static $bldg_data = NULL;

  public static function category_titles() {
    $result = array();
    foreach (self::$categories as $category => $catinfo) {
      $result[$category] = $catinfo['title'];
    }
    return $result;
  }

  public static function category_title($category) {
    return self::$categories[$category]['title'];
  }

  public static function category_items($category) {
    $finder = self::$categories[$category]['finder'];
    return call_user_func(array('self', $finder), $category);
  }

  public static $categories = array(
    'room' => array('title' => 'Selected Rooms', 'finder' => 'find_contents'),
    'food' => array('title' => 'Food Services', 'finder' => 'find_contents'),
    'library' => array('title' => 'Libraries', 'finder' => 'find_contents'),
    'residence' => array('title' => 'Residences', 'finder' => 'find_objects'),
    'parking' => array('title' => 'Parking Lots', 'finder' => 'find_objects'),
    'landmark' => array('title' => 'Streets and Landmarks', 'finder' => 'find_objects'),
    'green' => array('title' => 'Courts and Green Spaces', 'finder' => 'find_objects'),
    'clusters' => array('title' => 'Athena Clusters', 'finder' => 'find_contents'),
    'museum_gallery' => array('title' => 'Museums and Galleries', 'finder' => 'find_objects'),
    'hotel' => array('title' => 'Hotels', 'finder' => 'find_objects'),
    );

  public static function get_lat_lon($search) {
    if (preg_match('/^(\w*\d+\w*)/', $search, $matches)) {
      $id = $matches[1];
      $found = self::$bldg_data[$id];
    } else {
      foreach (self::$bldg_data as $id => $data) {
	if (array_key_exists('contents', $data)) {
	  foreach ($data['contents'] as $content) {
	    if ($search == $content['name']) {
	      $found = $data;
	      break;
	    }
	  }
	  break;
	}
      }
    }

    if ($found) {
      return array('lat' => $found['lat_wgs84'], 'lon' => $found['long_wgs84']);
    }

    return FALSE;
  }

  public static function bldg_info($bldg_id) {
    return self::$bldg_data[$bldg_id];
  }

  // this is a really dumb function that converts our bldg_data xml
  // a php associative arrays
  private static function children_to_array(DOMNode $node) {
    $result = array();
    if ($node->hasAttributes()) {
      foreach ($node->attributes as $name => $attribute) {
	$result[$name] = $attribute->value;
      }
    }

    if ($node->nodeType == XML_TEXT_NODE) {
      $parentName = $node->parentNode->nodeName;
      if ($parentName == 'lat_wgs84' || $parentName == 'long_wgs84') {
	return (double)(trim($node->nodeValue));
      }
      return trim($node->nodeValue);
    }

    $aChild = $node->firstChild;

    while ($aChild !== NULL) {
      $nodeName = $aChild->nodeName;

      if ($nodeName == 'category' || $nodeName == 'contents' || $nodeName == 'floor' || $nodeName == 'altname') {
	if (!array_key_exists($nodeName, $result)) {
	  $result[$nodeName] = array();
	}
	$result[$nodeName][] = self::children_to_array($aChild);
      } else {
	$result[$nodeName] = self::children_to_array($aChild);
      }
      $aChild = $aChild->nextSibling;
    }

    if (array_key_exists('#text', $result)) {
      if (count($result) == 1) {
	return $result['#text'];
      } else {
	return array_diff_key($result, array('#text' => true));
      }
    }

    return $result;

  }

  public static function init() {
    if (!self::$bldg_data) {
      $xml_obj = new DOMDocument('1.0', 'iso-8859-1');
      $xml_obj->load(BLDG_DATA_XML);

      foreach ($xml_obj->documentElement->getElementsByTagName('object')
	       as $bldgNode) {
	$bldg_id = self::getID($bldgNode);

	self::$bldg_data[$bldg_id] = self::children_to_array($bldgNode);
	if (!array_key_exists('city', self::$bldg_data[$bldg_id])) {
	  self::$bldg_data[$bldg_id]['city'] = "Cambridge, MA";
	}
      }
    }

    //var_dump(self::$bldg_data);
  }

  // xml parsing helpers
  private static function getID($object) {
    return substr($object->getAttribute('id'), 7);
  }

  private static function getName($object) {
    return trim($object->getElementsByTagName('name')->item(0)->nodeValue);
  }

  private static function is_type($bldg_arr, $type) {
    if (array_key_exists('contents', $bldg_arr)) {
      foreach ($bldg_arr['contents'] as $content) {
	$category = $bldg_arr['category'];
	if ($category == $type || in_array($type, $bldg_arr['category'])) {
	  return TRUE;
	}
      }
    }

    if (array_key_exists('category', $bldg_arr)) {
      $category = $bldg_arr['category'];
      return ($category == $type) || in_array($type, $bldg_arr['category']);
    }
    return false;
  }

  public static function find_objects($type) {
    $found = array();
    foreach (self::$bldg_data as $id => $bldg) {
      if (self::is_type($bldg, $type)) {
	$found[$bldg['name']] = $id;
	if (array_key_exists('altname', $bldg)) {
	  foreach ($bldg['altname'] as $altname) {
	    $found[$altname] = $id;
	  }
	}
      }
    }
    ksort($found);
    return $found;
  }

  public static function find_contents($type) {
    $found = array();
    foreach (self::$bldg_data as $id => $bldg) {
      if (array_key_exists('contents', $bldg)) {
	foreach ($bldg['contents'] as $content) {
	  if (self::is_type($content, $type)) {
	    $found[ $content['name'] ] = $id;
	  }
	}
      }
    }
    ksort($found);
    return $found;
  }

  private static function id_compare($id1, $id2) {
    preg_match('/^([A-Z]*)(\d+)([A-Z]*)/', $id1, $match1);
    preg_match('/^([A-Z]*)(\d+)([A-Z]*)/', $id2, $match2);

    if ($match1[1] != $match2[1]) {
      return strcmp($match1[1], $match2[1]);
    }

    $str1 = str_pad($match1[2], 5, '0', STR_PAD_LEFT) . $match1[3];
    $str2 = str_pad($match2[2], 5, '0', STR_PAD_LEFT) . $match2[3];

    return strcmp($str1, $str2);
  }

}

?>
