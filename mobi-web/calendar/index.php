<?php


require_once "../mobi-config/mobi_web_constants.php";
require PAGE_HEADER;

//defines all the variables related to being today
require "calendar_lib.php";

$today = day_info(time());

$search_options = SearchOptions::get_options();

require "$page->branch/index.html";
$page->output();

?>
