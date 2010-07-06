<?php

/* this file interacts with a GTFS feed based on the schedule
 * published by Parking & Transportation and NextBus' routeConfig
 *
 * php must be compiled with support for Zip files.
 */

require_once "lib_constants.inc";
require_once 'TimeRange.php';
require_once 'datetime_lib.php';

class GTFSReader {

  public static function get_bbox_stops($nbound, $ebound, $sbound, $wbound, $limit) {
    $result = array();
    foreach (self::$stops as $stop_id => $stop) {
      if ($stop->lon >= $wbound && $stop->lon < $ebound
	  && $stop->lat >= $sbound && $stop->lat < $nbound) {
	$result[] = array($stop_id, $stop->name, $stop->lat, $stop->lon, 0);
      }
    }
    return $result;
  }

  public static function get_route($route_id) {
    return self::$routes[$route_id];
  }

  public static function get_stop($stop_id) {
    return self::$stops[$stop_id];
  }

  public static function get_trip($trip_id) {
    return self::$trips[$trip_id];
  }

  public static function get_shape($shape_id) {
    return self::$shapes[$shape_id];
  }

  private static function get_service($service_id) {
    return self::$services[$service_id];
  }

  public static function get_active_routes() {
    $route_list = array_keys(self::$routes);
    return array_filter($route_list, Array('self', 'route_runs_today'));
  }

  // following two functions return true if at least one trip is running

  public static function route_is_running($route_id, $time=NULL) {
    $route = self::get_route($route_id);
    foreach ($route->trips as $trip_id) {
      if (self::trip_is_running($trip_id, $time)) {
	return TRUE;
      }
    }
    return FALSE;
  }

  public static function route_runs_today($route_id, $time=NULL) {
    if ($time === NULL) // get_active_routes will pass a null arg
      $time = time();

    $route = self::get_route($route_id);
    foreach ($route->trips as $trip) {
      if ($trip->service->is_running_today($time)) {
	return TRUE;
      }
    }
    return FALSE;
  }

  public static function trip_is_running($trip_id, $time=NULL) {
    if ($time === NULL)
      $time = time();

    $trip = self::get_trip($trip_id);
    return $trip->is_running($time);
  }

  public static function trip_runs_today($trip_id, $time=NULL) {
    if ($time === NULL)
      $time = time();

    $trip = self::get_trip($trip_id);
    return $trip->service->is_running_today($time);
  }

  public static function count_shuttles_running($route_id, $time=NULL) {
    $route = self::get_route($route_id);
    $numshuttles = 0;
    foreach ($route->trips as $trip) {
      if ($trip->is_running($time)) {
	$numshuttles++;
      }
    }
    return $numshuttles;
  }

  public function is_safe_ride($route_id) {
    return (substr($route_id, 0, 8) == 'saferide');
  }

  public static function get_sms_title($route_id) {
    if (array_key_exists($route_id, self::$smsTitles)) {
      return self::$smsTitle[$route_id];
    }
    $route = self::get_route($route_id);
    return $route->long_name;
  }


  // functions for upcoming schedule

  public static function get_trips_for_stop($stop_id, $time) {
    $result = array();
    $midnight = day_of(time());
    foreach (self::$trips as $trip) {
      $offset = NULL;

      foreach ($trip->stop_times as $stop_time) {
	if ($stop_time[0] == $stop_id) {
	  $offset = $stop_time[1];
	  break;
	}
      }

      if ($offset !== NULL) {
	$next_start = $trip->next_trip_start($time);
	$next_stop_time = $next_start + $offset;
	$route_id = $trip->route_id;
	$route = self::get_route($route_id);
	$result[] = array(
          $next_stop_time,
	  array(
            $trip->id,
	    $route->short_name . ' - ' . $route->long_name,
	    $trip->service->id,
	    ),
	  TRUE,
	  );
      }
    }

    return $result;
  }

  public static function get_next_scheduled_loop($route_id, $time=NULL) {
    if ($time === NULL)
      $time = time();

    $route = self::get_route($route_id);
    $loop_start = $time;
    $trip = self::$trips[0]; // pick a random trip b/c can't declare variables
    foreach ($route->trips as $atrip) {
      $trip_start = $atrip->next_trip_start($time);
      if ($trip_start < $loop_start) {
	$loop_start = $trip_start;
	$trip = $atrip;
      }
    }

    $result = array();
    foreach ($trip->stop_times as $stop_time) {
      // 0: id, 1: arrive, 2: depart
      $result[] = array($stop_time[0], $stop_time[1] + $loop_start);
    }

    return $result;
  }

  ///// parsing logic

  // file handles
  private static $zip;
  private static $csv;

  private static $routes = Array();
  private static $stops = Array();
  private static $trips = Array();
  private static $services = Array();
  private static $shapes = Array();

  // alternate titles for SMS -- haven't found a good place
  // to include this in GTFS
  private static $smsTitles = Array(
    'saferidecamball' => 'Camb All',
    'saferidecambeast' => 'Camb East',
    'saferidecambwest' => 'Camb West',
    'northwest' => 'NW Shuttle',
    );

  // this is a strange function to have but schedule_viewer does it
  public static function trip_rows($trip_id) {
    $trip_arr = array('trips.txt');
    self::$csv->reset_file('trips.txt');
    while ($fields = self::$csv->next()) {
      if ($fields['trip_id'] == $trip_id) {
	$route_id = $fields['route_id'];
	$trip_arr[] = $fields;
	break;
      }
    }

    $route_arr = array('routes.txt');
    self::$csv->reset_file('routes.txt');
    while ($fields = self::$csv->next()) {
      if ($fields['route_id'] == $route_id) {
	$route_arr[] = $fields;
	break;
      }
    }

    return array($trip_arr, $route_arr);
  }

  // this function reads GTFS file and populates data in memory
  // (without ever closing the zip file actually...)
  public static function init($gtfs_feed_location) {
    if (!self::$routes) {
      self::$zip = new ZipArchive;
      if (self::$zip->open($gtfs_feed_location) !== TRUE) {
	error_log("failed to open zip archive");
	return;
      }

      // create all route objects
      self::$csv = new CsvWrapper('routes.txt', self::$zip);
      while ($fields = self::$csv->next()) {
	$route_id = $fields['route_id'];
	$route = new ShuttleRoute($fields);
	self::$routes[$route_id] = $route;
      }

      // create all trip and service objects; associate routes with trips
      self::$csv->reset_file('trips.txt');
      while ($fields = self::$csv->next()) {
	$trip_id = $fields['trip_id'];
	$trip = new ShuttleTrip($fields);
	self::$trips[$trip_id] = $trip;

	$route_id = $fields['route_id'];
	self::$routes[$route_id]->add_trip($trip);

	$service_id = $fields['service_id'];
	if (!array_key_exists($service_id, self::$services)) {
	  self::$services[$service_id] = new ShuttleService();
	}
	$service = self::$services[$service_id];
	$service->id = $service_id;
	$trip->service = $service;

	$shape_id = $fields['shape_id'];
	if (!array_key_exists($shape_id, self::$shapes)) {
	  self::$shapes[$shape_id] = new ShuttleShape();
	}
	$trip->shape = self::$shapes[$shape_id];
      }

      // create stop list for each trips
      self::$csv->reset_file('stop_times.txt');
      while ($fields = self::$csv->next()) {
	$trip_id = $fields['trip_id'];
	self::$trips[$trip_id]->add_stop_time($fields);
      }

      // setup service list
      self::$csv->reset_file('calendar.txt');
      while ($fields = self::$csv->next()) {
	$service_id = $fields['service_id'];
	$service = self::$services[$service_id];
	$service->add_range($fields);
      }

      self::$csv->reset_file('calendar_dates.txt');
      while ($fields = self::$csv->next()) {
	$service_id = $fields['service_id'];
	$service = self::$services[$service_id];
	$service->add_exception($fields);
      }

      // get interval and today's runs
      self::$csv->reset_file('frequencies.txt');
      while ($fields = self::$csv->next()) {
	$trip_id = $fields['trip_id'];
	self::$trips[$trip_id]->add_frequency($fields);
      }

      // get attributes of physical stops
      self::$csv->reset_file('stops.txt');
      while ($fields = self::$csv->next()) {
	self::$stops[$fields['stop_id']] = new ShuttleStop($fields);
      }

      // get shapes
      self::$csv->reset_file('shapes.txt');
      while ($fields = self::$csv->next()) {
	$shape_id = $fields['shape_id'];
	self::$shapes[$shape_id]->add_point($fields);
      }
      
    }
  }

}

class ShuttleRoute {
  public $short_name;
  public $long_name;
  public $desc;
  public $type;
  public $color;
  public $trips = array();

  public function __construct($csvdata) {
    foreach ($csvdata as $key => $value) {
      $local_varname = str_replace('route_', '', $key);
      $this->{$local_varname} = $value;
    }
  }

  public function add_trip($trip) {
    $this->trips[] = $trip;
  }
}

class ShuttleStop {
  public $code;
  public $name;
  public $desc;
  public $lat;
  public $lon;

  public function __construct($csvdata) {
    foreach ($csvdata as $key => $value) {
      $local_varname = str_replace('stop_', '', $key);
      if ($local_varname == 'lat' || $local_varname == 'lon') {
	$this->{$local_varname} = (double)$value;
      } else {
	$this->{$local_varname} = $value;
      }
    }
  }
}

class ShuttleTrip {
  public $id;
  public $route_id; // this might not be needed
  public $services;
  public $shape;
  public $stop_times = array();
  public $frequencies = array();

  // keeps track of whether $stop_times has absolute times.  we only want
  // relative times since we use frequencies to describe recurring loops.
  private $stops_processed = FALSE;

  public function is_running($time) {
    if (!$this->service->is_running_today($time)) {
      return FALSE;
    }

    $time -= day_of($time);

    foreach ($this->frequenceis as $freq) {
      // 0: start time; 1: end time
      if ($freq[0] <= $time && $freq[1] > $time) {
	return TRUE;
      }
    }
  }

  public function next_trip_start($time) {
    if ($time > 100000000) { // is unixtime
      $midnight = day_of($time);
      $time -= $midnight;
    } else {
      $midnight = 0;
    }

    $default = NULL;

    // requires that no time spans in frequencies overlap one another
    foreach ($this->frequencies as $freq) {
      $start_time = $freq[0];
      $end_time = $freq[1];
      $result = $start_time;
      if ($end_time > $time) {
	$interval = $freq[2];
	while ($result < $time) {
	  $result += $interval;
	}
      }

      if ($default === NULL || $result < $default) {
	$default = $result;
      }
    }
    return $default + $midnight;
  }

  public function __construct($csvdata) {
    $this->id = $csvdata['trip_id'];
    $this->route_id = $csvdata['route_id'];
  }

  public function add_frequency($csvdata) {
    $start_time = $csvdata['start_time'];
    $end_time = $csvdata['end_time'];
    $headway_secs = $csvdata['headway_secs'];
    $this->frequencies[] = array(
      $this->timetoseconds($start_time),
      $this->timetoseconds($end_time),
      (int)$headway_secs
      );

    // TODO: need a better place to call this
    $this->process_stops();
  }

  // after all stops are loaded then we can replace the times with offsets
  public function process_stops() {
    if (!$this->stops_processed && count($this->frequencies) && count($this->stop_times)) {
      $start_time = $this->stop_times[0][1];
      foreach ($this->stop_times as $index => $stop_time) {
	// 1 is arrival position, 2 is departure position
	$this->stop_times[$index][1] = $stop_time[1] - $start_time;
	$this->stop_times[$index][2] = $stop_time[2] - $start_time;
      }
      $this->stops_processed = TRUE;
    }
  }

  public function add_stop_time($csvdata) {
    $sequence = (int)$csvdata['stop_sequence'];
    $arrive = $csvdata['arrival_time'];
    $depart = $csvdata['departure_time'];
    $stop_id = $csvdata['stop_id'];
    $this->add_stop_time_data($stop_id, $arrive, $depart, $sequence);
  }

  private function add_stop_time_data($stop_id, $arrive, $depart, $sequence) {
    if ($sequence >= count($this->stop_times)) {
      array_pad($this->stop_times, $sequence, array());
    }
    $this->stop_times[$sequence] = array(
      $stop_id,
      $this->timetoseconds($arrive),
      $this->timetoseconds($depart),
      );
  }

  private function timetoseconds($timestring) {
    $parts = explode(':', $timestring);
    return 3600 * (int)$parts[0] + 60 * (int)$parts[1] + (int)$parts[2];
  }
}

class ShuttleShape {

  public $points = array();

  public function add_point($csvdata) {
    $distance = NULL;
    if (array_key_exists('shape_dist_traveled', $csvdata)) {
      $distance = $csvdata['shape_dist_traveled'];
    }
    $this->add_point_data(
      (double)$csvdata['shape_pt_lat'],
      (double)$csvdata['shape_pt_lon'],
      (int)$csvdata['shape_pt_sequence'],
      $distance
      );
  }

  private function add_point_data($lat, $lon, $sequence, $distance=NULL) {
    if ($sequence >= count($this->points)) {
      array_pad($this->points, $sequence, array());
    }

    $this->points[$sequence] = ($distance === NULL)
      ? array($lat, $lon) 
      : array($lat, $lon, $distance);
  }
}

// helper class for ShuttleService
class ServiceRange {
  public $start;
  public $end;
  public $week = array();

  public function __construct($csvdata) {
    $this->start = (int)$csvdata['start_date'];
    $this->end = (int)$csvdata['end_date'];
    $this->week = array_pad($this->week, 7, 0);
    $this->week[0] = (int)$csvdata['sunday'];
    $this->week[1] = (int)$csvdata['monday'];
    $this->week[2] = (int)$csvdata['tuesday'];
    $this->week[3] = (int)$csvdata['wednesday'];
    $this->week[4] = (int)$csvdata['thursday'];
    $this->week[5] = (int)$csvdata['friday'];
    $this->week[6] = (int)$csvdata['saturday'];
  }
}

class ShuttleService {

  public $id;
  public $ranges = array();
  public $on_days = array();
  public $off_days = array();

  public function add_exception($csvdata) {
    if ($csvdata['exception_type'] == '1') {
      $this->on_days[] = (int)$csvdata['date'];
    } else {
      $this->off_days[] = (int)$csvdata['date'];
    }
  }

  public function add_range($csvdata) {
    $this->ranges[] = new ServiceRange($csvdata);
  }

  public function is_running_today($time) {
    $date = (int)date('Ymd', $time);
    if (in_array($date, $this->on_days)) {
      return TRUE;
    }
    if (in_array($date, $this->off_days)) {
      return FALSE;
    }

    $dayofweek = (int)date('w', $time);
    foreach ($this->ranges as $range) {
      if ($date < $range->start || $date > $range->end) {
	continue;
      }
      return ($range->week[$dayofweek] == 1);
    }
  }

  public function next_start_date($time) {
    $date = date('Ymd', $time);
    $start = FALSE;
    foreach ($this->ranges as $range) {
      if ($start === FALSE
	  || ($range->start >= $date && $range->start < $start)) {
	$start = $range->start;
      }
    }

    return $start;

  }
}

class CsvWrapper {

  private $fp;
  private $zip;
  private $headers;

  public function __construct($filename, ZipArchive $zip=NULL, $has_headers=TRUE) {
    if ($zip !== NULL) {
      $this->zip = $zip;
    } else {
      $this->zip = NULL;
    }

    $this->reset_file($filename, $has_headers);
  }

  public function next() {
    if (feof($this->fp)) {
      fclose($this->fp);
      return NULL;
    } else {
      if (!$fields = fgetcsv($this->fp)) {
	return NULL;
      }
      $result = array();
      foreach ($this->headers as $index => $header) {
	$result[$header] = $fields[$index];
      }
    }
    return $result;
  }

  public function reset_file($filename, $has_headers=TRUE) {

    if ($this->zip !== NULL) {
      $this->fp = $this->zip->getStream($filename);
      if (!$this->fp) {
	error_log("could not open $filename");
      }
    } else {
      $this->fp = fopen($filename, 'r');
    }

    if ($has_headers) {
      $this->headers = fgetcsv($this->fp);
    }
  }

}

?>
