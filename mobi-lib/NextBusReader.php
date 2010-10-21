<?

/* this file interacts with nextbus' xml feed */

/* this uses the constants:
 * CACHE_DIR -- location of cache files
 * NEXTBUS_FEED_URL -- base location of nextbus feed
 * NEXTBUS_AGENCY -- id of the agency that's querying nextbus
 * NEXTBUS_ROUTE_CACHE_TIMEOUT -- max age of route info cache
 * NEXTBUS_PREDICTION_CACHE_TIMEOUT -- max age of predictions cache
 * NEXTBUS_CACHE_MAX_TOLERANCE -- max age before reverting to published schedule
 */
$docRoot = getenv("DOCUMENT_ROOT");

require_once($docRoot . '/mobi-config/mobi_lib_constants.php');

class NextBusReader {
  private static $query_params = Array();
  private static $routeCache = Array();
  private static $predictionCache = Array();
  private static $vehicleCache = Array();
  private static $routeCachePrefix = 'NEXTBUS_ROUTE_';
  private static $predictionCachePrefix = 'NEXTBUS_PREDICTION_';
  private static $vehicleCacheFile = 'NEXTBUS_VEHICLE_LOCATIONS';

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
	//warn($error->nodeValue);
      }
      if (!$errors)
	return $xml_obj;
    }
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

  private static function assert_exists($routeName) {
    self::init();
    if (!array_key_exists($routeName, self::$routeCache)) {
      throw new Exception("invalid route name '$routeName' for nextbus");
    }
  }

  /* private cache methods */

  private static function write_cache($filename, $data) {
    $fhandle = fopen(CACHE_DIR . $filename, 'w');
    fwrite($fhandle, json_encode($data));
    fclose($fhandle);
  }

  private static function read_cache($filename) {
    if (file_exists(CACHE_DIR . $filename))
      return json_decode(file_get_contents(CACHE_DIR . $filename), TRUE);
    return FALSE;
  }

  private static function write_route_cache($routeName) {
    self::write_cache(self::$routeCachePrefix 
		      . $routeName, self::$routeCache[$routeName]);
  }

  private static function read_route_cache($routeName) {
    return self::read_cache(self::$routeCachePrefix . $routeName);
  }

  private static function write_prediction_cache($routeName) {
    self::write_cache(self::$predictionCachePrefix 
		      . $routeName, self::$predictionCache[$routeName]);
  }

  private static function read_prediction_cache($routeName) {
    return self::read_cache(self::$predictionCachePrefix . $routeName);
  }

  private static function write_vehicle_cache() {
    self::write_cache(self::$vehicleCacheFile, self::$vehicleCache);
  }

  private static function read_vehicle_cache() {
    return self::read_cache(self::$vehicleCacheFile);
  }

  private static function route_is_fresh($routeName) {
    $filename = CACHE_DIR . self::$routeCachePrefix . $routeName;
    return (file_exists($filename) 
	    && time() - filemtime($filename) < NEXTBUS_ROUTE_CACHE_TIMEOUT);
  }

  private static function get_prediction_age($routeName) {
    $filename = CACHE_DIR . self::$predictionCachePrefix . $routeName;
    if (file_exists($filename))
      return time() - filemtime($filename);
    return FALSE;
  }

  private static function prediction_is_fresh($routeName) {
    $age = self::get_prediction_age($routeName);
    return ($age && $age < NEXTBUS_PREDICTION_CACHE_TIMEOUT);
  }

  /* public top-level methods */

  public static function init() {
    self::reset_query();
    if (!self::$routeCache) {
      // query nextbus to see what routes are available
      self::set_command('routeList');
      $xml = self::query();
      if (!$xml) {
	// query failed; get {routeName}s from cached filenames
	foreach (scandir(CACHE_DIR) as $filename) {
	  if (strpos($filename, self::$route_cache_prefix) == 0) {
	    $routeName = substr($filename, 0, strlen(self::$route_cache_prefix));
	    self::$routeCache[$routeName] = Array();
	  }
	}
      } else {
	foreach ($xml->getElementsByTagName('route') as $route) {
	  $routeName = $route->getAttribute('tag');
	  self::$routeCache[$routeName] = Array();
	  if (!self::route_is_fresh($routeName))
	    self::$routeCache[$routeName] = self::read_route_cache($routeName);
	}
      }
    }
  }

  public static function get_route_list() {
    self::init();
    return array_keys(self::$routeCache);
  }

  /* public route methods */

  public static function get_last_refreshed($routeName) {
    return time() - self::get_prediction_age($routeName);
  }

  public static function get_stop_list($routeName) {
    self::assert_exists($routeName);
    $routeInfo = self::get_route_info($routeName);
    return array_keys($routeInfo);
  }

  public static function get_path($routeName) {
    self::assert_exists($routeName);
    $routeInfo = self::get_route_info($routeName);
    $path = Array();
    foreach ($routeInfo as $stopName => $stopInfo) {
      $path = array_merge($path, $stopInfo['path']);
    }
  }

  public static function get_route_info($routeName) {
    self::assert_exists($routeName);
    if (self::route_is_fresh($routeName)) {
      self::$routeCache[$routeName] = self::read_route_cache($routeName);
      return self::$routeCache[$routeName];
    }
    self::set_command('routeConfig');
    self::set_query_route($routeName);
    $xml = self::query();
    if (!$xml) { // either we got errors or couldn't read the url
      self::$routeCache[$routeName] = self::read_route_cache($routeName);
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
      self::write_route_cache($routeName);
    }
    return self::$routeCache[$routeName];
  }

  /* public predictions methods */

  public static function gps_active($routeName) {
    self::get_predictions($routeName);
    $age = self::get_prediction_age($routeName);
    return ($age !== FALSE && $age <= NEXTBUS_CACHE_MAX_TOLERANCE);
  }

  // coordinates of current position of vehicle(s)
  public static function get_coordinates($routeName) {
    $age = 0;
    if (file_exists(self::$vehicleCacheFile)) {
      self::$vehicleCache = self::read_vehicle_data();
      $age = time() - filemtime(self::$vehicleCacheFile);

      // if cache is fresh, return cached values
      if ($age <= NEXTBUS_PRDICTION_CACHE_TIMEOUT) {

	// but if not for this route, skip to query below
	if (in_array($routeName, self::$vehicleCache)) { 
	  $coordinates = Array();
	  foreach (self::$vehicleCache[$routeName] as $coords) {
	    $coords['secsSinceReport'] += $age;
	    $coordinates[] = $coords;
	  }
	  return $coordinates;
	}
      }
    }

    // get all vehicle locations
    self::set_command('vehicleLocations');
    self::set_other_params(Array('t' => 0));
    $xml = self::query();
    if (!$xml) {
      if (!in_array($routeName, self::$vehicleCache))
	return FALSE;
      $coordinates = Array();
      foreach (self::$vehicleCache[$routeName] as $coords) {
	$coords['secsSinceReport'] += $age;
	$coordinates[] = $coords;
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
	if (!in_array($routeTag, $results)) 
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
      self::write_vehicle_cache();
      $coordinates = self::$vehicleCache[$routeName];
    }
    return $coordinates;
  }

  // predictions for multiple stops
  public static function get_predictions($routeName) {
    self::assert_exists($routeName);
    if (self::prediction_is_fresh($routeName)) {
      self::$predictionCache[$routeName] = self::read_prediction_cache($routeName);
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
      if (!$age || $age > NEXTBUS_CACHE_MAX_TOLERANCE)
	return FALSE;
      self::$predictionCache[$routeName] = self::read_prediction_cache($routeName);
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
	self::write_prediction_cache($routeName);
      }
    }
    return self::$predictionCache[$routeName];
  }

}

?>