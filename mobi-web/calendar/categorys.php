<?php

require LIBDIR . "/harvard_calendar.php";
require "calendar_lib.inc";

$categories = Harvard_Calendar::get_categories(PATH_TO_EVENTS_CAT);

require "$page->branch/categorys.html";
$page->output();

?>
