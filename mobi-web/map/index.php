<?php

require_once LIBDIR . '/ArcGISServer.php';

if (!isset($_REQUEST['category'])) {
  $categories = ArcGISServer::getLayers();
  require "$page->branch/index.html";
} else {
  $category = $_REQUEST['category'];
  $layer = ArcGISServer::getLayer($category);
  $title = $layer->getName();
  $places = $layer->getFeatureList();
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
