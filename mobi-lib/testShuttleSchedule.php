<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once(LIBDIR . 'ShuttleSchedule.php');

//print_r(ShuttleSchedule::get_route_list());
$routes = ShuttleSchedule::get_active_routes();
//$routes = Array('boston_all');
//print_r($routes);
//$routes = Array('boston_east');
foreach ($routes as $route) {
  echo ShuttleSchedule::get_title($route) . '(' . $route . ")\n";
  //print_r(ShuttleSchedule::get_next_scheduled_loop($route));
  //echo 'is running: ' . ShuttleSchedule::is_running($route) . "\n";
}

?>