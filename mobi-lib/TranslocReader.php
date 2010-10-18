<?php

require 'decodePolylineToArray.php';
require 'encodePolylineFromArray.php';
require 'DiskCache.inc';
require 'MapPlotUtility.php';

define('MASCO_TRANSLOC_FEED', 'http://masco.transloc.com/itouch/feeds/');
define('HARVARD_TRANSLOC_FEED', 'http://harvard.transloc.com/itouch/feeds/');
define('HARVARD_TRANSLOC_MARKERS', 'http://harvard.transloc.com/m/markers/marker.php');
define('STATIC_MAPS_URL', 'http://maps.google.com/maps/api/staticmap?');
define('TRANSLOC_ICON_PATH', '/shuttleschedule/shuttle-transloc.png');
define('BUS_ICON_PATH', '/shuttleschedule/shuttle_stop_pin.png');
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
    '8' => 'nw',
  );

  private $harvardRouteCache;
  private $harvardStopsCache;
  private $mascoRouteCache;
  private $mascoStopsCache;
  
  public function __construct() {
    $this->harvardRouteCache = new DiskCache(CACHE_DIR . '/transloc.harvard.setup', 3600 * 2);
    $this->harvardStopsCache = new DiskCache(CACHE_DIR . '/transloc.harvard.stops', 3600 * 2);
    $this->mascoRouteCache = new DiskCache(CACHE_DIR . '/transloc.masco.setup', 3600 * 2);
    $this->mascoStopsCache = new DiskCache(CACHE_DIR . '/transloc.masco.stops', 3600 * 2);

    if (!$this->harvardRouteCache->isFresh()) {
      $harvardRouteInfo = $this->getTranslocData('setup');
    } else {
      $harvardRouteInfo = $this->harvardRouteCache->read();
    }

    if (!$this->harvardStopsCache->isFresh()) {
      $harvardStopsInfo = $this->getTranslocData('stops');
    } else {
      $harvardStopsInfo = $this->harvardStopsCache->read();
    }

    if (!$this->mascoRouteCache->isFresh()) {
      $mascoRouteInfo = $this->getMascoTranslocData('setup');
    } else {
      $mascoRouteInfo = $this->mascoRouteCache->read();
    }

    if (!$this->mascoStopsCache->isFresh()) {
      $mascoStopsInfo = $this->getMascoTranslocData('stops');
    } else {
      $mascoStopsInfo = $this->mascoStopsCache->read();
    }

    $this->contructorHelper($harvardRouteInfo, $harvardStopsInfo);
    $this->contructorHelper($mascoRouteInfo, $mascoStopsInfo);
  }

  public function refreshSetup() {
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
    $result = json_decode($json, true);

    if ($page == 'setup')
      $this->harvardRouteCache->write($result);
    elseif ($page == 'stops')
      $this->harvardStopsCache->write($result);

    return $result;
  }

  function getMascoTranslocData($page, $args=array()) {
    $args['v'] = 1; // version 1 of api

    $json = file_get_contents(MASCO_TRANSLOC_FEED.$page.'?'.http_build_query($args));
    $result = json_decode($json, true);

    if ($page == 'setup')
      $this->mascoRouteCache->write($result);
    elseif ($page == 'stops')
      $this->mascoStopsCache->write($result);

    return $result;
  }

  function getAgencies() {
    return array_keys($this->agencies);
  }
  
  function getAgenciesAndNames() {
    $agenciesAndNames = array();
    foreach($this->agencies as $agencyID => $agencyInfo) {
      $agenciesAndNames[$agencyID] = $agencyInfo['long_name'];
    }
    return $agenciesAndNames;
  }
  
  function getNameForAgency($agencyID) {
    return $this->agencies[$agencyID]['long_name'];
  }
  
  function getRoutesForAgency($agencyID) {
    return $this->agencies[$agencyID]['routes'];
  }
  
  function getRunningRoutesAndNamesForAgency($agencyID) {
    $runningRoutes = array();
    foreach ($this->getRoutesForAgency($agencyID) as $routeID) {
      if ($this->routeIsRunning($routeID)) {
        $runningRoutes[$routeID] = $this->getNameForRoute($routeID);
      }
    }
    asort($runningRoutes);
    return $runningRoutes;
  }

  function getNonRunningRoutesAndNamesForAgency($agencyID) {
    $nonRunningRoutes = array();
    foreach ($this->getRoutesForAgency($agencyID) as $routeID) {
      if (!$this->routeIsRunning($routeID)) {
        $nonRunningRoutes[$routeID] = $this->getNameForRoute($routeID);
      }
    }
    asort($nonRunningRoutes);
    return $nonRunningRoutes;
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

  function getPathForRoute($routeId) {
    $route = $this->routes[$routeId];

    $path = array();

    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $points = decodePolylineToArray($polyline);
      if (intVal($segment) < 0) {
        $points = array_reverse($points);
      }
      $path = array_merge($path, $points);
    }

    $pathToReturn = array();
    foreach($path as $pathEntity) {
        $pathToReturn[] = array('lat'=>$pathEntity[0], 'lon'=>$pathEntity[1]);
    }

    return $pathToReturn;
  }

  function getStopsForRoute($routeId) {
    $route = $this->routes[$routeId];

    //$path = array();
    $vehicles = $this->getVehiclesForRoute($routeId);
   // print(json_encode($vehicles));


    /*
    foreach ($route['segments'] as $segment) {
      $polyline = $this->segments[abs(intVal($segment))]['points'];
      $points = decodePolylineToArray($polyline);
      if (intVal($segment) < 0) {
        $points = array_reverse($points);
      }
      $path = array_merge($path, $points);
    }
    */
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

    //$pathToReturn = array();
    //foreach($path as $pathEntity) {
    //    $pathToReturn[] = array('lat'=>$pathEntity[0], 'lon'=>$pathEntity[1]);
    //}
    //$stopsToReturn[0]['path'] = $pathToReturn;

    return $stopsToReturn;
  }

  function getAllRoutesInfo() {
      $this->refreshSetup();
      return $this->routes;
  }

 function getOneRouteInfo($route_id) {
      return $this->routes[$route_id];
  }

  private static function getTranslocIconURL() {
      if($_SERVER['SERVER_NAME'] != 'localhost') {
          return 'http://' . $_SERVER['SERVER_NAME'] . TRANSLOC_ICON_PATH;
      } else {
          return SITE_URL . TRANSLOC_ICON_PATH;
      }
  }
  
  private static function getBusIconURL() {
      if($_SERVER['SERVER_NAME'] != 'localhost') {
          return 'http://' . $_SERVER['SERVER_NAME'] . BUS_ICON_PATH;
      } else {
          return SITE_URL . BUS_ICON_PATH;
      }
  }

  function getImageURLForRoute($routeID, $size='400') {
    $route = $this->routes[$routeID];
    $args = array(
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
    $opacity = 'C0';
    $args['path'] = 'weight:4|color:0x'.$route['color'].$opacity.'|enc:'.
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
      
      $iconURL = HARVARD_TRANSLOC_MARKERS.'?'.http_build_query(array(
        'm' => 'bus',
        'c' => $route['color'],
        'h' => $this->arrows[$arrowIndex],
      ));
      $vehicleSuffix .= '&amp;'.http_build_query(array(
        'markers' => 'icon:'.$iconURL.'|'.$lat.','.$lon
      ));
    }

    $bounds = MapPlotUtility::getBounds($this->getPathForRoute($routeID));
    $mapParams = MapPlotUtility::ComputeMapParameters($size, $size, $bounds, 0.0);
    $args = array_merge($args, MapPlotUtility::URLParams($mapParams));
    $iconLatLon = MapPlotUtility::computeLatLon($mapParams, 0.90, 0.88);

    $translocIcon = http_build_query(array(
       'markers' => 'icon:' . self::getTranslocIconURL() . '|shadow:false|' .
                    $iconLatLon['lat'] . ',' . $iconLatLon['lon'] ));


    //error_log(print_r($iconURL, true));
    //print_r(urldecode(($iconURL)));
    return STATIC_MAPS_URL.$translocIcon.'&amp;'.http_build_query($args, 0, '&amp;').$vehicleSuffix;
  }

  function getImageURLForStop($routeID, $stop, $width, $height) {
      $route = $this->routes[$routeID];

       $mapParams = array(
          "center" => array('lat' => $stop['ll'][0], 'lon' => $stop['ll'][1]),
          "width" => $width,
          "height" => $height,
          "zoom" => "16",
       );
       
      $iconLatLon = MapPlotUtility::computeLatLon($mapParams, 0.90, 0.79);
      $translocIconQuery = http_build_query(array(
          'markers' => 'icon:' . self::getTranslocIconURL() . '|shadow:false|' .
                    $iconLatLon['lat'] . ',' . $iconLatLon['lon'] ));

      $stopMarkerQuery = http_build_query(array(
         "markers" => "icon:" . self::getBusIconURL() . "|" . $stop['ll'][0] . ',' . $stop['ll'][1]));

      return STATIC_MAPS_URL . http_build_query(MapPlotUtility::URLParams($mapParams), 0, '&amp;') .
              '&amp;' . $translocIconQuery . '&amp;' . $stopMarkerQuery . '&amp;sensor=false';
  }

  function getAnnouncements() {
    return json_decode($this->getAnnouncementsJSON(), true);
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
              return 'Runs 5:40am - 8:40am, Monday-Friday';
              break;

          case 'Mather Express':
              return 'Runs 7:40am - 4:15pm, Monday-Friday';
              break;

          case 'Quad Express':
              return 'Runs 7:40am - 4:33pm, Monday-Friday';
              break;

          case 'River Houses A':
              return 'Runs 4:30pm - 12:37am, Monday-Friday';
              break;

          case 'River Houses B':
              return 'Runs 4:30pm - 12:37am, Monday-Friday';
              break;

          case 'River Houses C':
              return 'Runs 4:30pm - 12:37am, Monday-Friday';
              break;

          case 'Quad-Yard Express':
              return 'Runs 4:45pm - 12:32am, Monday-Friday';
              break;

          case 'Soldiers Field Park I':
              return 'Runs 5:00pm - 7:55pm, Saturday-Sunday';
              break;

          case 'Soldiers Field Park II':
              return 'Runs 7:20am - 9:50am, Monday-Friday';
              break;

          case 'Soldiers Field Park III':
              return 'Runs 3:50pm - 9:10pm, Monday-Friday';
              break;

          case 'Soldiers Field Park IV':
              return 'Runs 3:50pm - 9:10pm, Monday-Friday';
              break;

         case 'Crimson Campus Cruiser':
             return 'Runs 12:05pm - 4:23pm, Saturday-Sunday';
             break;

         case '1636\'er':
              return 'Runs 4:20pm - 12:33am, Saturday-Sunday';
             break;

         case 'Extended Overnight':
              return 'Runs 12:40am - 3:52am, Sunday-Thursday Nights; 12:40am - 4:52am, Friday-Saturday Nights';
             break;
      }
      return '';
  }

}

function aboutHarvardShuttles() {
    $port = $_SERVER['SERVER_PORT'];
    $port = $port == '' ? '' : ":$port";
    $img_src = 'http://' . $_SERVER['SERVER_NAME'] . $port . HTTPROOT . 'shuttleschedule/Webkit/images/handicapped.png';
    return <<<EOM
<h2>Daytime Van <img style="float:right;" src="$img_src" width="30" height="30"/></h2>
The Daytime Van is designed for persons who, because of mobility impairment or medical condition, find it difficult or impossible to use the regular shuttle bus.  Transportation is door to door within the Cambridge and Allston campuses. This year-round service is scheduled by appointment only. Service hours vary for academic, weekend, summer and holiday periods. For service hours, reservations and other information, please call 617-495-0400. For information for the hearing impaired, please call 617-496-6642 (TTY#)
<h2>Evening Van <img style="float:right;" src="$img_src" width="30" height="30"/></h2>
The Evening Van provides service to areas not on scheduled shuttle routes, between 7pm and 12:30am nightly, in the Cambridge/Allston campus area. Please call 617-495-0400 for more information and service requests. Please refer to map for service boundaries outlined. Last call received at 11:45 pm nightly.
<h2>Charter Service</h2>
Buses and vans are available to University affiliates for charters. We can accommodate small and large groups for both on and off campus service. Charter fees are billed on an hourly basis with a 2 hour minimum. We can provide referrals who offer expanded services.
<br/><br/>
For more information, please call 617-495-0400.
<h2>Stops</h2>
Bus stops are marked with special crimson, red and white signs. If you wish to exit at a non-designated stop, please inform the driver when boarding. Drivers will make requested stops whenever it is safe to do so.
<h2>Bikes on Shuttles</h2>
Harvard Passenger Transport Services gives bicyclists open access to our entire system. You can combine the freedom of riding your bicycle to work or class with the convenience of using Shuttles around campus during inclement weather or at night. Bikes are allowed on all buses equipped with bike racks at any time.
EOM;
}

function aboutMasco() {
    return <<<EOM
<p>The Harvard Medical School shuttle (M2), which is managed by MASCO, runs between the Longwood Medical Area (LMA) and Harvard's Cambridge campus. The shuttle is free to Harvard faculty and staff, and students at HMS, HSPH, GSAS, HSDM, DMS, HBS, and FAS. Other Harvard University graduate students may purchase shuttle tickets at the Info Center in the Holyoke Center
Arcade, or see the <a href="http://www.masco.org/transit/ptsM2_FareInfo.htm">Masco website</a> for other ticket locations.</p>
<p>All M2 shuttles are wheelchair accessible. Weekday service runs from approximate 7:00 a.m. until 11:30 p.m., while Saturday service runs from 8:30 a.m. until 10:30 p.m. There is no service on Sundays or holidays.</p>
<p>Tickets cost $3.25 each way, and may be paid in Crimson Cash. No cash is accepted on the shuttle.</p>
EOM;
}

function shuttlesCalendar() {
    return <<<EOM
<h2>2010-&shy;2011 Shuttles Calendar</h2>
<h2>Full Service</h2>
August 29-November 24<br/>
November 28-December 21<br/>
January 2-March 11<br/>
March 20-May 14<br/>
<h2>No Service</h2>
September 6<br/>
October 11<br/>
November 11<br/>
November 25-27<br/>
December 22-January 1<br/>
March 12-19<br/>
May 15<br/>
EOM;
}

