<?php

if ($search_terms = $_REQUEST['filter']) {
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
  $params = array(
    'selectvalues' => $resultObj->value,
    'info' => $resultObj->attributes,
    );

  return 'detail.php?' . http_build_query($params);
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

function exact_match($terms, $snippets) {
  $terms = strtolower(trim($terms));
  foreach($snippets as $snippet) {
    if(strtolower($snippet) == $terms) {
      return True;
    }
  }
  return False;
}
    
?>
