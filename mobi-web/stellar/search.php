<?php


require_once "../config/mobi_web_constants.php";
require_once PAGE_HEADER;
require_once LIBDIR . "StellarData.php";
require_once "stellar_lib.php";

function selfURL() {
  $start = $_REQUEST["start"] ? (int)$_REQUEST["start"] : 0;
  $query = http_build_query(array("filter" => $_REQUEST['filter'], "start" => $start));
  return "search.php?$query";
}

$classes = StellarData::search_subjects($_REQUEST['filter']);

// if exactly one class is found redirect to that
// classes detail page
if(count($classes) == 1) {
  header("Location: " . detailURL($classes[0], selfURL()));
  die();
}

$content = new ResultsContent("items", "stellar", $page);

require "$page->branch/search.html";

$page->output();
    
?>
