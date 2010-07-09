<?php

require LIBDIR . "/mit_calendar.php";
require "calendar_lib.inc";

$categorys = MIT_Calendar::Categorys();

require "$page->branch/categorys.html";
$page->output();

?>
