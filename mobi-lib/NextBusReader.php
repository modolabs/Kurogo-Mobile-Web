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

require_once "lib_constants.inc";
require_once('DiskCache.inc');
require_once('ShuttleObjects.php');

//define('NEXTBUS_SERVICE_URL', 'http://webservices.nextbus.com/service/publicXMLFeed?');

class NextBusAgency {

  protected $agency;
  protected $nickname;
  public $errors;
  private $routeWhiteList = array(); // if we only want a few routes for this agency
  private $routeBlackList = array();

  // query results in memory
  private $predictions = array();
  private $vehicles = array();
  private $routes;
  private $stops = array(); // custom: no 'stops' command from nextbus

  private $ageOfLastQuery;

  // since we daemonize this class
  // things can be stuck in memory for long times
  private $predictionsUpdated = array();
  private $vehiclesUpdated = array();
  private $routesUpdated = array();
  private $routeListUpdated = 0;

  // cache files on disk
  private $routeCache;
  private $predCache;
  private $vehicleCache;
  private $stopCache;

  private function cacheFileName($params) {
    // just need a unique name for each set of params
    //$command = $params['command'];
    if (array_key_exists('r', $params)) {
      return $this->agency . '.' . $params['r']; // route
    } else if ($params['command'] == 'predictionsForMultiStops') {
      $stops = $params['stops'];
      list($route_id, $direction_id, $stop_id) = explode('|', end($stops));

      // being lazy... if there's more than 5 items it's a route
      // otherwise it's a stop
      if (count($stops) > 5)
	$filename = $route_id;
      else
	$filename = $stop_id;

      return $this->agency . '.' . $filename;

    } else {
      return $this->agency;
      //return $command;
    }
  }

  private function cacheForCommand($command) {
    switch ($command) {
    case 'routeList': case 'routeConfig':
      return $this->routeCache;
    case 'predictions': case 'predictionsForMultiStops':
      return $this->predCache;
    case 'vehicleLocations':
      return $this->vehicleCache;
    default:
      return NULL;
    }
  }

  public function __construct($agency, $nickname=NULL) {
    $this->agency = $agency;
    $this->nickname = $nickname ? $nickname : $agency;

    $this->routeCache = new DiskCache(CACHE_DIR . 'NextBus', 86400, TRUE);
    $this->routeCache->setSuffix('.route.xml');
    $this->routeCache->preserveFormat();

    $this->predCache = new DiskCache(CACHE_DIR . 'NextBus', 20, TRUE);
    $this->predCache->setSuffix('.prediction.xml');
    $this->predCache->preserveFormat();

    $this->vehicleCache = new DiskCache(CACHE_DIR . 'NextBus', 20, TRUE);
    $this->vehicleCache->setSuffix('.vehicle.xml');
    $this->vehicleCache->preserveFormat();

    $this->stopCache = new DiskCache(CACHE_DIR . 'NextBus/' . $this->nickname . '.stops.php', 86400);
  }

  public function addRoute(ShuttleRoute $route) {
    if ((!$this->routeWhiteList || in_array($route->id, $this->routeWhiteList))
	&& !in_array($route->id, $this->routeBlackList)) {
      $this->routes[$route->id] = $route;
    }
  }

  public function whiteList($routeList) {
    $this->routeWhiteList = $routeList;
  }

  public function blackList($routeList) {
    $this->routeBlackList = $routeList;
  }

  public function routeList() {
    if (time() - $this->routeListUpdated > 86400) {
      $params = array('command' => 'routeList');
      $xml = $this->query($params);
      if ($xml) {
        $this->routes = array();
        foreach ($xml->getElementsByTagName('route') as $route) {
	  $route_id = $route->attributes->getNamedItem('tag')->nodeValue;
	  if ((!$this->routeWhiteList 
	       || in_array($route_id, $this->routeWhiteList)) && 
	      (!$this->routeBlackList
	       || !in_array($route_id, $this->routeBlackList))) {
	    $routeData = array(
              'route_id' => $route_id,
	      'route_short_name' => $route_id,
	      'route_long_name' => $route->attributes->getNamedItem('title')->nodeValue,
	      'route_type' => '3',
	      );
	    if (!array_key_exists($route_id, $this->routes)) {
	      $this->routes[$route_id] = new ShuttleRoute($routeData);
	      $this->routes[$route_id]->agency_id = $this->agency;
	    }
	  }
        }
	$this->routeListUpdated = time();

	// now that we know which routes exist,
	// set the lastUpdate time for individual route details
	foreach (array_keys($this->routes) as $route_id) {
	  if (!array_key_exists($route_id, $this->vehiclesUpdated)) {
	    $this->vehicles[$route_id] = array('lastUpdate' => 0);
	  }
	  if (!array_key_exists($route_id, $this->predictionsUpdated)) {
	    $this->predictions[$route_id] = array('lastUpdate' => 0);
	  }
	}

      } // if ($xml)
    }
    return $this->routes;
  }

  // it looks like what NextBus calls a "direction"
  // is what GTFS calls a "trip"
  public function routeConfig($route_id) {
    if (!$this->routes) {
      $this->routeList();
    }

    if (!array_key_exists($route_id, $this->routes)) {
      return;
    }

    if (time() - $this->routes[$route_id]->lastUpdate > 86400) {
      $route = $this->routes[$route_id];

      $params = array(
        'command' => 'routeConfig',
        'r' => $route_id,
        );
      $xml = $this->query($params);
      if ($xml) {

	// nextbus returns path segments between stop pairs in
	// random order, so we keep track of order from <stop> tags
	$stop_order = array();

        // consume stops into overall stop array
	foreach ($xml->getElementsByTagName('stop') as $stop) {
	  $attributes = $stop->attributes;
	  // we don't want the nested stop tags in <direction> tags
	  if ($attributes->getNamedItem('title')) {
	    $stop_id = $attributes->getNamedItem('tag')->nodeValue;
	    $stop_lat = $attributes->getNamedItem('lat')->nodeValue;
	    $stop_lon = $attributes->getNamedItem('lon')->nodeValue;
	    $stop_name = $attributes->getNamedItem('title')->nodeValue;
	    if (!array_key_exists($stop_id, $this->stops)) {
              $aStop = ShuttleSchedule::getStop($stop_id);
              if (!$aStop) {
                $stopData = array(
                  'stop_id' => $stop_id,
		  'stop_tag' => $stop_id,
		  'stop_name' => $stop_name,
		  'stop_lat' => $stop_lat,
		  'stop_lon' => $stop_lon,
                  );
                $aStop = new ShuttleStop($stopData);
              } else {
		$aStop->name = $stop_name;
		$aStop->lat = $stop_lat;
		$aStop->lon = $stop_lon;
	      }
              $this->stops[$stop_id] = $aStop;
	    } else {
		$this->stops[$stop_id]->name = $stop_name;
		$this->stops[$stop_id]->lat = $stop_lat;
		$this->stops[$stop_id]->lon = $stop_lon;
		ShuttleSchedule::setStop($stop_id, $this->stops[$stop_id]);
	    }
	    $this->stops[$stop_id]->addRouteId($route_id);
	    $stop_order[$stop_id] = array();
	  }
        } // stop

	// just for debugging
	$route->stops = array_keys($stop_order);

	// create trip shape, assign to trip objects before populating
	$shape = new ShuttleShape($route_id);
	$segments = array();

        // set up trips.
	// wipe out old trip objects on the route,
	// but preserve properties from whichever is currently running
	$oldTrip = $route->anyTrip(time());
	$oldService = $oldTrip->getService();
	$oldFrequencies = $oldTrip->frequencies;
	$oldStopTimes = $oldTrip->stop_times;

	$route->trips = array();
	foreach ($xml->getElementsByTagName('direction') as $direction) {
	  $attributes = $direction->attributes;
	  if ($attributes->getNamedItem('useForUI')->nodeValue == 'true') {
	    $direction_id = $attributes->getNamedItem('tag')->nodeValue;
	    $headsign = $attributes->getNamedItem('title')->nodeValue;
	    $trip_id = $route_id . '_' . $direction_id;
	    $tripData = array(
              'trip_id' => $trip_id,
	      'route_id' => $route_id,
	      );
	    
	    $trip = new ShuttleTrip($tripData);
	    $trip->direction_id = $direction_id;
	    $trip->headsign = $headsign;
	    $trip->shape_id = $route_id;
	    $trip->shape = $shape;

	    // stuff we preserve b/c nextbus doesn't have it
	    $trip->setService($oldService);
	    $trip->frequencies = $oldFrequencies;
	    $route->addTrip($trip);

	    foreach ($direction->getElementsByTagName('stop') as $stop) {
	      $stop_id = $stop->attributes->getNamedItem('tag')->nodeValue;
	      $segments[$stop_id] = array();
	      $trip->addStop($this->stops[$stop_id]);
	      if (array_key_exists($stop_id, $oldStopTimes)) {
		$trip->stop_times[$stop_id] = $oldStopTimes[$stop_id];
	      }
	    }

	  }
	} // direction

	// path
	foreach ($xml->getElementsByTagName('path') as $path) {
	  $tag = $path->getElementsByTagName('tag')->item(0);
	  $tag_id = $tag->attributes->getNamedItem('id')->nodeValue;
	  $parts = explode('_', $tag_id);
	  $stop_id = $parts[1];
	  if ($parts[2] == 'd') $stop_id .= '_d';
	  
	  foreach ($path->getElementsByTagname('point') as $point) {
	    $attributes = $point->attributes;
	    $pointData = array(
              'shape_pt_lat' => $attributes->getNamedItem('lat')->nodeValue,
              'shape_pt_lon' => $attributes->getNamedItem('lon')->nodeValue,
	      );
	    $stop_order[$stop_id][] = $pointData;
	  }
	} // path

	foreach ($stop_order as $stop_id => $segment) {
	  foreach ($segment as $pointData)
	    $shape->addPoint($pointData);
	}

	$route->lastUpdate = time();

      } // if ($xml)

    }

    return $this->routes[$route_id];

  }

  public function vehicleLocations($route_id) {
    if (isset($this->vehicles[$route_id])) {
      $age = time() - $this->vehicles[$route_id]['lastUpdate'];
    } else {
      $age = 9999; // no data at all, reload
    }
    if ($age > 20) {
      $params = array(
        'command' => 'vehicleLocations',
        'r' => $route_id,
        't' => '0',
      );
      $xml = $this->query($params);
      if ($xml) {
        $result = array();
        foreach ($xml->getElementsByTagName('vehicle') as $vehicle) {
          $attrs = $vehicle->attributes;
          $id = $attrs->getNamedItem('id')->nodeValue;
          $result[$id] = array(
            'lat' => $attrs->getNamedItem('lat')->nodeValue,
            'lon' => $attrs->getNamedItem('lon')->nodeValue,
            'secsSinceReport' => intval($attrs->getNamedItem('secsSinceReport')->nodeValue),
            'heading' => $attrs->getNamedItem('heading')->nodeValue,
          );
        }
        $age = $this->ageOfLastQuery;
        $result['lastUpdate'] = time() - $age;
        $this->vehicles[$route_id] = $result;
      }
    }
    
    foreach ($this->vehicles[$route_id] as $vehicle => $report) {
      if ($vehicle != 'lastUpdate') {
        $secsSinceReport = $report['secsSinceReport'] + $age;
        $this->vehicles[$route_id][$vehicle]['secsSinceReport'] = $secsSinceReport;
      }
    }

    return $this->vehicles[$route_id];
  }

  public function getAllStops() {
    if (!$this->stops) {
      if (!$this->stopCache->isFresh()) {
        // make sure we've gone through each routeConfig
        foreach ($this->routes as $route_id => $route) {
	  if (time() - $route->lastUpdate > 86400) {
	    $this->routeConfig($route_id);
          }
        }
        $this->stopCache->write($this->stops);
      }
      $this->stops = $this->stopCache->read();
    }
    return $this->stops;
  }

  public function predictionsForStop($stop_id) {
    // TODO: only ask for predictions for routes that are running

    // wasteful way to ensure that we have all routes
    // tied to this stop
    $this->getAllStops();
    $stopList = array();
    // we might ask for a stop that is inconsistent with nextbus
    if (!array_key_exists($stop_id, $this->stops)) {
      error_log("stop_id $stop_id not found for agency $this->agency", 0);
      return array();
    }

    foreach ($this->stops[$stop_id]->routes as $route_id) {
      if (array_key_exists($route_id, $this->routes)) {
        $stopList[] = $route_id . '|null|' . $stop_id;
      }
    }

    $params = array(
      'command' => 'predictionsForMultiStops',
      'stops' => $stopList,
      );

    $time = time();
    $xml = $this->query($params);
    if ($xml) {
      $result = array();
      foreach ($xml->getElementsByTagName('predictions') as $predictions) {
	$attributes = $predictions->attributes;
	$route_id = $attributes->getNamedItem('routeTag')->nodeValue;
	$result[$route_id] = array();
	foreach ($predictions->getElementsByTagName('prediction') as $prediction) {
	  $attributes = $prediction->attributes;
	  $seconds = $attributes->getNamedItem('seconds')->nodeValue;
	  $result[$route_id][] = $time + intval($seconds);
	}
      }

      return $result;
    }

  }

  public function predictionsForRoute($route_id) {
    $this->routeConfig($route_id);
    $time = time();
    $age = isset($this->predictions[$route_id]) ? 
    $this->predictions[$route_id]['lastUpdate'] : 0;
    
    if ($time - $age > 20) {
      $route = $this->routes[$route_id];
      $stopList = array();
      foreach ($route->stops as $stop_id) {
        $stopList[] = $route_id . '|null|' . $stop_id;
      }
    
      $params = array(
        'command' => 'predictionsForMultiStops',
        'stops' => $stopList,
      );
      $xml = $this->query($params);
      
      $result = array();
      if ($xml) {
        foreach ($xml->getElementsByTagName('predictions') as $predictions) {
          $attributes = $predictions->attributes;
          $stop_id = $attributes->getNamedItem('stopTag')->nodeValue;
          $resultForStop = array();
          foreach ($predictions->getElementsByTagName('prediction') as $prediction) {
            $attributes = $prediction->attributes;
            $seconds = $attributes->getNamedItem('seconds')->nodeValue;
            // nextbus returns the number of seconds
            // after the bus is expected to arrive.
            // keep in absolute time since we may cache for a long time
            $resultForStop[] = $time + intval($seconds);
          }
          if (count($resultForStop)) {
            $result[$stop_id] = $resultForStop;
          }
        }
      }
      
      if (count($result)) {
        $result['lastUpdate'] = time() - $this->ageOfLastQuery;
        $age = $result['lastUpdate'];
        $this->predictions[$route_id] = $result;
                
      } else if (time() - $age > 120) {
        // invalidate predictions if they're too old
        $this->predictions[$route_id] = array();
      }
    }
    return $this->predictions[$route_id];
  }

  private function parseNextBusXML($text) {
    $xml = new DOMDocument();
    $xml->loadXML($text);
    $this->errors = array();
    foreach ($xml->getElementsByTagName('Error') as $error) {
      $this->errors[] = $error->nodeValue;
    }
    if (!$this->errors) {
      return $xml;
    }
    return FALSE;
  }

  // return false on failure
  private function query($params) {
    $cache = $this->cacheForCommand($params['command']);
    $filename = $this->cacheFileName($params);
    if (!$cache->isFresh($filename)) {
      $params['a'] = $this->agency;
      $url = NEXTBUS_FEED_URL . http_build_query($params);
      // remove urlencoded brackets.  nextbus doesn't do brackets.
      $url = preg_replace('/%5B\d+%5D/', '', $url);
      //error_log("shuttles: requesting $url", 0);
      //error_log("Requesting NextBus query with params:\n".print_r($params, true));
      $contents = file_get_contents($url);

      if (!$contents) {
        $this->errors[] = "Failed to read contents from $url, reading expired cache";
        $xml = $this->parseNextBusXML($cache->read($filename));
      } else {
        $xml = $this->parseNextBusXML($contents);
        if ($xml) {
          $cache->write($contents, $filename);
        } else {
          $this->errors[] = "XML from $url had errors, reading expired cache";
          $xml = $this->parseNextBusXML($cache->read($filename));
        }
      }

    } else {
      $contents = $cache->read($filename);
      $xml = $this->parseNextBusXML($contents);
    }

    foreach ($this->errors as $error) {
      error_log("shuttles: $error", 0);
    }

    if ($xml) {
      $this->ageOfLastQuery = $cache->getAge($filename);
      return $xml;
    }

    return FALSE;
  }

}




