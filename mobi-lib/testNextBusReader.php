<?


require_once "mobi_lib_constants.php";
require_once(LIB_ROOT . "NextBusReader.php");

NextBusReader::init();

$routes = NextBusReader::get_route_list();
print_r($routes);

$route = reset($routes);
$route = 'saferidebostonw';
print_r(NextBusReader::get_route_info($route));
print_r(NextBusReader::get_last_refreshed($route));
//NextBusReader::get_route_info($route);
//print_r(NextBusReader::get_coordinates($route));
//print_r(NextBusReader::get_predictions($route));

?>
