<?php

require_once LIBDIR . '/ArcGISServer.php';

if (!isset($_REQUEST['category'])) {
  $categories = ArcGISServer::getCollections();
  require "$page->branch/index.html";
} else {
  $category = $_REQUEST['category'];
  $collection = ArcGISServer::getCollection($category);
  $title = $collection->getMapName();
  $places = $collection->getFeatureList();
  require "$page->branch/drilldown.html";
}

$page->output();

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

function detailURL($name, $category, $info) {
  $params = array(
    'selectvalues' => $name,
    'category' => $category,
    'info' => $info,
    );
  return 'detail.php?' . http_build_query($params);
}

function searchURL() {
  return "search.php";
}

    
?>
