<?php

require 'decodePolylineToArray.php';
require 'encodePolylineFromArray.php';

define('MASCO_TRANSLOC_FEED', 'http://masco.transloc.com/itouch/feeds/');
define('HARVARD_TRANSLOC_FEED', 'http://harvard.transloc.com/itouch/feeds/');
define('HARVARD_TRANSLOC_MARKERS', 'http://harvard.transloc.com/m/markers/marker.php');
define('STATIC_MAPS_URL', 'http://maps.google.com/maps/api/staticmap?');
define('TRANSLOC_UPDATE_FREQ', 200);
define('ANNOUNCEMENTS_FEED', 'http://harvard.transloc.com/itouch/feeds/announcements?v=1&contents=true');

class TranslocReader {
  private $agencies = array();
  private $routes = array();
  private $stops = array();
  private $segments = array();
  private $activeRoutes = array('lastUpdate' => 0);
  private $arrows = array(
    '1' => 'n',
    '2' => 'ne',
    '3' => 'e',
    '4' => 'se',
    '5' => 's',
    '6' => 'sw',
    '7' => 'w',
    '8' => 'sw',
  );
  
  public function __construct() {
    $harvardRouteInfo = $this->getTranslocData('setup');
    $harvardStopsInfo = $this->getTranslocData('stops');

    $mascoRouteInfo = $this->getMascoTranslocData('setup');
    $mascoStopsInfo = $this->getMascoTranslocData('stops');

    $this->contructorHelper($harvardRouteInfo, $harvardStopsInfo);
    $this->contructorHelper($mascoRouteInfo, $mascoStopsInfo);
  }

  function contructorHelper($routeInfo, $stopsInfo) {
    foreach ($routeInfo['segments'] as $segment) {
      $this->segments[$segment['id']] = $segment;
    }
    foreach ($routeInfo['agencies'] as $agency) {
      $this->agencies[$agency['name']] = $agency;

      foreach ($agency['routes'] as $i => $route) {
        $this->agencies[$agency['name']]['routes'][$i] = $route['id'];
        $this->routes[$route['id']] = $route;
        $this->routes[$route['id']]['agency'] = $agency['name'];
        $this->routes[$route['id']]['vehicles'] = array();
        $this->routes[$route['id']]['active'] = false;
      }
    }

    foreach ($stopsInfo['stops'] as $stop) {
      $this->stops[$stop['id']] = $stop;
    }

    foreach ($stopsInfo['routes'] as $route) {
      $this->routes[$route['id']]['stops'] = $route['stops'];
    }
  }

  function getTranslocData($page, $args=array()) {
    $args['v'] = 1; // version 1 of api
    
    $json = file_get_contents(HARVARD_TRANSLOC_FEED.$page.'?'.http_build_query($args));
    return json_decode($json, true);
  }

    function getMascoTranslocData($page, $args=array()) {
    $args['v'] = 1; // version 1 of api

    $json = file_get_contents(MASCO_TRANSLOC_FEED.$page.'?'.http_build_query($args));
    return json_decode($json, true);
  }

  function getAgencies() {
    return array_keys($this->agencies);
  }
  
  function getNameForAgency($agencyID) {
    return $this->agencies[$agencyID]['long_name'];
  }
  
  function getRoutesForAgency($agencyID) {
    return $this->agencies[$agencyID]['routes'];
  }
  
  function getNameForRoute($routeID) {
    return $this->routes[$routeID]['long_name'];
  }
  
  function updateIfNeeded($harvardUpdate, $mascoUpdate) {
      $this->activeRoutes = array();


      foreach ($harvardUpdate['active_routes'] as $routeID) {
        $this->activeRoutes[$routeID] = array();
      }
      foreach ($mascoUpdate['active_routes'] as $routeID) {
        $this->activeRoutes[$routeID] = array();
      }


      foreach ($harvardUpdate['vehicles'] as $vehicle) {
        if (isset($this->activeRoutes[$vehicle['r']])) {
          $this->activeRoutes[$vehicle['r']][$vehicle['id']] = $vehicle;
        } else {
          error_log('Warning: inactive route '.$vehicle['r'].
            ' has active vehicle '.$vehicle['id']);
        }
      }
      foreach ($mascoUpdate['vehicles'] as $vehicle) {
        if (isset($this->activeRoutes[$vehicle['r']])) {
          $this->activeRoutes[$vehicle['r']][$vehicle['id']] = $vehicle;
        } else {
          error_log('Warning: inactive route '.$vehicle['r'].
            ' has active vehicle '.$vehicle['id']);
        }
      }

      
      $this->activeRoutes['lastUpdate'] = time();
  }

  function updateHarvardAndMascoIfNeeded() {
    if (time() > ($this->activeRoutes['lastUpdate'] + TRANSLOC_UPDATE_FREQ)) {
      $updateHarvard = $this->getTranslocData('update', array('nextstops' => 'true'));
      $updateMasco = $this->getMascoTranslocData('update', array('nextstops' => 'true'));
      $this->updateIfNeeded($updateHarvard,$updateMasco);
    }
  }

  
  function getVehiclesForRoute($routeID) {
    $this->updateHarvardAndMascoIfNeeded();
    
    return isset($this->activeRoutes[$routeID]) ? 
      $this->activeRoutes[$routeID] : array();
  }
  
  function routeIsRunning($routeID) {
    $this->updateHarvardAndMascoIfNeeded();
    return isset($this->activeRoutes[$routeID]);
  }
  
  function getVehiclesLastUpdateTime($routeID) {
    return $this->activeRoutes['lastUpdate'];
  }
  
  function getRoutes() {
    return array_keys($this->routes);
  }
  
  function getActiveRoutes() {
    $this->updateHarvardAndMascoIfNeeded();
    return array_keys($this->activeRoutes);
  }

  function getStops() {
    return $this->stops;
  }

  function getStopsForRoute($routeId) {
    $route = $this->routes[$routeId];

    $path = array();
    $vehicles = $this->getVehiclesForRoute($routeId);
   // print(json_encode($vehicles));


    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $points = decodePolylineToArray($polyline);
      if (intVal($segment) < 0) {
        $points = array_reverse($points);
      }
      $path = array_merge($path, $points);
    }
    //print(json_encode($this->stops));
    //print(json_encode($this->routes[$route['id']]['stops']));

    $stopsToReturn = array();
    foreach ($route['stops'] as $stop) {
      $upComingStop =  false;
            foreach ($vehicles as $vehicle) {
                if($stop == $vehicle['next_stop'])
                    $upComingStop = true;
            }

    if ($upComingStop == true) {
        $stopsToReturn[] = array('id'=> strval($stop),
                                'title'=>$this->stops[$stop]['name'],
                                'lat'=>$this->stops[$stop]['ll'][0],
                                'lon'=>$this->stops[$stop]['ll'][1],
                                'next'=> 1284649000,
                                'upcoming'=> $upComingStop,
                                'predictions'=>array(3600, 7200));

    }
    else {
               $stopsToReturn[] = array('id'=> strval($stop),
                                'title'=>$this->stops[$stop]['name'],
                                'lat'=>$this->stops[$stop]['ll'][0],
                                'lon'=>$this->stops[$stop]['ll'][1],
                                'next'=> 1284649000,
                                'predictions'=>array(3600, 7200));
        }
    }

    if (count($vehicles) > 0)
        $stopsToReturn[] = array('gps'=>true); // if there is an upcomingstop, then we know we have vehicle data
    else
        $stopsToReturn[] = array('gps'=>false);

    $pathToReturn = array();
    foreach($path as $pathEntity) {
        $pathToReturn[] = array('lat'=>$pathEntity[0], 'lon'=>$pathEntity[1]);
    }
    $stopsToReturn[0]['path'] = $pathToReturn;

    return $stopsToReturn;
  }

  function getAllRoutesInfo() {
      return $this->routes;
  }

 function getOneRouteInfo($route_id) {
      return $this->routes[$route_id];
  }
  
  function getImageURLForRoute($routeID, $size='400') {
    $route = $this->routes[$routeID];
    $args = array(
      'size'   => $size.'x'.$size,
      'sensor' => 'false',
    );

    //print_r($route);
    $path = array();
    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $points = decodePolylineToArray($polyline);
      if (intVal($segment) < 0) {
        $points = array_reverse($points);
      }
      $path = array_merge($path, $points);
    }
    //print(json_encode($this->stops));
    //print(json_encode($this->routes[$route['id']]['stops']));
    $args['path'] = 'weight:4|color:0x'.$route['color'].'|enc:'.
          encodePolylineFromArray($path);
    
    $vehicleSuffix = '';
    $vehicles = $this->getVehiclesForRoute($routeID);

    //print(json_encode($this->routes));
    foreach ($vehicles as $vehicle) {
      $lat = $vehicle['ll'][0];
      $lon = $vehicle['ll'][1];
      $heading = $vehicle['h'];
      
      $arrowIndex = ($heading / 45) + 1.5;
      if ($arrowIndex > 8) { $arrowIndex = 8; }
      if ($arrowIndex < 0) { $arrowIndex = 0; }
      $arrowIndex = floor($arrowIndex);
      
      $iconURL = HARVARD_TRANSLOC_MARKERS.'?'.urlencode(http_build_query(array(
        'm' => 'bus',
        'c' => $route['color'],
        'h' => $this->arrows[$arrowIndex],
      )));
      $vehicleSuffix .= '&markers=icon:'.$iconURL.'|'.$lat.','.$lon;
    }

    //print_r($iconURL);
    //print_r(urldecode(($iconURL)));
    return STATIC_MAPS_URL.http_build_query($args).$vehicleSuffix;
      ;
  }




  function getIconUrl($routeID, $size='400') {
    $route = $this->routes[$routeID];
    $args = array(
      'size'   => $size.'x'.$size,
      'sensor' => 'false',
    );

    //print_r($route);
    $path = array();
    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $points = decodePolylineToArray($polyline);
      if (intVal($segment) < 0) {
        $points = array_reverse($points);
      }
      $path = array_merge($path, $points);
    }
    //print(json_encode($this->stops));
    //print(json_encode($this->routes[$route['id']]['stops']));
    $args['path'] = 'weight:4|color:0x'.$route['color'].'|enc:'.
          encodePolylineFromArray($path);

    $vehicleSuffix = '';
    $vehicles = $this->getVehiclesForRoute($routeID);

    //print(json_encode($this->routes));
    foreach ($vehicles as $vehicle) {
      $lat = $vehicle['ll'][0];
      $lon = $vehicle['ll'][1];
      $heading = $vehicle['h'];

      $arrowIndex = ($heading / 45) + 1.5;
      if ($arrowIndex > 8) { $arrowIndex = 8; }
      if ($arrowIndex < 0) { $arrowIndex = 0; }
      $arrowIndex = floor($arrowIndex);

      $iconURL = HARVARD_TRANSLOC_MARKERS.'?'.urlencode(http_build_query(array(
        'm' => 'bus',
        'c' => $route['color'],
        'h' => $this->arrows[$arrowIndex],
      )));
      $vehicleSuffix .= '&markers=icon:'.$iconURL.'|'.$lat.','.$lon;
    }

    
    if ($iconURL != null)
        return urldecode($iconURL);
    else
        return "";
      ;
  }


  function getAnnouncementsJSON() {
      return file_get_contents(ANNOUNCEMENTS_FEED);
  }



  function getBriefDescription($routeName) {

      switch ($routeName) {
          case 'Quad Stadium':
              return 'River Houses via Harvard Square';
              break;

          case 'Mather Express':
              return 'Memorial Hall via Harvard Square';
              break;

          case 'Quad Express':
              return 'Memorial Hall via Harvard Square';
              break;

          case 'River Houses A':
              return 'Harvard Square - Quad - Memorial Hall - Lamont';
              break;

          case 'River Houses B':
              return 'Harvard Square - Quad - Memorial Hall - Lamont';
              break;

          case 'River Houses C':
              return 'Harvard Square - Quad - Memorial Hall - Lamont';
              break;

          case 'Quad-Yard Express':
              return 'Lamont - Harvard Square';
              break;

          case 'Soldiers Field Park I':
              return 'Quad - Lamont Library - via Harvard Square';
              break;

          case 'Soldiers Field Park II':
              return 'Business School - Harvard Square';
              break;

          case 'Soldiers Field Park III':
              return 'Business School - Harvard Square - Memorial Hall - Lamont';
              break;

          case 'Soldiers Field Park IV':
              return 'Business School - Harvard Square - Memorial Hall - Lamont';
              break;
              
         case 'Crimson Campus Cruiser':
             return 'Quad - Mather House - via Memorial Hall';
             break;

         case '1636\'er':
             return '';
             break;

         case 'Extended Overnight':
             return 'Quad - Mather House via Memorial Hall';
             break;      
      }
      return '';
  }


   function getSummary($routeName) {

      switch ($routeName) {
          case 'Quad Stadium':
              return 'Runs 5.40am - 8.40am, Monday-Friday';
              break;

          case 'Mather Express':
              return 'Runs 7.40am - 4.15pm, Monday-Friday';
              break;

          case 'Quad Express':
              return 'Runs 7.40am - 4.33pm, Monday-Friday';
              break;

          case 'River Houses A':
              return 'Runs 4.30pm - 12.37am, Monday-Friday';
              break;

          case 'River Houses B':
              return 'Runs 4.30pm - 12.37am, Monday-Friday';
              break;

          case 'River Houses C':
              return 'Runs 4.30pm - 12.37am, Monday-Friday';
              break;

          case 'Quad-Yard Express':
              return 'Runs 4.45pm - 12.32am, Monday-Friday';
              break;

          case 'Soldiers Field Park I':
              return 'Runs 5.00pm - 7.55pm, Saturday-Sunday';
              break;

          case 'Soldiers Field Park II':
              return 'Runs 7.20am - 9.50am, Monday-Friday';
              break;

          case 'Soldiers Field Park III':
              return 'Runs 3.50pm - 9.10pm, Monday-Friday';
              break;

          case 'Soldiers Field Park IV':
              return 'Runs 3.50pm - 9.10pm, Monday-Friday';
              break;

         case 'Crimson Campus Cruiser':
             return 'Runs 12.20pm - 4.23pm, Saturday-Sunday';
             break;

         case '1636\'er':
              return 'Runs 4.20pm - 12.33am, Saturday-Sunday';
             break;

         case 'Extended Overnight':
              return 'Runs 12.40am - 3.52am, Daily';
             break;
      }
      return '';
  }

}
