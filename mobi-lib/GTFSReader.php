<?php

/* this file interacts with a GTFS feed based on the schedule
 * published by Parking & Transportation and NextBus' routeConfig
 *
 * php must be compiled with support for Zip files.
 */

require_once "lib_constants.inc";

require_once 'ShuttleObjects.php';
require_once 'TimeRange.php';
require_once 'datetime_lib.php';
require_once 'NextBusReader.php';

// encoding a polyline in base64
// http://code.google.com/apis/maps/documentation/utilities/polylinealgorithm.html
// (don't know if php has something more built-in to do this)
function base64EncodeNumber($number) {
  $num = round($number * 100000, 0);
  if ($num > 0) {
    $bin = base_convert($num << 1, 10, 2);
    $bin = ltrim($bin, '0');
  } else if ($num < 0) {
    $bin = base_convert(($num << 1) + 1, 10, 2);
    $bin = ltrim($bin, '0');
  } else {
    $bin = '0';
  }

  $chunks = ceil(strlen($bin) / 5);
  $len = $chunks * 5;
  $bin = str_pad($bin, $len, '0', STR_PAD_LEFT);
  $result = '';
  for ($i = $chunks; $i > 0; $i--) {
    $chunk = substr($bin, ($i-1)*5, 5);
    if ($i > 1) {
      $value = (base_convert($chunk, 2, 10) | 0x20) + 63;
    } else {
      $value = base_convert($chunk, 2, 10) + 63;
    }
    $result .= chr($value);
  }
  return $result;
}

class ShuttleSchedule {

  private static $agencies = array();
  private static $gtfs;

  // backward compatibility

  public static function is_running($route_id, $time=NULL) {
    if ($time === NULL)
      $time = time();
    return self::$gtfs->getRoute($route_id)->isRunning($time);
  }

  public static function get_title($route_id) {
    return self::$gtfs->getRoute($route_id)->long_name;
  }

  public static function list_stop_times($route_id) {
    $nextLoop = self::getNextLoop($route_id);
    $time = time();
    $prevTime = 0;
    $stops = array();
    $predicted = FALSE;
    foreach ($nextLoop as $stop_id => $predictions) {
      if ($stop_id == 'lastUpdate') {
	$predicted = TRUE;
	continue;
      }

      $stop = self::getStop($stop_id);
      $nextTime = isset($predictions[0]) ? $predictions[0] : 0;
      $stopData = array(
        'id' => $stop_id,
	'title' => $stop->name,
	'lat' => $stop->lat,
	'lon' => $stop->lon,
	'next' => $nextTime,
	);

      if ($nextTime < $prevTime)
	$stopData['upcoming'] = TRUE;
      $prevTime = $nextTime;

      if (count($predictions) > 1) {
	$offsets = array();
	foreach (array_slice($predictions, 1) as $aTime) {
	  $offsets[] = $aTime - $time;
	}
	$stopData['predictions'] = $offsets;
      }

      $stops[] = $stopData;
    }

    $lastIndex = count($stops) - 1;

    if ($stops[0]['next'] < $stops[$lastIndex]['next']) {
      $stops[0]['upcoming'] = TRUE;
    }

    $stops[] = array('gps' => $predicted);

    return $stops;
  }

  public static function image_tag($size, $trip, $upcoming_stops) {
    $tag = '';

    $url = "http://maps.google.com/maps/api/staticmap?";

    $center = '42.35904, -71.09355';
    $zoom = 13;

    $markers = "color:red";
    foreach ($upcoming_stops as $stop) {
      $stop = ShuttleSchedule::getStop($stop);
      $markers .= '|' . $stop->lat . ',' . $stop->lon;
    }

    $params = array(
      'size' => $size . 'x' . $size,
      'markers' => $markers,
      'sensor' => 'false',
      );

    if (count($trip->shape->points) > 1) {
      $path = 'weight:3|color:red|enc:';
      $numPoints = count($trip->shape->points);
      $totalCount = 0;
      $usedCount = 0;
      $prevLat = 0;
      $prevLon = 0;
      $latSum = 0;
      $lonSum = 0;

      foreach ($trip->shape->points as $point) {
	$latSum += $point[0];
	$lonSum += $point[1];

        $lat = $point[0] - $prevLat;
        $lon = $point[1] - $prevLon;

        $prevLat = $point[0];
        $prevLon = $point[1];
        $path .= base64EncodeNumber($lat) . base64EncodeNumber($lon);
	$usedCount++;
      }
    
      $center = strval($latSum / $usedCount) . ',' . strval($lonSum / $usedCount);
      $params['path'] = $path;

    } else {
      $params['zoom'] = $zoom;
    }

    $params['center']= $center;

    $query = $url . http_build_query($params);
    $tag = '<img src="' . $query . '" width="' . $size
      . '" height="' . $size . '" id="mapimage" alt="Map" />';
    return $tag;
  }

  // 

  public static function getAllStops() {
    $results = array();
    $stops = self::$gtfs->getAllStops();
    foreach ($stops as $stop) {
      $results[] = array(
        'title' => $stop->name,
	'lon' => $stop->lon,
	'lat' => $stop->lat,
	'id' => $stop->id,
	'routes' => $stop->routes,
	);
    }
    return $results;
  }

  public static function getVehicleLocations($route_id) {
    $route = self::getRoute($route_id);
    $agency = self::$agencies[$route->agency_id];
    return $agency->vehicleLocations($route_id);
  }

  // functions for upcoming schedule
  // use predictions if exist; schedule otherwise

  public static function getNextLoop($route_id) {
    $route = self::$gtfs->getRoute($route_id);
    if (isset(self::$agencies[$route->agency_id])) {
      self::$agencies[$route->agency_id]->routeConfig($route_id);
    }

    $nextLoop = NULL;
    if ($route->isRunning()) {
      $nextLoop = self::getNextPredictedLoop($route_id);
    }

    if (!$nextLoop) {
      $nextLoop = self::getNextScheduledLoop($route_id, time());
    }
    return $nextLoop;
  }

  public static function getNextPredictedLoop($route_id) {
    $predictions = array();
    $time = time();
    $route = self::$gtfs->getRoute($route_id);
    if (isset(self::$agencies[$route->agency_id])) {
      $predictions = self::$agencies[$route->agency_id]->predictionsForRoute($route_id);
    }
    return $predictions;
  }

  public static function getNextScheduledLoop($route_id, $time) {
    if ($time === NULL)
      $time = time();

    $route = self::$gtfs->getRoute($route_id);
    $result = array();
    if ($route->lastUpdate) {
      // if we've gone through routeConfig, trips are just
      // different directions on the same route

      $loop_start = NULL;
      foreach ($route->trips as $trip) {
	if (!$loop_start)
	  $loop_start = $trip->nextTripStart($time);
	  
	foreach ($trip->stop_times as $stop_id => $stop_time) {
	  list($arrive, $depart) = $stop_time;
	  $result[$stop_id] = array($arrive + $loop_start);
	}
      }

      asort($result);

    } else {
      // otherwise we have trips that differ by day of week
      // in the future we will change the schedule to 
      // conform more to nextbus
      $earliest = PHP_INT_MAX;
      $trip = NULL;

      foreach ($route->trips as $atrip) {
	$trip_start = $atrip->nextTripStart($time);
	if ($trip_start < $earliest) {
	  $loop_start = $trip_start;
	  $trip = $atrip;
	}
      }

      foreach ($trip->stop_times as $stop_id => $stop_time) {
	list($arrive, $depart) = $stop_time;
	$result[$stop_id] = array($arrive + $loop_start);
      }
    }

    return $result;
  }

  public static function getTimesForStop($stop_id) {
    $stop = self::getStop($stop_id);
    $schedule = self::getScheduledTimesForStop($stop_id);
    $predicted = self::getPredictedTimesForStop($stop_id);
    $time = time();
    $results = array();
    foreach ($schedule as $route_id => $times) {
      $data = array(
	'id' => $stop->id,
        'route_id' => $route_id,
	'lat' => $stop->lat,
	'lon' => $stop->lon,
	'next' => $times[0],
	);

      if (array_key_exists($route_id, $predicted)
	  && $predicted[$route_id])
      {
	$predictions = $predicted[$route_id];
	$data['next'] = $predictions[0];
	foreach (array_slice($predictions, 1) as $aTime) {
	  $data['predictions'][] = $aTime - $time;
	}
	$data['gps'] = TRUE;
      } else {
	$data['gps'] = FALSE;
      }

      $results[] = $data;
    }
    return $results;
  }

  public static function getPredictedTimesForStop($stop_id) {
    $time = time();
    $stop = self::getStop($stop_id);
    $agencies = array(); // though unlikely that multiple agencies share stops
    foreach ($stop->routes as $route_id) {
      $route = self::getRoute($route_id);
      if (array_key_exists($route->agency_id, self::$agencies)) {
      $agency = self::$agencies[$route->agency_id];
      $agencies[$route->agency_id] = $agency;
    }
    }
    $result = array();
    foreach ($agencies as $id => $agency) {
      $predictions = $agency->predictionsForStop($stop_id);
      foreach ($predictions as $route_id => $seconds) {
	$result[$route_id] = $seconds;
      }
    }
    return $result;
  }

  public static function getScheduledTimesForStop($stop_id) {
    $stop = self::getStop($stop_id);
    $time = time();
    $result = array();
    foreach ($stop->routes as $route_id) {
      $route = self::getRoute($route_id);
      if ($route->isInService($time)) {
      foreach ($route->trips as $trip) {
	//if ($trip->isRunningToday($time)) {
	$start = $trip->nextTripStart($time);
	if (array_key_exists($stop_id, $trip->stop_times)) {
	    list($arrive, $depart) = $trip->stop_times[$stop_id];
	    $result[$route_id][] = $start + $arrive;
	}
	//}
      }
    }
    }
    return $result;
  }

  // various getters

  public static function getRouteList($agency_id=NULL) {
    return self::$gtfs->getRouteList($agency_id);
  }

  public static function getActiveRoutes($agency_id=NULL, $time=NULL) {
    return self::$gtfs->getActiveRoutes($agency_id, $time);
  }

  public static function getRunningRoutes($agency_id=NULL, $time=NULL) {
    return self::$gtfs->getRunningRoutes($agency_id, $time);
  }

  public static function getRoute($route_id) {
    $route = self::$gtfs->getRoute($route_id);
    return $route;
  }

  public static function getStop($stop_id) {
    return self::$gtfs->getStop($stop_id);
  }

  public static function setStop($stop_id, ShuttleStop $stop) {
    self::$gtfs->setStop($stop_id, $stop);
  }

  public static function getAgency($agency_id) {
    return self::$agencies[$agency_id];
  }

  public static function getService($service_id) {
    return self::$gtfs->getService($service_id);
  }

  public static function numShuttlesOnTrip($trip_id, $time) {
    $trip = self::getTrip($trip_id);
    return $trip->numShuttlesRunning($time);
  }

  public static function numShuttlesOnRoute($route_id, $time) {
    $route = self::getRoute($route_id);
    $numshuttles = 0;
    foreach ($route->trips as $trip) {
      $numshuttles += $trip->numShuttlesRunning($time);
    }
    return $numshuttles;
  }

  public static function routesForStop($stop_id, $time) {
    $result = array();
    $midnight = day_of(time(), TIMEZONE);
    foreach (self::$trips as $trip) {
      $offset = NULL;

      foreach ($trip->stop_times as $stop_time) {
	if ($stop_time[0] == $stop_id) {
	  $offset = $stop_time[1];
	  break;
	}
      }

      if ($offset !== NULL) {
	$next_start = $trip->nextTripStart($time);
	$next_stop_time = $next_start + $offset;
	$route_id = $trip->route_id;
	$route = self::$gtfs->getRoute($route_id);
	$result[] = array(
          $next_stop_time,
	  array(
            $trip->id,
	    $route->short_name . ' - ' . $route->long_name,
	    $trip->getService()->id,
	    ),
	  TRUE,
	  );
      }
    }

    return $result;
  }

  public static function getBboxStops($north, $east, $south, $west, $limit) {
    $result = array();
    foreach (self::$gtfs->getAllStops as $stop_id => $stop) {
      if ($stop->lon >= $west && $stop->lon < $east
	  && $stop->lat >= $south && $stop->lat < $north) {
	$result[] = array($stop_id, $stop->name, $stop->lat, $stop->lon, 0);
      }
    }
    return $result;
  }

  public static function init() {
    if (!self::$gtfs) {
      self::$gtfs = new GTFSReader(SHUTTLE_GTFS_FEED, 'r');
      self::$gtfs->parse();

      self::$agencies = array(
        'mit' => new NextBusAgency('mit'),
        'saferide' => new NextBusAgency('mit', 'saferide'),
	);

      $saferide = array(
        'saferidebostone', 'saferidebostonw', 'saferidebostonall',
	'saferidecambeast', 'saferidecambwest', 'saferidecamball',
	);

      // the 'mit' agency should catch new routes from nextbus
      self::$agencies['mit']->blackList($saferide);
      self::$agencies['saferide']->whiteList($saferide);

      $time = time();
      foreach (self::$agencies as $agency_id => $nbAgency) {
        $gtfsAgency = self::$gtfs->getAgency($agency_id);
	foreach ($gtfsAgency->routes as $route_id => $route) {
	  if ($route->isInService($time)) {
	    $nbAgency->addRoute($route);
	  }
	}
	foreach ($nbAgency->getAllStops() as $stop_id => $stop) {
	  self::$gtfs->setStop($stop_id, $stop);
	}
      }

      // start writing contents if we can sync up
      // nextbus direction id's with trip id's
      //self::$gtfs->write('routes.txt');
      //self::$gtfs->write('trips.txt');
      //self::$gtfs->write('stop_times.txt');
    }
  }



}

class GTFSReader {

  // file handling
  private $filename;
  private $mode;

  private $errors = array();

  // shuttle object representation
  private $agencies = array();
  private $routes = array();
  private $stops = array();
  private $trips = array();
  private $services = array();
  private $shapes = array();

  public function getRouteList($agency_id=NULL) {
    $routes = array();

    if ($agency_id !== NULL) {
      $agency = $this->getAgency($agency_id);
      return array_keys($agency->routes);
    } else {
      return array_keys($this->routes);
    }
  }

  public function getRunningRoutes($agency_id=NULL, $time=NULL) {
    return $this->filterRoutes($agency_id, $time, 'isRunning');
  }

  public function getActiveRoutes($agency_id=NULL, $time=NULL) {
    return $this->filterRoutes($agency_id, $time, 'isInService');
  }

  private function filterRoutes($agency_id, $time, $userFunc) {
    $routes = array();
    if ($time === NULL) $time = time();

    if ($agency_id !== NULL) {
      $agency = $this->getAgency($agency_id);
      $routeList = $agency->routes;
    } else {
      $routeList = $this->routes;
    }

    foreach ($routeList as $routeId => $route) {
      if ($userFunc == 'isRunning') {
	if ($route->isRunning($time)) {
	  $routes[] = $routeId;
	}
      } else if ($userFunc == 'isInService') {
	if ($route->isInService($time)) {
	  $routes[] = $routeId;
	}
      }
    }

    return $routes;
  }

  public function getAllStops($agency_id=NULL) {
    if ($agency_id === NULL) {
      return $this->stops;
    } else {
      $agency = $this->getAgency($agency_id);
      $stops = array();
      foreach ($agency->routes as $route) {
	foreach ($route->trips as $trip) {
	  foreach ($trip->stop_times as $stop_id => $time) {
	    $stops[$stop_id] = $this->getStop($stop_id);
	  }
	}
      }
      return $stops;
    }
  }

  // this function reads GTFS file and populates data in memory
  public function __construct($filename) {
    $this->filename = $filename;
  }

  // getter and setters

  public function getAgency($id) {
    if (array_key_exists($id, $this->agencies)) {
      return $this->agencies[$id];
    }
    $this->error("agency_id $id not found");
    return FALSE;    
  }

  public function getRoute($id) {
    if (array_key_exists($id, $this->routes)) {
      return $this->routes[$id];
    }
    $this->error("route_id $id not found");
    return FALSE;
  }

  public function getStop($id) {
    if (array_key_exists($id, $this->stops)) {
      return $this->stops[$id];
    }
    $this->error("stop_id $id not found");
    return FALSE;
  }

  public function setStop($id, $stop) {
    $this->stops[$id] = $stop;
  }

  public function getTrip($id) {
    if (array_key_exists($id, $this->trips)) {
      return $this->trips[$id];
    }
    $this->error("trip_id $id not found");
    return FALSE;
  }

  public function getShape($id) {
    if (array_key_exists($id, $this->shapes)) {
      return $this->shapes[$id];
    }
    $this->error("shape_id $id not found");
    return FALSE;
  }

  public function getService($id) {
    if (array_key_exists($id, $this->services)) {
      return $this->services[$id];
    }
    $this->error("service_id $id not found");
    return FALSE;
  }

  private function error($msg) {
    $this->errors[] = $msg;
  }

  // io

  public function dumpErrors() {
    var_dump($this->errors);
  }

  public function write($filename, $headers=NULL) {

    $zip = new ZipArchive();
    
    if ($zip->open($this->filename, ZIPARCHIVE::OVERWRITE) !== TRUE) {
      $this->errMsg = "failed to open zip archive for writing";
    }

    switch ($filename) {
    case 'agency.txt':
      $headers = ShuttleAgency::headers($filename);
      $iterator = $this->agencies;
      break;
    case 'routes.txt':
      $headers = ShuttleRoute::headers($filename);
      $iterator = $this->routes;
      break;
    case 'stops.txt':
      $headers = ShuttleStop::headers($filename);
      $iterator = $this->stops;
      break;
    case 'trips.txt': case 'stop_times.txt': case 'frequencies.txt':
      $headers = ShuttleTrip::headers($filename);
      $iterator = $this->trips;
      break;
    case 'calendar.txt': case 'calendar_dates.txt':
      $headers = ShuttleService::headers($filename);
      $iterator = $this->services;
      break;
    case 'shapes.txt':
      $headers = ShuttleShape::headers($filename);
      $iterator = $this->shapes;
      break;
    default:
      break;
    }

    $csv = new CsvWrapper($filename, $zip, $headers, 'w');
    foreach ($iterator as $id => $object) {
      $rows = $object->csvdump($filename);
      foreach ($rows as $row) {
	$csv->add($row);
      }
    }
    $csv->save();
  }

  public function parse() {
    if ($this->mode == 'w') {
      $this->errMsg = "file opened in writeonly mode";
      return FALSE;
    }

    $zip = new ZipArchive();
    if ($zip->open($this->filename) !== TRUE) {
      $this->errMsg = "failed to open zip archive for reading";
      return FALSE;
    }

    // create agency objects
    $csv = new CsvWrapper('agency.txt', $zip);
    while ($fields = $csv->next()) {
      $agency_id = $fields['agency_id'];
      $agency = new ShuttleAgency($fields);
      $this->agencies[$agency_id] = $agency;
    }

    // create all route objects
    $csv->reset_file('routes.txt');
    while ($fields = $csv->next()) {
      $route_id = $fields['route_id'];
      $agency_id = $fields['agency_id'];
      $route = new ShuttleRoute($fields);
      if ($agency = $this->getAgency($agency_id)) {
        $agency->addRoute($route);
      }
      $this->routes[$route_id] = $route;
    }

    // get attributes of physical stops
    $csv->reset_file('stops.txt');
    while ($fields = $csv->next()) {
      $stop_id = $fields['stop_id'];
      if (array_key_exists($stop_id, $this->stops)) {
	$this->errMsg = "warning: stop_id $stop_id multiply defined";
      }
      $this->stops[$stop_id] = new ShuttleStop($fields);
    }

    // setup service calendar
    $csv->reset_file('calendar.txt');
    while ($fields = $csv->next()) {
      $service_id = $fields['service_id'];
      if (!array_key_exists($service_id, $this->services)) {
	$this->services[$service_id] = new ShuttleService($service_id);
      }
      $this->services[$service_id]->addRange($fields);
    }

    $csv->reset_file('calendar_dates.txt');
    while ($fields = $csv->next()) {
      $service_id = $fields['service_id'];
      if ($service = $this->getService($service_id)) {
	$service->addException($fields);
      }
    }

    // get shapes, if any
    $csv->reset_file('shapes.txt');
    while ($fields = $csv->next()) {
      $shape_id = $fields['shape_id'];
      if (!array_key_exists($shape_id, $this->shapes)) {
	$this->shapes[$shape_id] = new ShuttleShape();
      }
      $this->shapes[$shape_id]->addPoint($fields);
    }

    // create all trip objects; associate route, shape, and service
    $csv->reset_file('trips.txt');
    while ($fields = $csv->next()) {
      $trip_id = $fields['trip_id'];
      $trip = new ShuttleTrip($fields);
      $this->trips[$trip_id] = $trip;

      $route_id = $fields['route_id'];
      if ($route = $this->getRoute($route_id)) {
	$route->addTrip($trip);
      }

      $service_id = $fields['service_id'];
      if ($service = $this->getService($service_id)) {
	$trip->setService($service);
      }

      $shape_id = $fields['shape_id'];
      if ($shape = $this->getShape($shape_id)) {
	$trip->shape = $shape;
      }
    }

    // create stop list for each trips
    $csv->reset_file('stop_times.txt');
    while ($fields = $csv->next()) {
      $trip_id = $fields['trip_id'];
      $stop_id = $fields['stop_id'];
      if ($trip = $this->getTrip($trip_id)) {
	$trip->addStopTime($fields);
      }
      if ($stop = $this->getStop($stop_id)) {
	$stop->addRouteId($trip->route_id);
      }
    }

    // get interval and today's runs
    $csv->reset_file('frequencies.txt');
    while ($fields = $csv->next()) {
      $trip_id = $fields['trip_id'];
      if ($trip = $this->getTrip($trip_id)) {
	$trip->addFrequency($fields);
      }
    }

    $csv->close();
    return TRUE;
  }


}

class CsvWrapper {

  private $fp;
  private $zip;
  private $headers;

  private $tmpFile;
  private $currenFile;

  public function __construct($filename, ZipArchive $zip=NULL, $headers=TRUE, $mode='r') {
    if ($zip !== NULL) {
      $this->zip = $zip;
    } else {
      $this->zip = NULL;
    }

    if ($mode == 'w') {
      $this->new_file($filename, $headers);
    } else {
      $this->reset_file($filename, $headers);
    }
  }

  public function close() {
    if ($this->zip !== NULL) {
      $this->zip->close();
    }
  }

  // write-only functions
  public function new_file($filename, $headers) {
    $this->currentFile = $filename;
    if ($this->zip->locateName($filename)) {
      $this->zip->deleteName($filename);
    }

    $pseudoRandom = date('is');
    $this->tmpFile = "/tmp/$filename.$pseudoRandom";
    $this->fp = fopen($this->tmpFile, 'w');
    if (is_array($headers)) {
      $this->headers = $headers;
      fputcsv($this->fp, $headers);
    }
  }

  public function add($fields) {
    $orderedFields = array();
    if ($this->headers) {
      foreach ($this->headers as $header) {
	$orderedFields[] = $fields[$header];
      }
    } else {
      $orderedFields = $fields;
    }
    fputcsv($this->fp, $orderedFields);
  }

  public function save() {
    $success = FALSE;
    if ($this->fp) {
      fclose($this->fp);
      $success = $this->zip->addFile($this->tmpFile, $this->currentFile);
      unlink($this->tmpFile);
    }
    return $success;
  }

  // read-only functions
  public function next() {
    if (feof($this->fp)) {
      fclose($this->fp);
      return NULL;
    } else {
      if (!$fields = fgetcsv($this->fp)) {
        return NULL;
      }
      $result = array();
      if ($this->headers) {
        foreach ($this->headers as $index => $header) {
          $result[$header] = isset($fields[$index]) ? $fields[$index] : null;
        }
      }
    }
    return $result;
  }

  public function reset_file($filename, $has_headers=TRUE) {
    $this->currentFile = $filename;
    if ($this->zip !== NULL) {
      $this->fp = $this->zip->getStream($filename);
      if ($this->fp === FALSE) {
        error_log(__FUNCTION__.": could not open $filename");
      }
    } else {
      $this->fp = fopen($filename, 'r');
    }
    
    if ($has_headers) {
      $this->headers = fgetcsv($this->fp);
    }
  }

}



