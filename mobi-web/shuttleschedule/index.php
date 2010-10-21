<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "ShuttleSchedule.php";
require_once LIBDIR . "NextBusReader.php";

NextBusReader::init();

$now = time();

$routes = ShuttleSchedule::get_active_routes();
$day_routes = Array();
$night_routes = Array();
foreach ($routes as $route) {
  if (ShuttleSchedule::is_safe_ride($route))
    $night_routes[] = $route;
  else
    $day_routes[] = $route;
}

require "$page->branch/index.html";
$page->output();
?>
