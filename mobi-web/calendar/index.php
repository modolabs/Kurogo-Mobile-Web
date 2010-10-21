<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";

//defines all the variables related to being today
require WEBROOT . "calendar/calendar_lib.php";

$today = day_info(time());

$search_options = SearchOptions::get_options();

require "$page->branch/index.html";
$page->output();

?>
