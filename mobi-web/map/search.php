<?php

if (isset($_REQUEST['filter']) && $search_terms = $_REQUEST['filter']) {

    // if loc is set then this is a courses location lookup
    if (isset($_REQUEST['loc'])) {
        $results = map_search_courses($search_terms);
        $total = count($results);
    }
    else {
        $results = map_search($search_terms);
        $total = count($results);
    }
  
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
    'back' => 'Search',
    'filter' => urlencode($_REQUEST['filter']),
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
  require_once LIBDIR . '/MapSearch.php';
  $resultObj = searchCampusMap($terms);
  $results = array();
  foreach ($resultObj->results as $result) {
    $results[] = $result;
  }
  return $results;
}

// courses map lookup
function map_search_courses($terms) {
  require_once LIBDIR . '/MapSearch.php';
  $resultObj = searchCampusMapForCourseLoc($terms);
  $results = array();
  foreach ($resultObj->results as $result) {
    $results[] = $result;
  }
  return $results;
}
