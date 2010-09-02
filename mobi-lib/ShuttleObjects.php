<?php

/* The classes in this file represent everything
 * in GTFS that has a unique ID within the dataset:
 * route, trip, stop, shape, service.
 */

abstract class ShuttleObject {
  public $id;
  
  public static function headers($filename) {
    // return csv headers in gtfs
  }

  public function csvdump($filename) {
  }
}

class ShuttleAgency extends ShuttleObject {
  public $name;
  public $url;
  public $timezone;
  public $lang;
  public $phone;

  public $routes = array(); // of ShuttleRoute objects

  public function addRoute(ShuttleRoute $route) {
    $this->routes[$route->id] = $route;
  }

  public function __construct($csvdata) {
    foreach ($csvdata as $key => $value) {
      $localname = str_replace('agency_', '', $key);
      $this->{$localname} = $value;
    }
  }

  public static function headers() {
    return array(
      'agency_id', 'agency_name', 'agency_url',
      'agency_timezone', 'agency_lang', 'agency_phone',
      );
  }

  public function csvdump($filename) {
    $result = array(
      'agency_id' => $this->id,
      'agency_name' => $this->name,
      'agency_url' => $this->url,
      'agency_timezone' => $this->timezone,
      'agency_lang' => $this->lang,
      'agency_phone' => $this->phone,
      );

    return array($result);

  }
}

class ShuttleRoute extends ShuttleObject {
  // "id" is "route_id" in feed

  public $agency_id = ''; 
  public $short_name;     // required
  public $long_name;      // required
  public $type;           // required; "3" for bus
  public $desc = '';
  public $color = '';
  public $url = '';

  public $trips = array(); // of ShuttleTrip objects
  public $stops = array(); // of stop ID's

  // last time all the data associated with this route
  // (trips, stops on trips, trip paths) was updated
  public $lastUpdate = 0; 

  public function isInService($time) {
    foreach ($this->trips as $trip_id => $trip) {
      if ($trip->isInService($time))
	return TRUE;
    }
    return FALSE;
  }

  public function isRunning($time=NULL) {
    foreach ($this->trips as $trip_id => $trip) {
      if ($trip->isRunning($time))
	return TRUE;
    }
    return FALSE;
  }

  public function anyTrip($time) {
    foreach ($this->trips as $trip_id => $trip) {
      if ($trip->isRunning($time)) {
	return $trip;
      }
    }

    // nothing is running right now, get soonest
    $earliest = PHP_INT_MAX;
    $earliestTrip = NULL;
    foreach ($this->trips as $trip_id => $trip) {
      $maybeEarlier = $trip->nextTripStart($time);
      if ($maybeEarlier < $earliest) {
	$earliestTrip = $trip;
	$earliest = $maybeEarlier;
      }
    }
    return $earliestTrip;
  }

  // getters and setters

  public function addTrip(ShuttleTrip $trip) {
    $this->trips[$trip->id] = $trip;
  }

  public function getTrips() {
    return $this->trips;
  }

  // ShuttleObject

  public static function headers($filename) {
    return array(
      'route_id', 'agency_id', 'route_short_name', 'route_long_name',
      'route_type', 'route_desc', 'route_color', 'route_url',
      );
  }

  public function __construct($csvdata) {
    foreach ($csvdata as $key => $value) {
      $localname = str_replace('route_', '', $key);
      $this->{$localname} = $value;
    }
  }

  public function csvdump($filename) {
    $result = array(
      'route_id' => $this->id,
      'agency_id' => $this->agency_id,
      'route_short_name' => $this->short_name,
      'route_long_name' => $this->long_name,
      'route_type' => $this->type,
      'route_desc' => $this->desc,
      'route_color' => $this->color,
      'route_url' => $this->url,
      );

    return array($result);
  }
}

class ShuttleStop extends ShuttleObject {
  public $code = '';
  public $name;      // required
  public $desc = '';
  public $lat;       // required
  public $lon;       // required
  public $url = '';

  public $routes = array(); // of route ID's

  // getters and setters

  public function addRouteId($route_id) {
    if (!in_array($route_id, $this->routes))
      $this->routes[] = $route_id;
  }

  // ShuttleObject

  public static function headers($filename) {
    return array(
      'stop_id', 'stop_code', 'stop_name',
      'stop_desc', 'stop_lat', 'stop_lon', 'stop_url'
      );
  }

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

  public function csvdump($filename) {
    $result = array(
      'stop_id' => $this->id,
      'stop_name' => $this->name,
      'stop_lat' => $this->lat,
      'stop_lon' => $this->lon,
      'stop_code' => $this->code,
      'stop_desc' => $this->desc,
      'stop_url' => $this->url,
      );

    return array($result);
  }
}

class ShuttleTrip extends ShuttleObject {
  public $headsign;
  public $route_id;     // required
  public $service_id;   // required
  public $direction_id = '';
  public $shape_id = '';

  private $service; // ShuttleService object
  // { "stop_id": [ arrival_time, departure_time ],
  //   "another_stop_id": [ ... ], ...}
  public $stop_times = array();
  // [[ start_time, end_time, headway_secs],
  //  [ another_start_time, another_end_time, another_headway_secs]]
  public $frequencies = array();

  public function numShuttlesRunning($time) {
    if ($time === NULL) $time = time();
    if (!$this->isRunningToday($time)) {
      return 0;
    }

    $time -= day_of($time, TIMEZONE);
    // if it's before 5am, pretend it's the day before
    if ($time < 3600 * 5) $time += 86400;
    $frequency = NULL;
    foreach ($this->frequencies as $freq) {
      list($start, $end, $interval) = $freq;
      if ($start <= $time && $end > $time) {
	return intval($this->duration() / $interval);
      }
    }
    return 0;
  }

  // no data source supports this now,
  // need to think about how to get this.
  public function duration() {
    switch ($this->route_id) {
    case 'tech': return 20 * 60;
    case 'saferidebostone': return 20 * 60;
    case 'saferidebostonw': return 30 * 60;
    case 'saferidebostonall': return 60 * 60;
    case 'saferidecamball': return 60 * 60;
    default: return 30 * 60;
    }
  }

  public function isInService($time) {
    return ($this->service->isInService($time));
  }

  public function isRunning($time) {
    return $this->numShuttlesRunning($time) > 0;
  }

  public function isRunningToday($time) {
    return $this->service->isRunningToday($time);
  }

  public function nextTripStart($time) {
    $targetTime = $time;

    if ($targetTime > 100000000) { // is unixtime
      $midnight = day_of($time, TIMEZONE);
      $targetTime -= $midnight;
      if (!$this->isRunningToday($time)) {
	$midnight = $this->service->nextStartDate($time);
      }
    } else {
      $midnight = 0;
    }

    $secsPastMidnight = NULL;

    foreach ($this->frequencies as $freq) {
      list($start, $end, $interval) = $freq;
      $earliest = $start;
      if ($end > $targetTime) {
	while ($earliest < $targetTime) {
	  $earliest += $interval;
	}
      }

      if ($secsPastMidnight === NULL || $earliest < $secsPastMidnight) {
	$secsPastMidnight = $earliest;
      }
    }
    return $secsPastMidnight + $midnight;
  }

  // getters and setters

  public function getService() {
    return $this->service;
  }

  public function setService(ShuttleService $service) {
    $this->service = $service;
    $this->service_id = $service->id;
  }

  public function addStop(ShuttleStop $stop) {
    if (!array_key_exists($stop->id, $this->stop_times)) {
      $this->stop_times[$stop->id] = array(0, 0);
    }
  }

  public function addStopTime($csvdata) {
    $stop_id = $csvdata['stop_id'];
    $arrive = self::timetoseconds($csvdata['arrival_time']);
    $depart = self::timetoseconds($csvdata['departure_time']);
    $this->stop_times[$stop_id] = array($arrive, $depart);
  }

  public function addFrequency($csvdata) {
    $start = self::timetoseconds($csvdata['start_time']);
    $end = self::timetoseconds($csvdata['end_time']);
    $headway_secs = intval($csvdata['headway_secs']);

    $this->frequencies[] = array($start, $end, $headway_secs);
  }

  private static function timetoseconds($timestring) {
    $parts = explode(':', $timestring);
    return 3600 * (int)$parts[0] + 60 * (int)$parts[1] + (int)$parts[2];
  }

  private static function secondstotime($seconds) {
    $second = $seconds % 60;
    $seconds = ($seconds - $second) / 60;
    $minute = $seconds % 60;
    $hour = ($seconds - $minute) / 60;
    return $hour . ':'
      . str_pad($minute, 2, '0', STR_PAD_LEFT) . ':'
      . str_pad($second, 2, '0', STR_PAD_LEFT);
  }

  // ShuttleObject

  public function __construct($csvdata) {
    foreach ($csvdata as $key => $value) {
      $localname = str_replace('trip_', '', $key);
      $this->{$localname} = $value;
    }
  }

  public static function headers($filename) {
    switch ($filename) {
    case 'trips.txt':
      return array(
        'trip_id', 'trip_headsign', 'route_id',
        'direction_id', 'shape_id'
	);
    case 'frequencies.txt':
      return array('trip_id', 'start_time', 'end_time', 'headway_secs');
    case 'stop_times.txt':
      return array(
        'trip_id', 'stop_id', 'arrival_time',
        'departure_time', 'stop_sequence'
	);
    default:
      return array();
    }
  }

  public function csvdump($filename) {
    $result = array();
    switch ($filename) {
    case 'trips.txt':
      $result[] = array(
        'trip_id' => $this->id,
	'route_id' => $this->route_id,
	'service_id' => $this->service_id,
	'trip_headsign' => $this->headsign,
	'direction_id' => $this->direction_id,
	'shape_id' => $this->shape_id,
	);
      break;
    case 'stop_times.txt':
      $sequence = 0;
      foreach ($this->stop_times as $stop_id => $times) {
	$result[] = array(
          'trip_id' => $this->id,
	  'stop_id' => $stop_id,
	  'arrival_time' => $times[0],
	  'departure_time' => $times[1],
	  'stop_sequence' => $sequence,
	  );
	$sequence++;
      }
      break;
    case 'frequencies.txt':
      foreach ($this->frequencies as $frequency) {
	$result[] = array(
          'trip_id' => $this->id,
	  'start_time' => self::secondstotime($frequency[0]),
	  'end_time' => self::secondstotime($frequency[1]),
	  'headway_secs' => $frequency[2],
	  );
      }
      break;
    default:
      break;
    }

    return $result;
  }
}

class ShuttleShape extends ShuttleObject {

  public $points = array(); // [lat, lon]

  // getters and setters

  public function addPoint($csvdata) {
    $lat = floatval($csvdata['shape_pt_lat']);
    $lon = floatval($csvdata['shape_pt_lon']);

    $this->points[] = array($lat, $lon);
  }

  // ShuttleObject

  public static function headers($filename) {
    return array(
      'shape_id', 'shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence'
      );
  }

  public function dumpcsv() {
    $result = array();
    foreach ($this->points as $index => $point) {
      list($lat, $lon) = $point;
      $result[] = array(
        'shape_id' => $this->id,
        'shape_pt_lat' => $lat,
	'shape_pt_lon' => $lon,
	'shape_pt_sequence' => $index,
	);
    }
  }

}

class ShuttleService extends ShuttleObject {

  public $id;
  public $ranges = array();   // of ServiceRange objects
  public $on_days = array();  // [ 20100101, 20100304, ... ]
  public $off_days = array(); // [ 20100101, 20100304, ... ]

  public function isInService($time) {
    $date = intval(date('Ymd', $time));
    foreach ($this->ranges as $range) {
      if ($date >= $range->start && $date <= $range->end) {
	return TRUE;
      }
    }
    return FALSE;
  }

  public function isRunningToday($time) {
    $date = intval(date('Ymd', $time));
    if (in_array($date, $this->on_days)) {
      return TRUE;
    }
    if (in_array($date, $this->off_days)) {
      return FALSE;
    }

    $dayofweek = intval(date('w', $time));
    foreach ($this->ranges as $range) {
      if ($date < $range->start || $date > $range->end) {
	continue;
      }
      return ($range->week[$dayofweek] == 1);
    }
  }

  public function nextStartDate($time) {
    if ($this->isInService($time)) {
      while (!$this->isRunningToday($time)) {
	$time += 86400;
      }
      return day_of($time, TIMEZONE);
    }

    $date = intval(date('Ymd', $time));
    $start = FALSE;
    foreach ($this->ranges as $range) {
      if ($start === FALSE
	  || ($range->start >= $date && $range->start < $start)) {
	$start = $range->start;
      }
    }

    if ($start !== FALSE) {
      $datestr = strval($start);
      $start = mktime(0, 0, 0,
		      substr($datestr, 0, 4),
		      substr($datestr, 4, 2),
		      substr($datestr, 6, 2));
      $start = day_of($start, TIMEZONE);
    }

    return $start;
  }

  // getters and setters

  public function addException($csvdata) {
    if ($csvdata['exception_type'] == '1') {
      $this->on_days[] = intval($csvdata['date']);
    } else {
      $this->off_days[] = intval($csvdata['date']);
    }
  }

  public function addRange($csvdata) {
    $this->ranges[] = new ServiceRange($csvdata);
  }

  // ShuttleObject

  public static function headers($filename) {
    if ($filename == 'calendar.txt') {
      return array(
        'service_id', 'monday', 'tuesday', 'wednesday', 'thursday',
	'friday', 'saturday', 'sunday', 'start_date', 'end_date',
	);
    } elseif ($filename == 'calendar_dates.txt') {
      return array('service_id', 'date', 'exception_type');
    }
  }

  public function csvdump($filename) {
    $result = array();
    if ($filename == 'calendar.txt') {
      foreach ($this->ranges as $range) {
        $result[] = array(
          'service_id' => $this->id,
	  'monday' => $range->week[1],
	  'tuesday' => $range->week[2],
	  'wednesday' => $range->week[3],
	  'thursday' => $range->week[4],
	  'friday' => $range->week[5],
	  'saturday' => $range->week[6],
	  'sunday' => $range->week[0],
	  'start_date' => $range->start,
	  'end_date' => $range->end,
	  );
      }
    } else if ($filename == 'calendar_dates.txt') {
      foreach ($this->on_days as $day) {
        $result[] = array(
          'service_id' => $this->id,
	  'date' => $day,
	  'exception_type' => 1
	  );
      }
      foreach ($this->off_days as $day) {
	$result[] = array(
          'service_id' => $this->id,
	  'date' => $day,
	  'exception_type' => 2
	  );
      }
    }
    return $result;
  }

  public function __construct($id) {
    $this->id = $id;
  }
}

// helper class for ShuttleService
class ServiceRange {
  public $start;
  public $end;
  public $week = array();

  public function __construct($csvdata) {
    $this->start = intval($csvdata['start_date']);
    $this->end = intval($csvdata['end_date']);
    $this->week = array_pad($this->week, 7, 0);
    $this->week[0] = intval($csvdata['sunday']);
    $this->week[1] = intval($csvdata['monday']);
    $this->week[2] = intval($csvdata['tuesday']);
    $this->week[3] = intval($csvdata['wednesday']);
    $this->week[4] = intval($csvdata['thursday']);
    $this->week[5] = intval($csvdata['friday']);
    $this->week[6] = intval($csvdata['saturday']);
  }
}