<?php

require_once('TransitDataParser.php');
require_once('DiskCache.php');

define('TRANSLOC_SERVICE_URL_FORMAT', 'http://%s.transloc.com/itouch/feeds/%s?');
define('TRANSLOC_MARKERS_URL_FORMAT', 'http://%s.transloc.com/m/markers/marker.php');
define('TRANSLOC_ICON_PATH', '/shuttleschedule/shuttle-transloc.png');
define('BUS_ICON_PATH', '/shuttleschedule/shuttle_stop_pin.png');
define('TRANSLOC_UPDATE_FREQ', 200);
define('ANNOUNCEMENTS_FEED', 'http://harvard.transloc.com/itouch/feeds/announcements?v=1&contents=true');

class TranslocTransitDataParser extends TransitDataParser {
  private static $caches = array();
  private $routeColors = array();
  private $translocHostname = '';
  
  protected function isLive() {
    return true;
  }
  
  protected function getMapMarkersForVehicles($vehicles) {
    $query = '';
    
    foreach ($vehicles as $vehicle) {
      $arrowIndex = ($vehicle['heading'] / 45) + 1.5;
      if ($arrowIndex > 8) { $arrowIndex = 8; }
      if ($arrowIndex < 0) { $arrowIndex = 0; }
      $arrowIndex = floor($arrowIndex);

      $feedURL = sprintf(TRANSLOC_MARKERS_URL_FORMAT, $vehicle['agencyID']).'?';
      $iconURL = $feedURL.http_build_query(array(
        'm' => 'bus',
        'c' => $this->routeColors[$vehicle['routeID']],
        'h' => self::$arrows[$arrowIndex],
      ));

      $query .= '&'.http_build_query(array(
        'markers' => "icon:{$iconURL}|{$vehicle['lat']},{$vehicle['lon']}",
      ));
    }
    
    return $query;
  }  

  public function getRouteVehicles($routeID) {
    $updateInfo = self::getData($this->translocHostname, 'update');
    
    $vehicles = array();
    foreach ($updateInfo['vehicles'] as $vehicleInfo) {
      if ($this->getRoute($vehicle['r'])->isRunning()) {
        $vehicles[$vehicleInfo['id']] = array(
          'secsSinceReport' => $vehicleInfo['t'],
          'lat'             => $vehicleInfo['ll'][0],
          'lon'             => $vehicleInfo['ll'][1],
          'heading'         => $vehicleInfo['h'],
          'nextStop'        => $vehicleInfo['next_stop'],
          'agencyID'        => $this->getRoute($vehicle['r'])->getAgencyID(),
          'routeID'         => $vehicle['r'],
        );
      } else {
        error_log('Warning: inactive route '.$vehicleInfo['r'].
          ' has active vehicle '.$vehicleInfo['id']);
      }
    }
    return $vehicles;
  }

  protected function loadData($agencyIDs, $routeIDs, $args) {
    $this->translocHostname = $args['hostname'];
  
    $agencyIDs = array_unique($agencyIDs);

    $setupInfo = self::getData($this->translocHostname, 'setup');
        
    $segments = array();
    foreach ($setupInfo['segments'] as $segmentInfo) {
      $segments[$segmentInfo['id']] = Polyline::decodeToArray($segmentInfo['points']);
    }
    
    $mergedSegments = array();
    foreach ($setupInfo['agencies'] as $agency) {
      $agencyID = $agency['name'];

      foreach ($agency['routes'] as $i => $routeInfo) {
        $routeID = $routeInfo['id'];
      
        $this->addRoute(new TransitRoute(
          $routeID, 
          $agencyID, 
          $routeInfo['long_name'], 
          '' // will be overridden
        ));

        $this->routeColors[$routeInfo['id']] = $routeInfo['color'];
        
        $path = array();
        foreach ($routeInfo['segments'] as $segmentNum) {
          $segmentNum = intval($segmentNum);
          
          $segmentPath = $segments[abs($segmentNum)];
          if ($segmentNum < 0) {
            $segmentPath = array_reverse($segmentPath);
          }
          
          $path = array_merge($path, $segmentPath);
        }
        $this->getRoute($routeID)->addPath(new TransitPath('loop', $path));
        
        // special service type
        $routeService = new TranslocTransitService(
          "{$routeID}_service", 
          $this->translocHostname, 
          $routeID
        );
        
        // segments will be filled in below by the stop config
        $mergedSegments[$routeID] = new TranslocTransitSegment(
          'loop',
          '',
          $routeService,
          'loop',
          $this->translocHostname, 
          $routeID
        );
      }
    }

    $stopsInfo = self::getData($this->translocHostname, 'stops');
    foreach ($stopsInfo['stops'] as $stopInfo) {
      $this->addStop(new TransitStop(
        $stopInfo['id'], 
        $stopInfo['name'], 
        '', 
        $stopInfo['ll'][0], 
        $stopInfo['ll'][1]
      ));
    }
    foreach ($stopsInfo['routes'] as $routeInfo) {
      $routeID = $routeInfo['id'];
      foreach($routeInfo['stops'] as $stopIndex => $stopID) {
        $mergedSegments[$routeID]->addStop($stopID, $stopIndex);
      }
    }
    
    foreach ($mergedSegments as $routeID => $segment) {
      $this->getRoute($routeID)->addSegment($segment);
    }
  }
  
  private static function getCacheForCommand($action) {
    $cacheKey = $action;
    
    if (!isset(self::$caches[$cacheKey])) {
      $cacheTimeout = 20;
      $suffix = 'json';
      
      switch ($action) {
        case 'setup': 
        case 'stops':
          $cacheTimeout = 3600;
          break;
 
        case 'update':
          $cacheTimeout = 120;
          break;
     }
  
      self::$caches[$cacheKey] = new DiskCache(CACHE_DIR.'/TranslocParser', $cacheTimeout, TRUE);
      self::$caches[$cacheKey]->preserveFormat();
      self::$caches[$cacheKey]->setSuffix(".$cacheKey.$suffix");
    }
    
    return self::$caches[$cacheKey];
  }
  
  private static function getData($hostname, $action) {
    $cache = self::getCacheForCommand($action);
    $cacheName = $hostname;


    if ($cache->isFresh($cacheName)) {
      $results = json_decode($cache->read($cacheName), true);
      
    } else {
      $params['v'] = 1; // version 1 of api
      if ($action == 'update') {
        $params['nextstops'] = 'true';
      }

      $url = sprintf(TRANSLOC_SERVICE_URL_FORMAT, $hostname, $action).http_build_query($params);
      $contents = file_get_contents($url);
      error_log("TranslocTransitDataParser requested $url", 0);
      
      if (!$contents) {
        error_log("Failed to read contents from $url, reading expired cache");
        $results = json_decode($cache->read($cacheName), true);
        
      } else {
        $results = json_decode($contents, true);
        if ($results) {
          $cache->write($contents, $cacheName);
          
        } else {
          error_log("JSON from $url had errors, reading expired cache");
          $results = json_decode($cache->read($cacheName), true);
        }
      }
    }
    return $results;
  }
  
  public function getRouteInfo($routeID, $time=null) {
    $routeInfo = parent::getRouteInfo($routeID, $time);
    
    $updateInfo = self::getData($this->translocHostname, 'update');

    $runnningStops = array();
    foreach ($updateInfo['vehicles'] as $vehicleInfo) {
      $runnningStops[$vehicleInfo['next_stop']] = true;
    }

    // Add upcoming stop information
    foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
      $routeInfo['stops'][$stopID]['upcoming'] = isset($runnningStops[$stopID]);
    }

    return $routeInfo;
  }

  public static function translocRouteIsRunning($hostname, $routeID) {
    $updateInfo = self::getData($hostname, 'update');
    
    return in_array($routeID, $updateInfo['active_routes']);
  }
}

// Special version of the TransitService class
class TranslocTransitService extends TransitService {
  private $routeID = null;
  private $hostname = null;

  function __construct($id, $hostname, $routeID) {
    parent::__construct($id);
    $this->hostname = $hostname;
    $this->routeID = $routeID;
  }

  public function isRunning($time) {
    return TranslocTransitDataParser::translocRouteIsRunning($this->hostname, $this->routeID);
  }
}

// Special version of the TransitSegment class
class TranslocTransitSegment extends TransitSegment {
  private $routeID = null;
  private $hostname = null;

  function __construct($id, $name, $service, $direction, $hostname, $routeID) {
    parent::__construct($id, $name, $service, $direction);
    $this->hostname = $hostname;
    $this->routeID = $routeID;
  }

  public function isRunning($time) {
    return TranslocTransitDataParser::translocRouteIsRunning($this->hostname, $this->routeID);
  }
  
  public function getArrivalTimesForStop($stopID=null) {
    return array();
  }
  
  public function getNextArrivalTime($time, $stopIndex) {
    return 0;
  }
}
