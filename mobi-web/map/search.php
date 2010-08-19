<?php

if (isset($_REQUEST['filter']) && $search_terms = $_REQUEST['filter']) {
  $results = map_search($search_terms);
  $total = count($results);
  if ($total == 1) {
    header("Location: " . detailURL($results[0]));
  } else {
    $content = new ResultsContent("items", "map", $page);
    require "$page->branch/search.html";
    $page->output();
  }
} else {
  header("Location: ./");
}

function detailURL($resultObj) {
  $attributes = $resultObj->attributes;

  $params = array(
    'selectvalues' => titleFromResult($resultObj),
    'info' => $attributes,
    );

  return 'detail.php?' . http_build_query($params);
}

function titleFromResult($resultObj) {
  $attributes = $resultObj->attributes;
  $itemTitle = $attributes->{'Building Name'};
  if (!$itemTitle)
    $itemTitle = $resultObj->value;

  return $itemTitle;
}

function map_search($terms) {
  require_once LIBDIR . '/ArcGISServer.php';
  $resultObj = ArcGISServer::search($terms);
  $results = array();
  foreach ($resultObj->results as $result) {
    $results[] = $result;
  }
  return $results;
}
