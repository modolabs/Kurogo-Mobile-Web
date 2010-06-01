<?php


require_once "../config/mobi_web_constants.php";
require PAGE_HEADER;
require LIBDIR . "mit_calendar.php";
require "calendar_lib.php";

$categorys = MIT_Calendar::Categorys();

require "$page->branch/categorys.html";
$page->output();

?>
