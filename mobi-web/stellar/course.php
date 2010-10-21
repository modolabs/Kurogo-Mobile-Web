<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "StellarData.php";
require_once WEBROOT . "stellar/stellar_lib.php";

function selfURL() {
  return "course.php?id=" . $_REQUEST['id'] . '&back=' . $_REQUEST['back'];
}

$id = $_REQUEST['id'];
$back = $_REQUEST['back'];
$Back = ucwords($back);

$course = StellarData::get_course($id);
$classes = StellarData::get_subjects_with_xref($id);

require "$page->branch/course.html";
$page->output();
    
?>
