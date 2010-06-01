<?

require_once "../../mobi-config/mobi_web_constants.php";
require_once WEBROOT . "api/api_header.php";

PageViews::log_api('map', 'iphone');

$json = '';

switch ($_REQUEST['command']) {
 case 'categories':
   
   require_once WEBROOT . 'mobi-config/mobi_web_constants.php';
   require_once WEBROOT . 'map/buildings_lib.php';
   $bldg_data = loadXML(WEBROOT . 'map/xml/bldg_data.xml');
   $building_nodes = $bldg_data->documentElement->getELementsByTagName('object');

   $data = Array(
     make_category('Selected Rooms', find_contents_ll('room', $building_nodes)),
     make_category('Food Services', find_contents_ll('food', $building_nodes)),
     make_category('Libraries', find_contents_ll('library', $building_nodes)),

     make_category('Residences', find_objects_ll('residence', $building_nodes)),
     make_category('Parking', find_objects_ll('parking', $building_nodes)),
     make_category('Streets and Landmarks', find_objects_ll('landmark', $building_nodes)),
     make_category('Courts and Green Spaces', find_objects_ll('green', $building_nodes)),
     make_category('Museums and Galleries', find_objects_ll('museum_gallery', $building_nodes)),
     );
   $json = json_encode($data);
   break;
 case 'search':
   if (isset($_REQUEST['q'])) {
     $query = http_build_query(Array('type' => 'query', 
				     'q' => $_REQUEST['q'], 
				     'output' => 'json'));
     $json = file_get_contents(MAP_SEARCH_URL . '?' . $query);
   }			     
   break;
}

echo $json;


function make_category($name, $items) {
  return Array('categoryName' => $name, 'categoryItems' => $items);
  /*
  foreach ($items as $item => $bldg) {
    $category['categoryItems'][] = Array(
      'name' => $item,
      'building' => $bldg,
      );
  }
  return $category;
  */
}


?>
