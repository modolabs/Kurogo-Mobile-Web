<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "rss_services.php";

$ThreeDown = new ThreeDown();
$states = $ThreeDown->get_feed();
$title = $_REQUEST['title'];
$text = explode("\n", $states[$title]['text']);
$paragraphs = array();
foreach($text as $paragraph) {
  if($paragraph) {
    $paragraphs[] = htmlentities($paragraph);
  }
}


$long_date = date("l, F j, Y G:i:s", $states[$title]['unixtime']);

require "$page->branch/detail.html";
$page->module('3down');
$page->output();
    
?>
