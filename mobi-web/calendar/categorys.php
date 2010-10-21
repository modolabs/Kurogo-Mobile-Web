<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "mit_calendar.php";
require WEBROOT . "calendar/calendar_lib.php";

$categorys = MIT_Calendar::Categorys();

require "$page->branch/categorys.html";
$page->output();

?>
