<?php

/* this file interacts with nextbus' xml feed */

require_once "lib_constants.inc";
require_once "DiskCache.inc";
require_once("ShuttleSchedule.php");

NextBusReader::init();

class NextBusReader {
  private static $query_params = Array();
  private static $routeCache = Array(); // results of routeConfig command
  private static $predictionCache = Array(); // results of predictions command
  private static $vehicleCache = Array(); // results of vehicleLocatiosn command
  private static $unmodifiedRouteList = Array(); // results of routeList command

  private static $routeCachePrefix = 'ROUTE_';
  private static $predictionCachePrefix = 'PREDICTION_';
  private static $stopsCacheFile = 'STOPS';
  private static $vehicleCacheFile = 'vechicle_locations';

  private static $routeDiskCache;
  private static $predDiskCache;
  private static $vehicleDiskCache;
  private static $stopDiskCache;

  /* private query methods */

  private static function reset_query() {
    self::$query_params = Array('a' => NEXTBUS_AGENCY);
  }

  private static function set_command($command) {
    self::$query_params['command'] = $command;
  }

  private static function set_query_route($route) {
    self::$query_params['r'] = $route;
  }

  private static function set_other_params(Array $params) {
    foreach ($params as $param => $value) {
      self::$query_params[$param] = $value;
    }
  }

  private static function query() {
    $url = NEXTBUS_FEED_URL . http_build_query(self::$query_params);
    // replace q[0]=foo&q[1]=bar with q=foo&q=bar
    $url = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $url);
    // suppress warnings
    $error_reporting = intval(ini_get('error_reporting'));
    error_reporting($error_reporting & ~E_WARNING);
      $xml = file_get_contents($url);
    error_reporting($error_reporting);
    self::reset_query();

    if ($xml) {
      $xml_obj = new DOMDocument();
      $xml_obj->loadXML($xml);
      $errors = FALSE;
      // errors usually appear when nextbus is initializing our routes
      foreach ($xml_obj->getElementsByTagName('Error') as $error) {
	$errors = TRUE;
	error_log('NextBus returned XML: ' . $error->nodeValue);
      }
      if (!$errors)
	return $xml_obj;
    }
    error_log('Could not fetch XML from NextBus');
    return FALSE;
  }

  /* private route methods */

  private static function stop_id_from_title($routeName, $title) {
    $routeInfo = self::$routeCache[$routeName];
    foreach ($routeInfo as $stop => $attribs) {
      if ($attribs['title'] == $title) {
	return $stop;
      }
    }
  }

  private static function is_nextbus_route($routeName) {
    if (!in_array($routeName, self::$unmodifiedRouteList)) {
      return FALSE;
    }
    return TRUE;
  }

  /* private cache wrapper methods */

  private static function get_prediction_age($routeName) {
    if (self::$predictionCache[$routeName]
	&& array_key_exists('updated', self::$predictionCache[$routeName])) {
      return (time() - self::$predictionCache[$routeName]['updated']);
    } else {
      return self::$predDiskCache->getAge($routeName);
    }
  }

  private static function prediction_is_fresh($routeName) {
    $age = self::get_prediction_age($routeName);
    return ($age <= NEXTBUS_PREDICTION_CACHE_TIMEOUT);
  }

  /* public top-level methods */

  public static function init() {

    self::$routeDiskCache = new DiskCache(NEXTBUS_CACHE, NEXTBUS_ROUTE_CACHE_TIMEOUT, TRUE);
    self::$routeDiskCache->setPrefix('route_');
    self::$predDiskCache = new DiskCache(NEXTBUS_CACHE, NULL, TRUE);
    self::$predDiskCache->setPrefix('prediction_');
    self::$vehicleDiskCache = new DiskCache(NEXTBUS_CACHE . '/' . self::$vehicleCacheFile);
    self::$stopDiskCache = new DiskCache(NEXTBUS_CACHE . '/' . self::$stopsCacheFile, NEXTBUS_ROUTE_CACHE_TIMEOUT);
    self::reset_query();
    if (!self::$routeCache) {
      ShuttleSchedule::init();
      // this is part of a hack to correct 
      // saferide route names that nextbus gets wrong
      $combined_routes = Array(
        'saferidebostone' => 'saferidebostonall',
	'saferidebostonw' => 'saferidebostonall',
	'saferidecambeast' => 'saferidecamball',
	'saferidecambwest' => 'saferidecamball',
	);

      // query nextbus to see what routes are available
      self::set_command('routeList');
      $xml = self::query();
      if ($xml) {
	self::$unmodifiedRouteList = Array();

	foreach ($xml->getElementsByTagName('route') as $route) {
	  $routeName = $route->getAttribute('tag');
	  self::$unmodifiedRouteList[] = $routeName;

	  // if nextbus' route list is not consistent with the published schedule
	  // use the published route list

	  if (!ShuttleSchedule::is_running_today($routeName)
	      && array_key_exists($routeName, $combined_routes)
              && ShuttleSchedule::is_running_today($combined_routes[$routeName])) {
	    $routeName = $combined_routes[$routeName];
	  }

	  if (!self::$routeCache[$routeName] = self::$routeDiskCache->read($routeName)) {
	    self::$routeCache[$routeName] = Array();
	  }
	}

      } else {
	// query failed; get {routeName}s from cached filenames
        $prefix = self::$routeDiskCache->getPrefix();
	foreach (scandir(NEXTBUS_CACHE) as $filename) {
	  if (strpos($filename, $prefix) == 0) {
	    $routeName = substr($filename, 0, $prefix);
	    self::$routeCache[$routeName] = self::$routeDiskCache->read($routeName);
	  }
	}
      }

    }
  }

  public static function get_route_list() {
    return array_keys(self::$routeCache);
  }

  public static function get_all_stops() {
    if (self::$stopDiskCache->isFresh())
      return self::$stopDiskCache->read(self::$stopsCacheFile);

    $stops = Array();
    foreach (self::$routeCache as $route => $routeInfo) {
      if (!$routeInfo) {
	$routeInfo = self::get_route_info($route);
      }

      foreach ($routeInfo as $stop => $stopInfo) {
	$stops[$stop]['title'] = $stopInfo['title'];
	$stops[$stop]['lon'] = $stopInfo['lon'];
	$stops[$stop]['lat'] = $stopInfo['lat'];
	$stops[$stop]['id'] = $stop;
	if (!array_key_exists('routes', $stops[$stop]))
	  $stops[$stop]['routes'] = Array();
	$stops[$stop]['routes'][] = $route;
      }
    }

    self::$stopDiskCache->write($stops);

    return $stops;
  }

  /* public route methods */

  public static function get_last_refreshed($routeName) {
    return time() - self::get_prediction_age($routeName);
  }

  public static function get_route_info($routeName) {
    if (!self::$routeDiskCache->exists(self::$routeCachePrefix . $routeName)
        && !self::is_nextbus_route($routeName)) {
      // we have nothing cached and NextBus doesn't know about $routeName
      return FALSE;
    }

    if (!self::$routeCache[$routeName]) {
      self::$routeCache[$routeName] = self::$routeDiskCache->read($routeName);
    }

    if (self::$routeDiskCache->isFresh($routeName)
        || !self::is_nextbus_route($routeName)) {
      return self::$routeCache[$routeName];
    }

    self::set_command('routeConfig');
    self::set_query_route($routeName);
    $xml = self::query();
    if (!$xml) { // either we got errors or couldn't read the url
      self::$routeCache[$routeName] = self::$routeDiskCache->read($routeName);
    } else {
      $routeInfo = Array();

      // get list of stops and latlon coordinates
      foreach ($xml->getElementsByTagName('stop') as $stop) {
	if ($stop->hasAttribute('title')) {
	  $routeInfo[$stop->getAttribute('tag')] = Array(
	    'title' => $stop->getAttribute('title'),
	    'lat' => $stop->getAttribute('lat'),
	    'lon' => $stop->getAttribute('lon'),
	    'direction' => $stop->getAttribute('dirTag'),
	    );
	}
      }
      // get list of path coordinates
      $stop_order = array_keys($routeInfo); // use this to check position
      $num_stops = count($stop_order);
      foreach ($xml->getElementsByTagName('path') as $path) {
	foreach ($path->getElementsByTagName('tag') as $tag) {
	  // tag names look like tech_w92ames_simmhl
	  // where "tech" is the shuttle id
	  // w92ames and simmhl are stop id's
	  $idparts = explode('_', $tag->getAttribute('id'));
	  $from_stop = $idparts[1];
	  // for end stops like mass84_d, kendsq_d etc. there isn't a real path
	  if ($idparts[2] == 'd')
	    continue;

	  $to_stop = $idparts[2];

	  // for ending stops like beacmass_a, massbeac_b
	  if (count($idparts) > 3)
	    $to_stop .= '_' . $idparts[3];

	  // figure out whether the stops in the tag name are consecutive
	  // since there could be massbeac_massnewb and massbeac_beac528
	  // but one of those refers to massbeac_b and we can't tell which
	  // unless we look at the stop order
	  $from_pos = array_search($from_stop, $stop_order);
	  $to_pos = array_search($to_stop, $stop_order);
	  if ($to_pos - $from_pos != 1 && $to_pos - $from_pos + $num_stops != 1) {
	    $from_stop_candidates = Array();
	    $to_stop_candidates = Array();

	    // nextbus is really inconsistent in naming stops and paths
	    foreach ($stop_order as $index => $stop_name) {
	      // e.g. "randhl" in path is "rand"
	      if (strpos($stop_name, $to_stop) !== FALSE)
		$to_stop_candidates[] = $index;
	      if (strpos($stop_name, $from_stop) !== FALSE)
		$from_stop_candidates[] = $index;

	      // "comm487" in path is "487comm", grrrrrRRR!
	      preg_match('/((\d+)(\w+)|(\w+)(\d+))/', $stop_name, $matches);
	      if ($matches[3] . $matches[2] == $from_stop)
		$from_stop_candidates[] = $matches[3] . $matches[2];
	      elseif ($matches[5] . $matches[4] == $from_stop)
		$from_stop_candidates[] = $matches[5] . $matches[4];
	      if ($matches[3] . $matches[2] == $to_stop)
		$to_stop_candidates[] = $matches[3] . $matches[2];
	      elseif ($matches[5] . $matches[4] == $to_stop)
		$to_stop_candidates[] = $matches[5] . $matches[4];
	    }

	    foreach ($from_stop_candidates as $from) {
	      foreach ($to_stop_candidates as $to) {
		if ($to - $from == 1 || $to - $from + $num_stops == 1) {
		  $from_pos = $from;
		  break 2;
		}
	      }
	    }
	  }

	  // make sure the name of the stop is the original
	  $from_stop = $stop_order[$from_pos];

	  // read all the coordinates in the path (finally!)
	  $routeInfo[$from_stop]['path'] = Array();
	  foreach ($path->getElementsByTagName('point') as $point) {
	    $routeInfo[$from_stop]['path'][] = Array(
              'lat' => $point->getAttribute('lat'),
	      'lon' => $point->getAttribute('lon'),
	      );
	  }

	} // closes foreach($path...
      } // closes foreach($xml...
      self::$routeCache[$routeName] = $routeInfo;
      self::$routeDiskCache->write(self::$routeCache[$routeName],
                                   self::$routeCachePrefix . $routeName);
    }
    return self::$routeCache[$routeName];
  }

  /* public predictions methods */

  // max tolerance is how long we should go without receiving updates
  // before we decide the GPS is not active
  public static function gps_active($routeName) {
    self::get_predictions($routeName);
    $age = self::get_prediction_age($routeName);
    return ($age <= NEXTBUS_CACHE_MAX_TOLERANCE);
  }

  // coordinates of current position of vehicle(s)
  // nextbus api returns all vehicles at once
  public static function get_coordinates($routeName) {
    $age = self::$vehicleDiskCache->getAge();

    $coordinates = Array();
    // if cache is fresh, return cached values
    if ($age <= NEXTBUS_VEHICLE_CACHE_TIMEOUT) {
      self::$vehicleCache = self::$vehicleDiskCache->read();

      if (array_key_exists($routeName, self::$vehicleCache)) { 
	foreach (self::$vehicleCache[$routeName] as $coords) {
	  $coords['secsSinceReport'] += $age;
	  $coordinates[] = $coords;
	}
      }
      return $coordinates;
    }

    $age = ($age == PHP_INT_MAX) ? 0 : $age;

    // get all vehicle locations
    self::set_command('vehicleLocations');
    self::set_other_params(Array('t' => 0));
    $xml = self::query();
    if (!$xml) {
      if (array_key_exists($routeName, self::$vehicleCache)) {
	foreach (self::$vehicleCache[$routeName] as $coords) {
	  $coords['secsSinceReport'] += $age;
	  $coordinates[] = $coords;
	}
      }
    } else {
      // since not all vehicles always show up,
      // update each secsSinceReport tag
      // to be consistent with the new file modification time
      // and allow them to be overwritten if found
      foreach (self::$vehicleCache as $route => $data) {
	for ($i = 0; $i < count($data); $i++) {
	  self::$vehicleCache[$route][$i]['secsSinceReport'] += $age;
	}
      }

      // there's only one lastTime tag
      foreach ($xml->getElementsByTagName('lastTime') as $lastTime) { 
	// drop microseconds
	$reportAge = time() - (int) substr($lastTime->getAttribute('time'), 0, 10); 
      }

      $results = Array();
      foreach ($xml->getElementsByTagName('vehicle') as $vehicle) {
	$routeTag = $vehicle->getAttribute('routeTag');
	if (!array_key_exists($routeTag, $results)) 
	  $results[$routeTag] = Array();
	$results[$routeTag][] = Array(
         'lat' => $vehicle->getAttribute('lat'),
	 'lon' => $vehicle->getAttribute('lon'),
	 'secsSinceReport' => $reportAge 
	          + (int) $vehicle->getAttribute('secsSinceReport'),
	 'heading' => $vehicle->getAttribute('heading'),
	 );
      }
      foreach ($results as $route => $data) {
	self::$vehicleCache[$route] = $data;
      }
      self::$vehicleDiskCache->write(self::$vehicleCache);
      if (self::$vehicleCache[$routeName])
	$coordinates = self::$vehicleCache[$routeName];
    }

    return $coordinates;
  }

  // predictions for multiple stops
  // returns array of times or false on failure
  public static function get_predictions($routeName) {
    if (!self::is_nextbus_route($routeName))
      return FALSE;
    if (self::prediction_is_fresh($routeName)) {
      self::$predictionCache[$routeName] = self::$predDiskCache->read($routeName);
      return self::$predictionCache[$routeName];
    }

    $routeInfo = self::get_route_info($routeName);
    if (!$routeInfo)
      return FALSE;
    $stop_query_list = Array('stops' => Array());
    foreach ($routeInfo as $stop => $attribs) {
      $stop_query_list['stops'][] = $routeName . '|' . $stop;
    }
    self::set_command('predictionsForMultiStops');
    self::set_other_params($stop_query_list);
    $xml = self::query();
    if (!$xml) {
      $age = self::get_prediction_age($routeName);
      if ($age > NEXTBUS_CACHE_MAX_TOLERANCE)
	return FALSE;
      self::$predictionCache[$routeName] = self::$predDiskCache->read($routeName);
    } else {
      $results = Array();
      foreach ($xml->getElementsByTagName('predictions') as $predictions) {
	if ($predictions->hasAttribute('dirTitleBecauseNoPredictions')) {
	  break;
	}
	$stop = self::stop_id_from_title($routeName, 
					 $predictions->getAttribute('stopTitle'));
	$results[$stop] = Array();
	foreach ($predictions->getElementsByTagName('prediction') as $prediction) {
	  $results[$stop][] = $prediction->getAttribute('seconds');
	}
      }
      if (count($results)) {
	self::$predictionCache[$routeName] = $results;
	self::$predictionCache[$routeName]['updated'] = time();
        self::$predDiskCache->write(self::$predictionCache[$routeName],
                                    self::$predictionCachePrefix . $routeName);
      }
    }
    return self::$predictionCache[$routeName];
  }

}

?>
