<?

define('MAP_SERVER_URL', 'http://maps.mit.edu/ArcGIS/rest/services/Mobile/WhereIs_MobileAll/MapServer');

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "api/api_header.php";

log_api('map');

switch ($_REQUEST['f']) {
 case 'json':
   $filename = '/var/local/maptiles/service.json';
   if (!file_exists($filename)) {
     $json = file_get_contents(MAP_SERVER_URL . '?f=json');
     $fh = open($filename, 'w');
     fwrite($fh, $json);
     fclose($fh);
   } else {
     $json = file_get_contents($filename);
   }

   if ($json) {
     echo $json;
   }
   break;
}

switch ($_REQUEST['command']) {
 case 'categories':
   require_once LIBDIR . "campus_map.php";
   $categories = Buildings::category_titles();
   $result = array();
   foreach ($categories as $category => $category_text) {
     $result[] = array(
       'categoryName' => $category_text,
       'categoryId' => $category,
       'categoryItems' => get_category($category),
       );
   }

   echo json_encode($result);
   break;
 case 'category':
   require_once LIBDIR . "campus_map.php";
   if ($category = $_REQUEST['id']) {
     $places = get_category($category);
     echo json_encode($places);
   }
   break;
 case 'categorytitles':
   require_once LIBDIR . "campus_map.php";
   $categories = category_titles_wrapper();
   echo json_encode($categories);
   break;
 case 'search':
   if (isset($_REQUEST['q'])) {
     $query = http_build_query(Array('type' => 'query', 
				     'q' => $_REQUEST['q'], 
				     'output' => 'json'));
     $json = file_get_contents(MAP_SEARCH_URL . '?' . $query);
   }

   echo $json;
   break;
 case 'tilesupdated':
   $date = file_get_contents(MAP_TILE_CACHE_DATE);
   $data = array("last_updated" => trim($date));
   echo json_encode($data);
   break;
}

function category_titles_wrapper() {
  $result = array(
    array('categoryName' => 'Building Number',
	  'categoryId' => 'building_number',
	  'subcategories' => generate_building_number_categories()),
    array('categoryName' => 'Building Name',
	  'categoryId' => 'building_name',
	  'subcategories' => generate_building_name_categories()),
    );

  $categories = Buildings::category_titles(); 
  foreach ($categories as $category => $category_text) {
    $result[] = array(
      'categoryName' => $category_text,
      'categoryId' => $category,
      );
  }
 
  return $result;
}

function generate_building_names($category) {
  $limits = explode('_', strtoupper($category));
  $result = array();
  foreach (Buildings::$bldg_data as $id => $data) {
    $firstchar = strtoupper(substr($data['name'], 0, 1));
    if ($firstchar >= $limits[0] && $firstchar <= $limits[1]) {
      $data['displayName'] = $data['name'];
      $result[$id] = $data;
    }
  }

  usort($result, 'building_name_cmp');

  return array_values($result);
}

function building_name_cmp($a, $b) {
  return strnatcasecmp($a['name'], $b['name']);
}

function generate_building_number_categories() {
  return array(
    array('categoryId' => 'm','categoryName' => 'Main Campus (1-76)'),
    array('categoryId' => 'e','categoryName' => 'East Campus (E1-E70)'),
    array('categoryId' => 'n','categoryName' => 'North Campus (N4-N57)'),
    array('categoryId' => 'ne','categoryName' => 'Northeast Campus (NE18-NE125)'),
    array('categoryId' => 'nw','categoryName' => 'Northwest Campus (NW10-NW95)'),
    array('categoryId' => 'w','categoryName' => 'West Campus (W1-WW15)'),
    );
}

function category_exceptions($id) {
  $exceptions = array('WW15' => 'W');
  if(isset($exceptions[$id])) {
    return $exceptions[$id];
  }
  return NULL;
}

function generate_building_numbers($category) {
  $area = ($category == 'm') ? '' : strtoupper($category);
  $result = array();
  foreach (Buildings::$bldg_data as $id => $data) {
    if ((preg_match('/([A-Z]*)\d+\w?/', $id, $matches) && $matches[1] == $area) ||
	(category_exceptions($id) === $area)) {
 
      $data['displayName'] = $data['bldgnum'];
      $result[$id] = $data;
    }
  }

  uksort($result, 'strnatcmp');

  return array_values($result);
}

function generate_building_name_categories() {
  return array(
    array('categoryId' => '1_999', 'categoryName' => '1-999'),
    array('categoryId' => 'a_c', 'categoryName' => 'A-C'),
    array('categoryId' => 'd_f', 'categoryName' => 'D-F'),
    array('categoryId' => 'g_l', 'categoryName' => 'G-L'),
    array('categoryId' => 'm_q', 'categoryName' => 'M-Q'),
    array('categoryId' => 'r_u', 'categoryName' => 'R-U'),
    array('categoryId' => 'v_z', 'categoryName' => 'V-Z'),
    );
}

function get_category($category) {
  if (preg_match('/^[enmw]{1,2}$/', $category)) {
    return generate_building_numbers($category);
  } elseif (preg_match('/^\w_\w+$/', $category)) {
    return generate_building_names($category);
  }

  $places = Buildings::category_items($category);
  $result = array();

  foreach ($places as $title => $building) {
    $bldg_info = Buildings::bldg_info($building);
    $bldg_info['displayName'] = $title;
    $result[] = $bldg_info;
  }
  return $result;
}



?>
