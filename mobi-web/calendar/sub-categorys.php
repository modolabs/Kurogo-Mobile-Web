<?php


require_once "../config/mobi_web_constants.php";
require PAGE_HEADER;
require LIBDIR . "mit_calendar.php";
require "calendar_lib.php";

$category = MIT_Calendar::Category($_REQUEST['id']);
$categorys = MIT_Calendar::subCategorys($category);

if(count($categorys) == 0) {
  header("Location: " . categoryURL($category));
}

require "$page->branch/sub-categorys.html";
$page->output();

?>
