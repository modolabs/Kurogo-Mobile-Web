<?php

require 'decodePolylineToArray.php';
require 'encodePolylineFromArray.php';

define('HARVARD_TRANSLOC_FEED', 'http://harvard.transloc.com/itouch/feeds/');
define('HARVARD_TRANSLOC_MARKERS', 'http://harvard.transloc.com/m/markers/marker.php');
define('STATIC_MAPS_URL', 'http://maps.google.com/maps/api/staticmap?');
define('TRANSLOC_UPDATE_FREQ', 200);

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
    $routeInfo = $this->getTranslocData('setup');
    $stopsInfo = $this->getTranslocData('stops');
    
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
  
  function updateIfNeeded() {
    if (time() > ($this->activeRoutes['lastUpdate'] + TRANSLOC_UPDATE_FREQ)) {
      $update = $this->getTranslocData('update', array('nextstops' => 'true'));
      
      $this->activeRoutes = array();
      foreach ($update['active_routes'] as $routeID) {
        $this->activeRoutes[$routeID] = array();
      }
      foreach ($update['vehicles'] as $vehicle) {
        if (isset($this->activeRoutes[$vehicle['r']])) {
          $this->activeRoutes[$vehicle['r']][$vehicle['id']] = $vehicle;
        } else {
          error_log('Warning: inactive route '.$vehicle['r'].
            ' has active vehicle '.$vehicle['id']);
        }
      }
      $this->activeRoutes['lastUpdate'] = time();
    }
  }
  
  function getVehiclesForRoute($routeID) {
    $this->updateIfNeeded();
    
    return isset($this->activeRoutes[$routeID]) ? 
      $this->activeRoutes[$routeID] : array();
  }
  
  function routeIsRunning($routeID) {
    $this->updateIfNeeded();
    return isset($this->activeRoutes[$routeID]);
  }
  
  function getVehiclesLastUpdateTime($routeID) {
    return $this->activeRoutes['lastUpdate'];
  }
  
  function getRoutes() {
    return array_keys($this->routes);
  }
  
  function getActiveRoutes() {
    $this->updateIfNeeded();
    return array_keys($this->activeRoutes);
  }

  function getStops() {
    return $this->stops;
   /* $stopsToReturn = array();
      foreach($this->routes as $route) {
          $route_id = $route['id'];

          foreach($route['stops'] as $stop) {
              if (in_array($stop, $this->stops)) {
                 $stopsToReturn[] = $this->stops
              }

          }
      }*/
  }

  function getStopsForRoute($routeId) {
    $route = $this->routes[$routeId];

    $path = array();
    $vehicles = $this->getVehiclesForRoute($routeId);
   // print(json_encode($vehicles));


    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $path = array_merge($path, decodePolylineToArray($polyline));
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

    $path = array();
    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $path = array_merge($path, decodePolylineToArray($polyline));
    }
    //print(json_encode($this->stops));
    //print(json_encode($this->routes[$route['id']]['stops']));
    $args['path'] = 'weight:4|color:0x'.$route['color'].'|enc:'.
          encodePolylineFromArray($path);
    
    $vehicleSuffix = '';
    $vehicles = $this->getVehiclesForRoute($routeID);

    print(json_encode($vehicles));
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
    
    return STATIC_MAPS_URL.http_build_query($args).$vehicleSuffix;
      ;
  }
}
