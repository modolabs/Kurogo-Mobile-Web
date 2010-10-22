<?php

/****************************************************************
*
*  Copyright 2010 The President and Fellows of Harvard College
*  Copyright 2010 Modo Labs Inc.
*
*****************************************************************/

require_once realpath(LIB_DIR . '/feeds/ArcGISServer.php');

$content = "";

switch ($_REQUEST['command']) {

  case 'capabilities':
    $results = ArcGISServer::getCapabilities();
    $content = json_encode($results);
    break;
  
  case 'proj4specs':
    $wkid = $_REQUEST['wkid'];
    $results = ArcGISServer::getWkidProperties($wkid);
    $content = json_encode($results);
    break;
  
  case 'tilesupdated':
    $date = file_get_contents($GLOBALS['siteConfig']->getVar('MAP_TILE_CACHE_DATE'));
    $data = array("last_updated" => trim($date));
    $content = json_encode($data);
    break;
    
  case 'categorytitles':
    $collections = ArcGISServer::getLayers();
    $result = array();
    foreach ($collections as $id => $name) {
      $result[] = array(
        'categoryName' => $name, 
        'categoryId' => $id,
      );
    }
    $content = json_encode($result);
    break;
  
  case 'search':
    if (isset($_REQUEST['q'])) {
      $searchTerms = $_REQUEST['q'];
    
      if (isset($_REQUEST['loc'])) {
        $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query(array(
          'loc' => $_REQUEST['loc'],
          'str' => $searchTerms,
        ));
        $content = file_get_contents($url);
    
      } else {
        if (isset($_REQUEST['category'])) {
          $category = $_REQUEST['category'];
          $results = ArcGISServer::search($_REQUEST['q'], $category);
          if ($results === FALSE) {
            $results = array();
          } else if (count($results) <= 1) {
            // if we're looking at a single result,
            // see if we can get more comprehensive info from the main search
            $moreResults = ArcGISServer::search($_REQUEST['q']);
            if (count($moreResults->results) == 1) {
              $result = $moreResults->results[0];
              if (count($results)) {
                $attributes = $results->results[0]->attributes;
                foreach ($attributes as $name => $value) {
                  $result->attributes->{$name} = $value;
                }
              }
              $results = $moreResults;
            }
          }
    
        } else {
          require_once realpath(LIB_DIR.'/feeds/MapSearch.php');
          $results = searchCampusMap($_REQUEST['q']);
        }
        $content = json_encode($results);
      }
    } elseif (isset($_REQUEST['category'])) {
      $category = $_REQUEST['category'];
      $results = array();
      $layer = ArcGISServer::getLayer($category);
      if ($layer) {
        $featurelist = $layer->getFeatureList();
        foreach ($featurelist as $featureId => $attributes) {
          $results[] = array_merge($attributes,
            array('displayName' => $featureId));
        }
      }
      $content = json_encode($results);
    }
    break;
}

header('Content-Length: ' . strlen($content));
echo $content;
