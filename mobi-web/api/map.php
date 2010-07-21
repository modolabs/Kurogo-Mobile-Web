<?php

switch ($_REQUEST['command']) {

 case 'capabilities':
   require_once LIBDIR . '/ArcGISTileServer.php';
   $json = ArcGISTileServer::getCapabilities();
   echo json_encode($json);
   break;

 case 'proj4specs':
   require_once LIBDIR . '/ArcGISTileServer.php';
   $wkid = $_REQUEST['wkid'];
   $json = ArcGISTileServer::getWkidProperties($wkid);
   echo json_encode($json);
   break;

 case 'tilesupdated':
   $date = file_get_contents(MAP_TILE_CACHE_DATE);
   $data = array("last_updated" => trim($date));
   echo json_encode($data);
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
       require_once LIBDIR . '/ArcGISTileServer.php';

       $json = ArcGISTileServer::search($_REQUEST['q']);
     }
     echo $json;
   }
   break;
}

