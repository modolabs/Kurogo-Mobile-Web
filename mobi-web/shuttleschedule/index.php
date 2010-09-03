<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "GTFSReader.php";

$now = time();

$day_routes = ShuttleSchedule::getActiveRoutes('mit');
$day_routes = array_merge($day_routes, ShuttleSchedule::getActiveRoutes('tma'));
$night_routes = ShuttleSchedule::getActiveRoutes('saferide');

require "$page->branch/index.html";
$page->output();
?>
