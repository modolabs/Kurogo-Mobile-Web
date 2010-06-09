<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "campus_map.php";

if ($page->branch == 'Webkit') {
  $categories = array(
    'buildings' => 'Building Number',
    'names' => 'Building Name',
    );
} else {
  $categories = array(
    'buildings' => 'Buildings by Number',
    'names' => 'Buildings by Name',
    );
}

$category_info = Buildings::category_titles();

if(!isset($_REQUEST['category'])) {

  $categories = array_merge($categories, $category_info);
  $page->cache();
  require "$page->branch/index.html";

} else {

  $category = $_REQUEST['category'];

  switch ($category) {
  case 'buildings':
  case 'names':
    if (isset($_REQUEST['drilldown'])) {
      $title = $categories[$category];
      $drilldown = $_REQUEST['drilldown'];
      $drilldown_title = $_REQUEST['desc'];
      $places = places_sublist($drilldown);
      require "$page->branch/drilldown.html";
    } else {
      require "$page->branch/$category.html";
    }
    break;

  default:
    $title = Buildings::category_title($category);
    $places = Buildings::category_items($category);
    require "$page->branch/places.html";
    break;
  }
} 

$page->output();

function places_sublist($listName) {
  $places = array();

  if($_REQUEST['category'] == 'buildings') {
    $drill = new DrillNumeralAlpha($listName, "key");

    $keys = array_keys(Buildings::$bldg_data);
    natsort($keys);
    $places = array_combine($keys, $keys);
    
  } else {
    $drill = new DrillAlphabeta($listName, "key");

    foreach (Buildings::$bldg_data as $id => $info) {
      $places[$info['name']] = $id;
    }
    uksort($places, 'strnatcasecmp');
  }
  return $drill->get_list($places);
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
