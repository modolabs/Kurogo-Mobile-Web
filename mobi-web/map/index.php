<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";

class Categorys {
  public static $info = array(
    "buildings"    => array("Building number", "Building &#35;s", "Buildings by Number"),
    "names"        => array("Building name", "Building Names", "Buildings by Name"),
    "residences"   => array("Residences", "Residences", "Residences"),
    "rooms"        => array("Selected rooms", "Selected Rooms", "Selected Rooms"),
    "landmarks"    => array("Streets and Landmarks", "Streets &amp; Landmarks", "Streets &amp; Landmarks"),
    "courts_green" => array("Courts and Green Spaces", "Courts &amp; Green Spaces", "Courts and Green Spaces"),
    "food"         => array("Food services", "Food Services", "Food Services"),
    "parking"      => array("Parking lots", "Parking Lots", "Parking Lots"),
    "library"      => array("Libraries", "Libraries", "Libraries")
  );
}
$category_info = Categorys::$info;

$index = ($page->branch == "Webkit") ? 0 : 2;

$categorys = array();
foreach($category_info as $category => $title) {
  $categorys[$category] = $title[$index];
}

if(!isset($_REQUEST['category'])) {
  $page->cache();
  require "$page->branch/index.html";
} else {
  $category = $_REQUEST['category'];
  $title = $category_info[$category][2];


  if(!isset($_REQUEST['drilldown'])) {
    $places = places();
    if($category=="buildings" || $category=="names") {
      require "$page->branch/$category.html";
    } else {
      require "$page->branch/places.html";
    }
  } else {
    $titlebar = ucwords($category_info[$category][0]);
    $drilldown = $_REQUEST['drilldown'];
    $drilldown_title = $_REQUEST['desc'];
    $places = places_sublist($drilldown);
    require "$page->branch/drilldown.html";
  }
} 



$page->output();

function places() {
  $docRoot = getenv("DOCUMENT_ROOT");
  require_once $docRoot . "/mobi-config/mobi_web_constants.php";
  require "buildings.php";

  if($_REQUEST['category'] == 'buildings') {
    // array needs to be converted to a hash
    $places = array();
    foreach($buildings as $building_number) {
      $places[$building_number] = $building_number;
    }
  } else {
    $places = ${$_REQUEST['category']};
  }
  return $places;
}

function places_sublist($listName) {
  if($_REQUEST['category'] == 'buildings') {
    $drill = new DrillNumeralAlpha($listName, "key");
  } else {
    $drill = new DrillAlphabeta($listName, "key");
  }
  return $drill->get_list(places());
}


function drillURL($drilldown, $name=NULL) {
  $url = categoryURL() . "&drilldown=$drilldown";
  if($name) {
    $url .= "&desc=" . urlencode($name);
  }
  return $url;
}

function categoryURL($category=NULL) {
  $category = $category ? $category : $_REQUEST['category'];
  return "?category=$category";
}

function detailURL($number, $snippet) {
  return "detail.php?selectvalues=$number&snippets=" . urlencode($snippet);
}

function searchURL() {
  return "search.php";
}

    
?>
