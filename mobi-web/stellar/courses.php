<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "StellarData.php";
require_once WEBROOT . "stellar/stellar_lib.php";

$which = $_REQUEST['which'];

if($which == "other") {
  $courses = StellarData::get_others();
  $title = "Other Courses";
} else {
  $all_courses = StellarData::get_courses();
  $drill = new DrillNumeralAlpha($which, "key");
  $courses = $drill->get_list($all_courses);
  $title = "Courses $which";
}

require "$page->branch/courses.html";

$page->output();
    
?>
