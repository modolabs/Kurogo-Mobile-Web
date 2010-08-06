<?php

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
   $collections = ArcGISServer::getCollections();
   $result = array();
   foreach ($collections as $id => $name) {
     $result[] = array(
       'categoryName' => $name, 
       'categoryId' => $id,
       );
   }
   $content = json_encode($result);
   break;

 case 'category':
   require_once LIBDIR . '/ArcGISServer.php';
   if ($category = $_REQUEST['id']) {
     $collection = ArcGISServer::getCollection($category);
     $featurelist = $collection->getFeatureList();
     $results = array();
     foreach ($featurelist as $featureId => $attributes) {
       $results[] = array_merge($attributes,
                                array('displayName' => $featureId));
     }
     $content = json_encode($results);
   }
   break;

 case 'search':
   if (isset($_REQUEST['q'])) {

     if (isset($_REQUEST['loc'])) {
  
       $params = array(
         'loc' => $_REQUEST['loc'],
         'str' => $_REQUEST['q'],
         );
  
       $query = http_build_query($params);
       $json = file_get_contents(MAP_SEARCH_URL . '?' . $query);
  
     } else {
       require_once LIBDIR . '/ArcGISServer.php';
       $json = ArcGISServer::search($_REQUEST['q']);
     }
     $content = json_encode($json);
   }
   break;
}

header('Content-Length: ' . strlen($content));
echo $content;