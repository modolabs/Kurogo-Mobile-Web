<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once LIBDIR . "ShuttleSchedule.php";
$schedule = new ShuttleSchedule();
require LIBDIR . "shuttle_schedule.php";

foreach($schedule->getRoutes() as $route) {
  $route->populate_db();
}

?>
