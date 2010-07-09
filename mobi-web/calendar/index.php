<?php


//defines all the variables related to being today
require "calendar_lib.inc";

$today = day_info(time());

$search_options = SearchOptions::get_options();

require "$page->branch/index.html";
$page->output();

?>
