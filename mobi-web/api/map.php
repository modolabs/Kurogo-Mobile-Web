<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

$content = "";

switch ($_REQUEST['command']) {

 case 'capabilities':
   require_once LIBDIR . '/ArcGISServer.php';
   $json = ArcGISServer::getCapabilities();
   $content = json_encode($json);
   break;

 case 'proj4specs':
   require_once LIBDIR . '/ArcGISServer.php';
   $wkid = $_REQUEST['wkid'];
   $json = ArcGISServer::getWkidProperties($wkid);
   $content = json_encode($json);
   break;

 case 'tilesupdated':
   $date = file_get_contents(MAP_TILE_CACHE_DATE);
   $data = array("last_updated" => trim($date));
   $content = json_encode($data);
   break;

 case 'categorytitles':
   require_once LIBDIR . '/ArcGISServer.php';
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
  
       $params = array(
         'loc' => $_REQUEST['loc'],
         'str' => $searchTerms,
         );
  
       $query = http_build_query($params);
       $content = file_get_contents(MAP_SEARCH_URL . '?' . $query);
  
     } else {

       require_once LIBDIR . '/ArcGISServer.php';
       if (isset($_REQUEST['category'])) {
         $category = $_REQUEST['category'];
         $json = ArcGISServer::search($_REQUEST['q'], $category);
         if ($json === FALSE) {
           $json = array();
         } else if (count($json) <= 1) {
           // if we're looking at a single result,
           // see if we can get more comprehensive info from the main search
           $moreJSON = ArcGISServer::search($_REQUEST['q']);
           if (count($moreJSON->results) == 1) {
             $result = $moreJSON->results[0];
             if (count($json)) {
               $attributes = $json->results[0]->attributes;
               foreach ($attributes as $name => $value) {
                 $result->attributes->{$name} = $value;
               }
             }
             $json = $moreJSON;
           }
         }

       } else {
         require_once LIBDIR . '/MapSearch.php';
         $json = searchCampusMap($_REQUEST['q']);
       }
       $content = json_encode($json);
     }
   } elseif (isset($_REQUEST['category'])) {
     require_once LIBDIR . '/ArcGISServer.php';
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