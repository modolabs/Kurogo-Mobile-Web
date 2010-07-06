<?php

if($search_terms = $_REQUEST['filter']) {
  $results = map_search($search_terms);
  $total = count($results);
  if(count($results) == 1) {
    header("Location: " . detailURL($results[0]));
  } else {
    $content = new ResultsContent("items", "map", $page);
    require "$page->branch/search.html";
    $page->output();
  }
} else {
  header("Location: ./");
}

function detailURL($place) {
  $id = substr($place->id, 7);
  return "detail.php?selectvalues=$id&snippets=" . urlencode(implode(", ", $place->snippets));
}

function map_search($terms) {
  
  $query = array(
    "type"   => "query",
    "q"      => $terms, 
    "output" => "json" 
  );

  $json = file_get_contents("http://map-dev.mit.edu/search?" . http_build_query($query));

  $data = json_decode($json);

  //sort data by priority
  $high = array();
  $low = array();
  foreach($data as $place) {
    if(exact_match($terms, $place->snippets)) {
      $high[] = $place;
    } else {
      $low[] = $place;
    }
  }
   
  return array_merge($high, $low);
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
