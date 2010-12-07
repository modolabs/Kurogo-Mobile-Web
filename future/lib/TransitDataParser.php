<?php

require_once('Polyline.php');

define('STATIC_MAPS_URL', 'http://maps.google.com/maps/api/staticmap?');

define('TRANSIT_MAX_ARRIVAL_DELAY', 120*60); // 2 hours
define('TRANSIT_PREDICTION_THRESHOLD', 320); // 5 mins, 20 seconds
define('TRANSIT_DEFAULT_ROUTE_COLOR', 'b12727'); // shade of red

class TransitConfig {
  static private $transitConfig = array(
    array(
      'routes' => array(
      ),
      'agencies' => array(
        'harvard',
      ),
      'live' => array(
        'class' => 'TranslocTransitDataParser',
        'args' => array(
          'hostname' => 'harvard',
          'routeFieldOverrides' => array(
            'description' => array(
              'harvard-5'  => 'Runs 5:40am - 8:40am, Mon-Fri',  // Quad Stadium
              'harvard-52' => 'Runs 7:40am - 4:33pm, Mon-Fri',  // Quad Express
              'harvard-7'  => 'Runs 4:20pm - 12:33am, Sat-Sun', // 1636'er
              'harvard-8'  => 'Runs 4:30pm - 12:37am, Mon-Fri', // River Houses A
              'harvard-9'  => 'Runs 4:30pm - 12:37am, Mon-Fri', // River Houses B
              'harvard-51' => 'Runs 4:30pm - 12:37am, Mon-Fri', // River Houses C
              'harvard-27' => 'Runs 4:45pm - 12:32am, Mon-Fri', // Quad-Yard Express
              'harvard-29' => 'Runs 7:40am - 4:15pm, Mon-Fri',  // Mather Express
              'harvard-39' => 'Runs 12:05pm - 4:23pm, Sat-Sun', // Crimson Campus Cruiser
              'harvard-40' => 'Runs 5:00pm - 7:55pm, Sat-Sun',  // Soldiers Field Park I
              'harvard-41' => 'Runs 7:20am - 9:50am, Mon-Fri',  // Soldiers Field Park II
              'harvard-11' => 'Runs 3:50pm - 9:10pm, Mon-Fri',  // Soldiers Field Park III
              'harvard-13' => 'Runs 3:50pm - 9:10pm, Mon-Fri',  // Soldiers Field Park IV
              // Extended Overnight
              'harvard-14' => 'Runs 12:40am - 3:52am, Sun-Thurs Nights; 12:40am - 4:52am, Fri-Sat Nights', 
            ),
            'summary' => array(
              'harvard-5'  => 'River Houses via Harvard Sq',                           // Quad Stadium
              'harvard-52' => 'Memorial Hall via Harvard Sq',                          // Quad Express
              'harvard-7'  => 'Quad - Memorial Hall - Lamont - Boylston',              // 1636'er
              'harvard-8'  => 'Harvard Sq - Quad - Memorial Hall - Lamont',            // River Houses A
              'harvard-9'  => 'Harvard Sq - Quad - Memorial Hall - Lamont',            // River Houses B
              'harvard-51' => 'Harvard Sq - Quad - Memorial Hall - Lamont',            // River Houses C
              'harvard-27' => 'Lamont - Harvard Sq',                                   // Quad-Yard Express
              'harvard-29' => 'Memorial Hall via Harvard Sq',                          // Mather Express
              'harvard-39' => 'Quad - Mather House - via Memorial Hall',               // Crimson Campus Cruiser
              'harvard-40' => 'Quad - Lamont Library - via Harvard Sq',                // Soldiers Field Park I
              'harvard-41' => 'Business School - Harvard Sq',                          // Soldiers Field Park II
              'harvard-11' => 'Business School - Harvard Sq - Memorial Hall - Lamont', // Soldiers Field Park III
              'harvard-13' => 'Business School - Harvard Sq - Memorial Hall - Lamont', // Soldiers Field Park IV
              'harvard-14' => 'Quad - Mather House via Memorial Hall',                 // Extended Overnight
            ),
          ),
        ),
      ),
    ),
    array(
      'routes' => array(
      ),
      'agencies' => array(
        'masco',
      ),
      'live' => array(
        'class' => 'TranslocTransitDataParser',
        'args' => array(
          'hostname' => 'masco',
          'routeFieldOverrides' => array(
            'description' => array(
              'masco-3'  => 'Runs 6:40am - 11:30pm Mon-Fri, 8:00AM - 10:30pm Sat',  // M2 Cambridge - Coolidge Corner
              'masco-10' => 'Runs 3:20pm - 8:05pm, Mon-Fri',                        // JFK/UMASS - PM
              'masco-11' => 'Runs 5:40am - 9:30am, Mon-Fri',                        // M6 Chestnut Hill - AM
              'masco-12' => 'Runs 2:35pm - 8:30pm, Mon-Fri',                        // M6 Chestnut Hill - PM
              'masco-13' => 'Runs 6:00am - 9:35am, Mon-Fri',                        // JFK/UMASS - AM
              'masco-14' => 'Runs 5:30am - 9:50am, Mon-Fri',                        // Ruggles Express - AM
              'masco-16' => 'Runs 6:40am - 11:30pm, Mon-Fri, 8:00AM - 10:30pm Sat', // M2 Cambridge - Vanderbilt Hall
              'masco-17' => 'Runs 5:35am - 10:30pm, Mon-Fri',                       // Wentworth Shuttle - AM
              'masco-18' => 'Runs 2:25pm - 8:55pm, Mon-Fri',                        // Wentworth Shuttle - PM
              'masco-19' => 'Runs 5:30am - 10:20am, Mon-Fri',                       // Crosstown - AM
              'masco-20' => 'Runs 2:25pm - 8:55pm, Mon-Fri',                        // Crosstown - PM
              'masco-25' => 'Runs 2:30pm - 8:45pm, Mon-Fri',                        // Ruggles Express - PM
              'masco-27' => 'Runs 5:00am - 10:00am, Mon-Fri',                       // Fenway (Beacon/Kenmore) - AM
              'masco-28' => 'Runs 5:30am - 10:00am, Mon-Fri',                       // Fenway (Lansdowne) - AM
              'masco-29' => 'Runs 2:30pm - 9:30pm, Mon-Fri',                        // Fenway - PM
              'masco-30' => 'Runs 10:12am - 2:28pm, Mon-Fri',                       // Fenway Mid-Day
              'masco-31' => 'Runs 10:12am - 2:28pm, Mon-Fri',                       // Fenway Mid-Day Express
            ),
            'summary' => array(
              'masco-3'  => 'Vanderbilt - Central Sq - Lamont',                     // M2 Cambridge - Coolidge Corner
              'masco-10' => 'JFK/UMASS Station - Vanderbilt - Brigham Circle',      // JFK/UMASS - PM
              'masco-11' => 'Chestnut Hill Lot - BIDMC Shapiro',                    // M6 Chestnut Hill - AM
              'masco-12' => '70 Francis St - Chestnut Hill Lot',                    // M6 Chestnut Hill - PM
              'masco-13' => 'JFK/UMASS MBTA Station - Vanderbilt - Brigham Circle', // JFK/UMASS - AM
              'masco-14' => 'Ruggles Station - BIDMC Shapiro',                      // Ruggles Express - AM
              'masco-16' => 'Vanderbilt - Central Square - Lamont',                 // M2 Cambridge - Vanderbilt Hall
              'masco-17' => 'Wentworth Lot - Children’s Station',                   // Wentworth Shuttle - AM
              'masco-18' => 'Vanderbilt - BIDMC Shapiro - Wentworth Lot',           // Wentworth Shuttle - PM
              'masco-19' => 'Crosstown Garage - BIDMC Shapiro - BIDMC East ',       // Crosstown - AM
              'masco-20' => 'Vanderbilt - 70 Francis St - Crosstown Garage',        // Crosstown - PM
              'masco-25' => 'BIDMC West - Ruggles Station',                         // Ruggles Express - PM
              'masco-27' => 'Beacon Street Lot - Landmark - Brookline Ave/Bank of America', // Fenway (Beacon/Kenmore) - AM
              'masco-28' => 'Lansdowne Garage - Landmark - Brookline Ave/Bank of America',  // Fenway (Lansdowne) - AM
              'masco-29' => 'Deaconess Rd - Brookline/BI East - Fenway Garage',     // Fenway - PM
              'masco-30' => 'BIDMC West - BIDMC East - Kenmore Lot',                // Fenway Mid-Day
              'masco-31' => 'BIDMC West - BIDMC East - Kenmore Lot',                // Fenway Mid-Day Express
            ),
          ),
        ),
      ),
    ),
    /*array( 
      'routes' => array(
        'boston', 
        'tech', 
        'saferidebostone', 
        'saferidebostonw', 
        'saferidebostonall', 
        'saferidecambeast', 
        'saferidecambwest', 
        'saferidecamball', 
        'morning', 
        'midday', 
        'evening', 
      ),
      'agencies' => array(
        'mit',
        'saferide',
        'charles-river',
      ),
      'live' => array(
        'class' => 'NextBusTransitDataParser',
        'args' => array(
          'isLoop' => true,
          'agencyRemap' => array(
            'saferide' => 'mit',
          ),
        ),
      ),
      'static' => array(
        'class' => 'GTFSTransitDataParser',
        'args' => array(
          'directory' => 'mit',
        ),
      ),
    ),
    array( 
      'routes' => array(
        '1', 
        '701', 
        '747', 
        '748',
      ),
      'agencies' => array(
        'mbta',
      ),
      'live' => array(
        'class' => 'NextBusTransitDataParser',
        'args' => array(
          'isLoop' => false,
        ),
      ),
      'static' => array(
        'class' => 'GTFSTransitDataParser',
        'args' => array(
          'directory' => 'mbta',
          'routeFieldOverrides' => array(
            'description' => array(
              '1'   => 'Runs 5:10am-1:25am Mon-Sat, 6:10am-1:19am Sun',
              '701' => 'Runs 6am-7:40pm Mon-Fri except holidays',
              '747' => 'Runs 5:55am-7:15pm Mon-Fri except holidays',
              '748' => 'Runs 6:35am-7:24pm Mon-Fri except holidays',
            ),
          ),
        ),
      ),
    ),*/
  );

  static public function get() {
    return self::$transitConfig;
  }
}

class TransitDataView {
  private $transitConfig = array();
  private $parsers = array();
  private $routeIDs = array();
  
  function __construct() {
    $this->transitConfig = TransitConfig::get();
    foreach ($this->transitConfig as $config) {
      $this->routeIDs = array_merge($this->routeIDs, $config['routes']);
      if (isset($config['live'])) {
        $parser['live'] = TransitDataParser::factory(
          $config['live']['class'], 
          $config['agencies'], 
          $config['routes'], 
          $config['live']['args']
        );
      } else {
        $parser['live'] = false;
      }
      if (isset($config['static'])) {
        $parser['static'] = TransitDataParser::factory(
          $config['static']['class'], 
          $config['agencies'], 
          $config['routes'], 
          $config['static']['args']
        );
      } else {
        $parser['static'] = false;
      }
      $this->parsers[] = $parser;
    }
  }
  
  public function refreshLiveParsers() {
    foreach ($this->transitConfig as $i => $config) {
      if (isset($config['live'])) {
        unset($this->parsers[$i]['live']);
        $this->parsers[$i]['live'] = TransitDataParser::factory(
          $config['live']['class'], 
          $config['agencies'], 
          $config['routes'], 
          $config['live']['args']
        );
      }
    }
  }
  
  public function getStopInfoForRoute($routeID, $stopID) {
    $stopInfo = array();
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $stopInfo = $parser['live']->getStopInfoForRoute($routeID, $stopID);
    }
    
    if ($parser['static']) {
      $staticStopInfo = $parser['static']->getStopInfoForRoute($routeID, $stopID);
    }
    
    if (!$stopInfo) {
      $stopInfo = $staticStopInfo;
    }
    
    if ($stopInfo) {
      if (!isset($stopInfo['arrives']) || $staticStopInfo['arrives'] < $stopInfo['arrives']) {
          $stopInfo['arrives'] = $staticStopInfo['arrives'];
      }
      if (!isset($stopInfo['predictions'])) {
        $stopInfo['predictions'] = $staticStopInfo['predictions'];
        
      } else if (count($staticStopInfo['predictions'])) {
        $stopInfo['predictions'] = array_merge($stopInfo['predictions'], $staticStopInfo['predictions']);
        
        $stopInfo['predictions'] = array_unique($stopInfo['predictions']);
        sort($stopInfo['predictions']);
      }
    }
    
    return $stopInfo;
  }
  
  public function getStopInfo($stopID) {
    $stopInfo = array();
    
    foreach ($this->parsersForStop($stopID) as $parser) {
      $parserInfo = false;
      
      if ($parser['live']) {
        $parserInfo = $parser['live']->getStopInfo($stopID);
      }
      
      if ($parser['static']) {
        $staticParserInfo = $parser['static']->getStopInfo($stopID);
      }
      
      if (!$parserInfo) {
        $parserInfo = $staticParserInfo;
      } else if (isset($staticParserInfo['routes'])) {
        foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
          if (!count($stopTimes) && isset($staticParserInfo['routes'][$routeID])) {
            $parserInfo['routes'][$routeID] = $staticParserInfo['routes'][$routeID];
          }
        }
      }
      
      if ($parserInfo) {
        if (!count($stopInfo)) {
          $stopInfo = $parserInfo;
        } else {
          foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
            if (!isset($stopInfo['routes'][$routeID])) {
              $stopInfo['routes'][$routeID] = $stopTimes;
            } else {
              if (!isset($stopTimes['arrives']) || $stopTimes['arrives'] < $stopInfo['routes'][$routeID]['arrives']) {
                $stopInfo['routes'][$routeID]['arrives'] = $stopTimes['arrives'];
              }
              if (!isset($stopTimes['predictions'])) {
                $stopInfo['routes'][$routeID]['predictions'] = $stopTimes['predictions'];
                
              } else if (count($stopTimes['predictions'])) {
                $stopInfo['routes'][$routeID]['predictions'] = array_merge(
                  $stopInfo['routes'][$routeID]['predictions'], $stopTimes['predictions']);
                
                $stopInfo['routes'][$routeID]['predictions'] = array_unique($stopInfo['routes'][$routeID]['predictions']);
                sort($stopInfo['routes'][$routeID]['predictions']);
              }
            }
          }
        }
      }
    }
    
    return $stopInfo;
  }

  public function getMapImageForStop($stopID, $width=270, $height=270) {
    $image = false;
    $parser = reset($this->parsersForStop($stopID));
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForStop($stopID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForStop($stopID, $width, $height);
    }
    
    return $image;
  }

  public function getMapImageForRoute($routeID, $width=270, $height=270) {
    $image = false;
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForRoute($routeID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForRoute($routeID, $width, $height);
    }
    
    return $image;
  }

  public function routeIsRunning($routeID, $time=null) {
    $isRunning = false;
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $isRunning = $parser['live']->routeIsRunning($routeID, $time);
      
    } else if ($parser['static']) {
      $isRunning = $parser['static']->routeIsRunning($routeID, $time);
    }
    
    return $isRunning;
  }
  
  public function getRouteInfo($routeID, $time=null) {
    $routeInfo = array();
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $routeInfo = $parser['live']->getRouteInfo($routeID, $time);
      if (count($routeInfo)) {
        $routeInfo['live'] = true;
      }
    }
    
    if ($parser['static']) {
      $staticRouteInfo = $parser['static']->getRouteInfo($routeID, $time);
      
      if (!count($routeInfo)) {
        $routeInfo = $staticRouteInfo;
      
      } else if (count($staticRouteInfo)) {
        if (strlen($staticRouteInfo['name'])) {
          // static name is better
          $routeInfo['name'] = $staticRouteInfo['name'];
        }
        if (strlen($staticRouteInfo['description'])) {
          // static description is better
          $routeInfo['description'] = $staticRouteInfo['description'];
        }
        if ($staticRouteInfo['frequency'] != 0) { // prefer static
          $routeInfo['frequency'] = $staticRouteInfo['frequency'];
        }
        if (!count($routeInfo['stops'])) {
          $routeInfo['stops'] = $staticRouteInfo['stops'];
        
        } else {
          // Use the static first stop, not the prediction first stop
          // Use static stop names if available
          $firstStop = reset(array_keys($staticRouteInfo['stops']));
          $foundFirstStop = false;
          $moveToEnd = array();
          foreach ($routeInfo['stops'] as $stopID => &$stop) {
            if (!isset($staticRouteInfo['stops'][$stopID])) {
              // NextBus sometimes has _ar suffixes on it.  Try stripping them
              $parts = explode('_', $stopID);
              if (isset($staticRouteInfo['stops'][$parts[0]])) {
                //error_log("Warning: static route does not have live stop id $stopID, using {$parts[0]}");
                $stopID = $parts[0];
              }
            }
            
            if (isset($staticRouteInfo['stops'][$stopID])) {
              $stop['name'] = $staticRouteInfo['stops'][$stopID]['name'];

              if (!$stop['hasTiming'] && $staticRouteInfo['stops'][$stopID]['hasTiming']) {
                $stop['arrives'] = $staticRouteInfo['stops'][$stopID]['arrives'];
                if (isset($staticRouteInfo['stops'][$stopID]['predictions'])) {
                  $stop['predictions'] = $staticRouteInfo['stops'][$stopID]['predictions'];
                } else {
                  unset($stop['predictions']);
                }
              }
            } else {
              error_log("Warning: static route info does not have live stop id $stopID");
            }
            
            if ($foundFirstStop || TransitDataParser::isSameStop($stopID, $firstStop)) {
              $foundFirstStop = true;
            } else {
              $moveToEnd[$stopID] = $stop;
              unset($routeInfo['stops'][$stopID]);
            }
          }
          $routeInfo['stops'] += $moveToEnd;
        }
      }
    }
    
    if (count($routeInfo)) {
      $now = time();
      
      // Walk the stops to figure out which is upcoming
      $stopIDs     = array_keys($routeInfo['stops']);
      $firstStopID = reset($stopIDs);
      
      $firstStopPrevID  = end($stopIDs);
      if (TransitDataParser::isSameStop($firstStopID, $firstStopPrevID)) {
        $firstStopPrevID = prev($stopIDs);
      }
      
      foreach ($stopIDs as $index => $stopID) {
        if (!isset($routeInfo['stops'][$stopID]['upcoming'])) {
          $arrives = $routeInfo['stops'][$stopID]['arrives'];
    
          if ($stopID == $firstStopID) {
            $prevArrives = $routeInfo['stops'][$firstStopPrevID]['arrives'];
          } else {
            $prevArrives = $routeInfo['stops'][$stopIDs[$index-1]]['arrives'];
          }
    
          // Suppress any soonest stops which are more than 2 hours from now
          $routeInfo['stops'][$stopID]['upcoming'] = 
              (abs($arrives - $now) < TRANSIT_MAX_ARRIVAL_DELAY) && $arrives <= $prevArrives;
        }
      }
      
      $routeInfo['lastupdate'] = $now;
    }

    return $routeInfo;    
  }
  
  public function getRoutePaths($routeID) {
    $paths = array();
    
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $paths = $parser['live']->getRoutePaths($routeID);
    } else if ($parser['static']) {
      $paths = $parser['static']->getRoutePaths($routeID);
    }
    
    return $paths;
  }
  
  public function getRouteVehicles($routeID) {
    $vehicles = array();
    
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $vehicles = $parser['live']->getRouteVehicles($routeID);
    } else if ($parser['static']) {
      $vehicles = $parser['static']->getRouteVehicles($routeID);
    }
    
    return $vehicles;
  }
  
  public function getNews() {
    $allNews = array();
    
    foreach ($this->parsers as $parser) {
      $news = array();

      if ($parser['live']) {
        $news = $parser['live']->getNews();
      }
      
      if ($parser['static']) {
        $staticNews = $parser['static']->getNews();
        if (!count($news)) {
          $news = $staticNews;
        
        } else if (count($staticNews)) {
          $news = $news + $staticNews;
        }
      }
      $allNews += $news;
    }
    
    return $allNews;
  }

  private function getAllRoutes($time=null) {
    $allRoutes = array();

    foreach ($this->parsers as $parser) {
      $routes = array();
      
      if ($parser['live']) {
        $routes = $parser['live']->getRoutes($time);
      }
      
      if ($parser['static']) {
        $staticRoutes = $parser['static']->getRoutes($time);
        if (!count($routes)) {
          $routes = $staticRoutes;
        } else {
          foreach ($routes as $routeID => $routeInfo) {
            if (isset($staticRoutes[$routeID])) {
              if (!$routeInfo['running']) {
                $routes[$routeID] = $staticRoutes[$routeID];
              } else {
                // static name is better
                $routes[$routeID]['name'] = $staticRoutes[$routeID]['name'];
                $routes[$routeID]['description'] = $staticRoutes[$routeID]['description'];
                
                if ($staticRoutes[$routeID]['frequency'] != 0) {
                  $routes[$routeID]['frequency'] = $staticRoutes[$routeID]['frequency'];
                }
              }
            }
          }
          // Pull in static routes with no live data
          foreach ($staticRoutes as $routeID => $staticRouteInfo) {
            if (!isset($routes[$routeID])) {
              $routes[$routeID] = $staticRouteInfo;
            }
          }
        }
      }
      $allRoutes += $routes;
    }
    
    return $allRoutes;
  }
 
  public function getRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are not in service
    foreach ($routes as $routeID => $routeInfo) {
      if (!$routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }
  
  public function getInactiveRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are in service
    foreach ($routes as $routeID => $routeInfo) {
      if ($routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }

  private function parserForRoute($routeID) {
    foreach ($this->parsers as $parser) {
      if ($parser['live'] && $parser['live']->hasRoute($routeID)) {
        return $parser;
      }
      if ($parser['static'] && $parser['static']->hasRoute($routeID)) {
        return $parser;
      }
    }
    return array('live' => false, 'static' => false);
  }
  
  private function parsersForStop($stopID) {
    $parsers = array();
  
    foreach ($this->parsers as $parser) {
      if (($parser['live'] && $parser['live']->hasStop($stopID)) ||
          ($parser['static'] && $parser['static']->hasStop($stopID))) {
        $parsers[] = $parser;
      }
    }
    return $parsers;
  }
}


abstract class TransitDataParser {
  private $routes   = array();
  private $stops    = array();

  static protected $arrows = array(
    '1' => 'n',
    '2' => 'ne',
    '3' => 'e',
    '4' => 'se',
    '5' => 's',
    '6' => 'sw',
    '7' => 'w',
    '8' => 'nw',
  );
  
  private $args = array();
  
  public static function factory($class, $agencyIDs, $routeIDs, $args) {
    $parser = null;
    $parserClassFile = realpath_exists(LIB_DIR."/$class.php");
    if ($parserClassFile) {
      require_once $parserClassFile;
      $parser = new $class($agencyIDs, $routeIDs, $args);
    }
    return $parser;
  }
  
  function __construct($agencyIDs, &$routeIDs, $args) {
    $this->args = $args;
    $this->loadData($agencyIDs, &$routeIDs, $args);
  }
  
  protected function updatePredictionData($routeID) {
    // override if you want to break out loading of prediction data
  }
    
  public function getRouteVehicles($routeID) {
    // override if the parser has vehicle locations
    return array();
  }
  
  public function getNewsForRoutes() {
    // override if the parser can get news items
    return array();
  }
  
  abstract protected function loadData($agencyIDs, $routeIDs, $args);
  
  abstract protected function isLive();

  //
  // Routes
  //

  protected function addRoute($route) {
    $id = $route->getID();

    if (isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning duplicate route '$id'");
      return;
    }
    $this->routes[$id] = $route;
  }
    
  protected function getRoute($id) {
    if (!isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning no such route '$id'");
      return false;
    }

    return $this->routes[$id];
  }
  
  // used to avoid warnings when looking for the right parser for a route
  public function hasRoute($id) {
    return isset($this->routes[$id]);
  }

  //
  // Stops
  //

  protected function addStop($stop) {
    $id = $stop->getID();

    if (isset($this->stops[$id])) {
      // This case seems to happen fairly often
      //error_log(__FUNCTION__."(): Warning duplicate stop '$id'");
      return;
    }
    $this->stops[$id] = $stop;
  }
    
  protected function getStop($id) {
    if (!isset($this->stops[$id])) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
      return false;
    }

    return $this->stops[$id];
  }
  
  // used to avoid warnings when looking at the wrong agency
  public function hasStop($id) {
    return isset($this->stops[$id]);
  }
  
  protected function getMapMarkersForVehicles($vehicles) {
    $query = '';
    
    foreach ($vehicles as $vehicle) {
      $markers .= "|{$vehicle['lat']},{$vehicle['lon']}";
    }
    $query .= '&'.http_build_query(array(
      'markers' => $markers,
    ));
    
    return $query;
  }  
  
  protected function getRouteColor($routeID) {
    return TRANSIT_DEFAULT_ROUTE_COLOR;
  }

  //
  // Query functions
  // 
  
  public function getStopInfoForRoute($routeID, $stopID) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
  
    $this->updatePredictionData($routeID);
    
    $stopInfo = array();

    $now = TransitTime::getCurrentTime();
    $predictions = $this->routes[$routeID]->getPredictionsForStop($stopID, $now); 
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'predictions' => $predictions['predictions'],
      'arrives'     => $predictions['arrives'],
      'live'        => $this->isLive(),
    );
    
    return $stopInfo;
  }
  
  public function getStopInfo($stopID) {
    if (!isset($this->stops[$stopID])) {
      error_log(__FUNCTION__."(): Warning no such stop '$stopID'");
      return array();
    }
  
    $now = TransitTime::getCurrentTime();

    $routePredictions = array();
    foreach ($this->routes as $routeID => $route) {
      if ($route->routeContainsStop($stopID)) {
        $this->updatePredictionData($route->getID());
        
        $routePredictions[$routeID] = $route->getPredictionsForStop($stopID, $now);
        if (isset($routePredictions[$routeID]['arrives'])) {
          $routePredictions[$routeID]['live'] = $this->isLive();
        }
      }
    }
    foreach ($routePredictions as $routeID => $prediction) {
      $routePredictions[$routeID]['name'] = $this->getRoute($routeID)->getName();
    }
    
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'routes'      => $routePredictions,
    );
    
    return $stopInfo;
  }
 
  public function getMapImageForStop($id, $width=270, $height=270) {
    if (!isset($this->stops[$id])) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
      return false;
    }
    
    $stop = $this->stops[$id];
    $coords = $stop->getCoordinates();
    
    $iconURL = false;
    if($_SERVER['SERVER_NAME'] != 'localhost') {
      $iconURL = "http://{$_SERVER['SERVER_NAME']}/modules/transit/images/shuttle_stop_pin.png";
    } else {
      $iconURL = "http://chart.apis.google.com/chart?".http_build_query(array(
        'chst' => 'd_map_pin_icon',
        'chld' => 'bus|'.TRANSIT_DEFAULT_ROUTE_COLOR,
      ));
    }
    
    $query = http_build_query(array(
      'sensor'  => 'false',
      'size'    => "{$width}x{$height}",
      'markers' => "icon:$iconURL|{$coords['lat']},{$coords['lon']}",
    ));
    
    return STATIC_MAPS_URL.$query;
  }

  public function getMapImageForRoute($id, $width=270, $height=270) {
    if (!isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning no such route '$id'");
      return false;
    }
    
    $route = $this->routes[$id];
    $paths = $route->getPaths();
    $color = $this->getRouteColor($id);
    
    if (!count($paths)) {
      error_log(__FUNCTION__."(): Warning no path for route '$id'");
      return false;
    }
    
    $query = http_build_query(array(
      'sensor' => 'false',
      'size'   => "{$width}x{$height}",
    ));
  
    $now = TransitTime::getCurrentTime();
    if ($route->isRunning($now)) {
      $vehicles = $this->getRouteVehicles($id);
      $query .= $this->getMapMarkersForVehicles($vehicles);
    }
    
    foreach ($paths as $points) {
      foreach ($points as &$point) {
        $point = array_values($point);
      }
      $query .= '&'.http_build_query(array(
        'path' => 'weight:3|color:0x'.$color.'C0|enc:'.Polyline::encodeFromArray($points)
      ), 0, '&amp;');
    }
    
    return STATIC_MAPS_URL.$query;
  }

  public function routeIsRunning($routeID, $time=null) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return false;
    }
    
    $this->updatePredictionData($routeID);

    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }
    return $this->routes[$routeID]->isRunning($time);
  }
  
  public function getRoutePaths($routeID) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }

    $route = $this->routes[$routeID];
    return $route->getPaths();
  }
  
  public function getRouteInfo($routeID, $time=null) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
    $this->updatePredictionData($routeID);

    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }
    $route = $this->routes[$routeID];

    $routeInfo = array(
      'agency'      => $route->getAgencyID(),
      'name'        => $route->getName(),
      'description' => $route->getDescription(),
      'live'        => $this->isLive(),
      'frequency'   => $route->getServiceFrequency($time),
      'running'     => $route->isRunning($time, $inService),
      'inService'   => $inService,
      'stops'       => array(),
    );

    // Check if there are a valid services and segments
    // Add a minute to the time checking so we don't tell people about buses 
    // that are leaving
    
    $seenDirections = array();
    $directions = array();
    foreach ($route->getDirections() as $direction) {
      $directionNames = array();
      $directionStops = array();

      foreach ($route->getSegmentsForDirection($direction) as $segment) {
        if (!$segment->getService()->isRunning($time)) {
          continue;
        }
        
        $segmentName = $segment->getName();
        if (isset($segmentName)) {
          $directionNames[$segment->getID()] = $segmentName;
        }

        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          $stopID = $stopInfo['stopID'];
          
          $arrivalTime = null;
          if ($stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
          }
          
          if (!isset($directionStops[$stopID])) {
            $directionStops[$stopID] = array(
              'name'      => $this->stops[$stopID]->getName(),
              'arrives'   => $arrivalTime,
              'hasTiming' => $stopInfo['hasTiming'],
            );
            if (isset($this->stops[$stopID])) {
              $directionStops[$stopID]['coordinates'] = $this->stops[$stopID]->getCoordinates();
            }
            if (isset($stopInfo['predictions'])) {
              $directionStops[$stopID]['predictions'] = $stopInfo['predictions'];
            }
            //error_log('Setting stop time to '.strftime("%H:%M:%S %Y/%m/%d", $arrives).' for '.$this->stops[$stopID]->getName());
          } else {
            $oldArrivalTime = $directionStops[$stopID]['arrives'];
            if ($arrivalTime > $time && ($arrivalTime < $oldArrivalTime || $oldArrivalTime < $time)) {
              $directionStops[$stopID]['arrives'] = $arrivalTime;
              //error_log('Replacing stop time '.strftime("%H:%M:%S %Y/%m/%d", $oldArrivalTime).' with '.strftime("%H:%M:%S %Y/%m/%d", $arrivalTime)." (".strftime("%H:%M:%S %Y/%m/%d", $time).') for stop '.$this->stops[$stopID]['name']);
            }
          }
        }
        
        $directions[$direction] = array(
          'names' => array_unique($directionNames),
          'stops' => $directionStops,
        );
      }
    }

    // Check if we can merge the directions together into one big loop
    if (count($directions) > 1) {
      $newDirections = array();
      $handled = array();
      foreach ($directions as $direction => &$info) {
        $directionStops = array_keys($info['stops']);
        $first = reset($directionStops);
        $last = end($directionStops);
        foreach ($directions as $testDirection => &$testInfo) {
          if ($direction != $testDirection && 
              !in_array($direction, $handled) && !in_array($testDirection, $handled)) {
            //error_log("Looking at directions '$direction' and '$testDirection'");
            $testDirectionStops = array_keys($testInfo['stops']);
            $testFirst = reset($testDirectionStops);
            $testLast = end($testDirectionStops);
            $stops = $info['stops'];
            $testStops = $testInfo['stops'];
            
            if (TransitDataParser::isSameStop($last, $testFirst)) {
              if ($last['arrives'] > $testFirst['arrives']) {
                TransitDataParser::removeLastStop($stops);
              } else {
                TransitDataParser::removeFirstStop($testStops);
              }
              //error_log("Collapsing '$direction' and '$testDirection'");
              $newDirections["$direction-$testDirection"] = array(
                'names' => array_unique($info['names'] + $testInfo['names']),
                'stops' => $stops + $testStops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            } else if (TransitDataParser::isSameStop($testLast, $first)) {
              if ($testLast['arrives'] > $first['arrives']) {
                TransitDataParser::removeLastStop($testStops);
              } else {
                TransitDataParser::removeFirstStop($stops);
              }
              //error_log("Collapsing '$testDirection' and '$direction'");
              $newDirections["$testDirection-$direction"] = array(
                'names' => array_unique($testInfo['names'] + $info['names']),
                'stops' => $testStops + $stops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            }
          }
        }
        if (!in_array($direction, $handled) && count($directions[$direction]['stops'])) {
          $newDirections[$direction] = $info;
        }
      }
      //error_log('NEW DIRECTIONS: '.print_r($newDirections, true));
      $directions = $newDirections;
    }

    $names = array();
    foreach ($directions as $direction => $info) {
      $routeInfo['stops'] += $info['stops'];
      $names = array_merge($names, $info['names']);
    }
    
    $routeInfo['frequency'] = round($routeInfo['frequency'] / 60, 0);
    //error_log(print_r($routeInfo, true));
    
    $this->applyRouteInfoOverrides($routeID, $routeInfo);
    
    return $routeInfo;
  }

  public function getRoutes($time=null) {
    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }

    $routes = array();
    $inService = false;
    foreach ($this->routes as $routeID => $route) {
      $this->updatePredictionData($routeID);
          
      $routes[$routeID] = array(
        'name'        => $route->getName(),
        'description' => $route->getDescription(),
        'frequency'   => round($route->getServiceFrequency($time) / 60),
        'agency'      => $route->getAgencyID(),
      );
      $routes[$routeID]['running'] = $route->isRunning($time, $inService);
      $routes[$routeID]['inService'] = $inService;
      
      $this->applyRouteInfoOverrides($routeID, $routes[$routeID]);
    }

    return $routes;
  }
  
  private function applyRouteInfoOverrides($routeID, &$routeInfo) {
    if (isset($this->args['routeFieldOverrides'])) {
      foreach ($this->args['routeFieldOverrides'] as $field => $overrides) {
        if (isset($overrides[$routeID])) {
          $routeInfo[$field] = $overrides[$routeID];
        }
      }
    }
  }
  
  public static function isSameStop($stopID, $compareStopID) {
    if ($stopID == $compareStopID) {
      return true;
    }
    if ($stopID == $compareStopID.'_ar') {
      return true;
    }
    if ($stopID.'_ar' == $compareStopID) {
      return true;
    }
    return false;
  }
  
  public static function removeLastStop(&$stops) {
    end($stops);
    unset($stops[key($stops)]);
  }
  
  public static function removeFirstStop(&$stops) {
    reset($stops);
    unset($stops[key($stops)]);
  }
}

//
// TransitTime -- compact time to reduce memory footprint
//

define('HOUR_MULTIPLIER', 10000);
define('MINUTE_MULTIPLIER', 100);

class TransitTime {   
  static public function getCurrentTime() {
    return time();
    //return strtotime("01:45:00 11/3/2010");
  }

  private static function getComponents($tt) {
    $hours = floor($tt/HOUR_MULTIPLIER);
    $minutes = floor(($tt - $hours*HOUR_MULTIPLIER)/MINUTE_MULTIPLIER); 
    $seconds = $tt - $minutes*MINUTE_MULTIPLIER - $hours*HOUR_MULTIPLIER;
    
    return array($hours, $minutes, $seconds);
  }
  
  private static function createFromComponents($hours, $minutes, $seconds) {
    if ($seconds > 59) {
      $addMinutes = floor($seconds/60);
      $minutes += $addMinutes;
      $seconds -= $addMinutes*60;
    }
    if ($minutes > 59) {
      $addHours = floor($minutes/60);
      $hours += $addHours;
      $minutes -= $addHours*60;
    }
    
    if ($hours > 23) {
      $days = floor($hours/24);
      $hours -= $days*24;
    }

    return $hours*HOUR_MULTIPLIER + $minutes*MINUTE_MULTIPLIER + $seconds;
  }
  
  public static function createFromString($timeString) {
    list($hours, $minutes, $seconds) = explode(':', $timeString);
    
    $hours = intval($hours);
    $minutes = intval($minutes);
    $seconds = intval($seconds);
    
    if ($hours > 23) {
      $days = floor($hours/24);
      $hours -= $days*24;
    }
    
    return self::createFromComponents($hours, $minutes, $seconds);
  }
  
  public static function getString($tt) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    
    return 
      str_pad($hours,   2, '0', STR_PAD_LEFT).':'.
      str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.
      str_pad($seconds, 2, '0', STR_PAD_LEFT);
  }
  
  public static function getTimestampOnDate($tt, $dateTimestamp) {
    $date = new DateTime('@'.$dateTimestamp);

    list($hours, $minutes, $seconds) = explode(':', $date->format('G:i:s'));
    $dateTT = self::createFromComponents($hours, $minutes, $seconds);
  
    if (self::compare($tt, $dateTT) < 0) {
      date_modify($date, '+1 day'); // earlier than date -- will be for the next day
    }
    
    return strtotime($date->format('Y-m-d').' '.self::getString($tt));
  }
  
  public static function compare($tt1, $tt2) {
    //error_log("Comparing ".self::getString($tt1)." to ".self::getString($tt2));
    if ($tt1 == $tt2) {
      return 0;
    } else {
      return $tt1 < $tt2 ? -1 : 1;
    }
  }
  
  public static function addSeconds(&$tt, $addSeconds) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours, $minutes, $seconds+$addSeconds);
  }
  
  public function addMinutes(&$tt, $addMinutes) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours, $minutes+$addMinutes, $seconds);
  }
  
  public function addHours(&$tt, $addHours) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours+$addHours, $minutes, $seconds);
  }
  
  public function addTime(&$tt, $addTT) {
    list($hours,    $minutes,    $seconds)    = self::getComponents($tt);
    list($addHours, $addMinutes, $addSeconds) = self::getComponents($addTT);
    
    $tt = self::createFromComponents($hours+$addHours, $minutes+$addMinutes, $seconds+$addSeconds);
  }
  
  public static function isTimeInRange($timestamp, $fromTT, $toTT) {
    $time = new DateTime('@'.$timestamp);
    $tt = TransitTime::createFromString($time->format('G:i:s'));
    
    $afterStart = TransitTime::compare($fromTT, $tt) <= 0;
    $beforeEnd  = TransitTime::compare($toTT, $tt) >= 0;
    $inRange = $afterStart && $beforeEnd;
    
    //error_log($tt->getString()." is ".($inRange ? '' : 'not ')."in range ".$this->from->getString().' - '.$this->to->getString());
    return $inRange;
  }
}

//
// Routes
//

class TransitRoute {
  private $id = null;
  private $name = null;
  private $description = null;
  private $agencyID = null;
  private $directions = array();
  private $viewAsLoop = false;
  
  function __construct($id, $agencyID, $name, $description, $viewAsLoop=false) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->agencyID = $agencyID;
    $this->viewAsLoop = $viewAsLoop;
    $this->paths = array();
  }
  
  public function getID() {
    return $this->id;
  }
    
  public function getName() {
    return $this->name;
  }
    
  public function getDescription() {
    return $this->description;
  }
    
  public function getAgencyID() {
    return $this->agencyID;
  }
    
  public function addSegment(&$segment) {
    $direction = $segment->getDirection();
  
    if (!isset($this->directions[$direction])) {
      $this->directions[$direction] = array(
        'segments' => array(),
      );
    }
    
    $segmentID = $segment->getID();
    if (isset($this->directions[$direction]['segments'][$segmentID])) {
      error_log(__FUNCTION__."(): Warning duplicate segment '$segmentID' for route '{$this->name}'");
    }
    
    $this->directions[$direction]['segments'][$segmentID] = $segment;
  }
  
  public function getDirections() {
    if ($this->viewAsLoop) {
      return array('loop');
    } else {
      return array_keys($this->directions);
    }
  }
  
  public function getSegmentsForDirection($direction) {
    if ($this->viewAsLoop) {
      $segments = array();
      foreach ($this->directions as $directionID => $direction) {
        $segments += $direction['segments'];
      }
      return $segments;
    } else {
      return $this->directions[$direction]['segments'];
    }
  }
  
  public function setStopTimes($directionID, $stopID, $arrivesOffset, $departsOffset) {
    if (!isset($this->directions[$directionID])) {
      error_log("Warning no direction $directionID for route {$this->id}");
    }
    foreach ($this->directions[$directionID]['segments'] as &$segment) {
      $segment->setStopTimes($stopID, $predictions, $arrivesOffset, $departsOffset);
    }
  }
  
  public function setStopPredictions($directionID, $stopID, $predictions) {
    if (!isset($this->directions[$directionID])) {
      error_log("Warning no direction $directionID for route {$this->id}");
    }
    foreach ($this->directions[$directionID]['segments'] as &$segment) {
      $segment->setStopPredictions($stopID, $predictions);
    }
  }
  
  public function getStops() {
    $stops = array();
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopInfo) {
          $stops[] = $stopInfo;
        }
      }
    }
    return $stops;
  }
  
  public function routeContainsStop($stopID) {
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopInfo) {
          if ($stopInfo['stopID'] == $stopID) {
            return true;
          }
        }
      }
    }
    return false;
  }
  
  public function getPredictionsForStop($stopID, $time) {
    $predictions = array(
      'running' => $this->isRunning($time),
    );
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          if ($stopInfo['stopID'] == $stopID && $stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
            
            if (!isset($predictions['arrives']) || 
                $arrivalTime < $predictions['arrives']) {
              $predictions['arrives'] = $arrivalTime;
            }
            if (isset($stopInfo['predictions'])) {
              if (!isset($predictions['predictions'])) {
                $predictions['predictions'] = array();
              }
              
              $predictions['predictions'] = array_merge(
                $predictions['predictions'], $stopInfo['predictions']);
                
              sort($predictions['predictions']);
            }
            break;
          }
        }
      }
    }

    return $predictions;
  }
  
  public function hasPredictions() {
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        if ($segment->hasPredictions()) {
          return true;
        }
      }
    }
    return false;
  }
  
  public function isRunning($time, &$inService=null, &$runningSegmentNames=null) {
    $isRunning = false;
    $inService = false;
    $runningSegmentNames = array();
    
    // Check if there is a valid segment
    $servicesForDate = null;
    
    //error_log(__FUNCTION__."(): Looking at route {$this->id} ({$this->name})");
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        //error_log("    Looking at segment $segment");
        if ($segment->getService()->isRunning($time)) {
          $inService = true;
          
          if ($segment->isRunning($time)) {
            $name = $segment->getName();
            if (isset($name) && !isset($runningSegmentNames[$name])) {
              //error_log("   Route {$this->name} has named running segment '$name' (direction '$direction')");
              $runningSegmentNames[$name] = $name;
            }
            $isRunning = true;
          }
        }
      }
    }
    
    $runningSegmentNames = array_values($runningSegmentNames);
    return $isRunning;
  }
  
  private function segmentsUseFrequencies() {
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        return $segment->hasFrequencies();
      }
    }
    return false;
  }
  
  private function getArrivalTimesForStop($time, $stopID=null) {
    $arrivalOffsets = array();
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        if ($segment->getService()->isRunning($time)) {
          $arrivalOffsets = array_merge(
            $arrivalOffsets, $segment->getArrivalTimesForStop($stopID));
        }
      }
    }
    $arrivalOffsets = array_unique($arrivalOffsets);
    sort($arrivalOffsets);
    return $arrivalOffsets;
  }
  
  public function getServiceFrequency($time, $stopID=null) {
    // Time between shuttles at the same stop
    $frequency = 0;
    
    if ($this->segmentsUseFrequencies()) {
      foreach ($this->directions as $direction) {
        foreach ($direction['segments'] as $segment) {
          if ($segment->isRunning($time)) {
            $frequency = $segment->getFrequency($time);
            if ($frequency > 0) { break; }
          }
          if ($frequency > 0) { break; }
        }
        if ($frequency > 0) { break; }
      }
    } else {
      $arrivalTimes = $this->getArrivalTimesForStop($time, $stopID);
      for ($i = 0; $i < count($arrivalTimes); $i++) {
        if ($arrivalTimes[$i] > $time) {
          if (isset($arrivalTimes[$i+1])) {
            $frequency = $arrivalTimes[$i+1] - $arrivalTimes[$i];
          } else if (isset($arrivalTimes[$i-1])) {
            $frequency = $arrivalTimes[$i] - $arrivalTimes[$i-1];
          }
        }
        if ($frequency > 0) { break; }
      }

    }
    return $frequency;
  }
  
  public function addPath($path) {
    $this->paths[] = $path;
  }
  
  public function getPaths() {
    $paths = array();
    foreach ($this->paths as $path) {
      $paths[$path->getID()] = $path->getPoints();
    }
    return $paths;
  }
}

//
// Services
//

class TransitService {
  private $id = null;
  private $dateRanges = array();
  private $exceptions = array();
  private $additions = array();
  
  private $live = false;
  
  function __construct($id, $live=false) {
    $this->id = $id;
    $this->live = $live;
  }
  
  public function getID() {
    return $this->id;
  }
  public function addDateRange($firstDate, $lastDate, $weekdays) {
    $this->dateRanges[] = array(
      'first'    => intval($firstDate),
      'last'     => intval($lastDate),
      'weekdays' => $weekdays,
    );
  }
  
  public function addExceptionDate($date) {
    $this->exceptions[] = intval($date);
  }
  
  public function addAdditionalDate($date) {
    $this->additions[] = intval($date);    
  }
  
  public function isRunning($time) {
    if ($this->live) { return true; }
  
    $date = intval(strftime('%Y%m%d', $time));
    $dayName = strftime('%A', $time);
    
    $hour = intval(strftime('%H', $time));
    if ($hour < 5) {
      $date--; // before 5am is part of the previous day
    }
    
    $insideValidDateRange = false;
    foreach ($this->dateRanges as $dateRange) {
      $week  = $dateRange['weekdays'];
      
      if ($date >= $dateRange['first'] && $date <= $dateRange['last'] && $week[strtolower($dayName)]) {
        $insideValidDateRange = true;
        break;
      }
    }
    $isException  = in_array($date, $this->exceptions);
    $isAddition   = in_array($date, $this->additions);

    //error_log("service $service is ".($isAddition || ($inValidDateRange && !$isException) ? '' : 'not ').'running');
    return $isAddition || ($insideValidDateRange && !$isException);
  }
}

//
// Segments
//

class TransitSegment {
  private $id = null;
  private $name = null;
  private $service = null;
  private $direction = null;
  private $stops = array();
  private $stopsSorted = false;
  private $frequencies = null;
  
  private $hasPredictions = false;
  
  function __construct($id, $name, $service, $direction) {
    $this->id = $id;
    $this->name = $name;
    $this->service = $service;
    $this->direction = $direction;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getName() {
    return $this->name;
  }

  public function getDirection() {
    return $this->direction;
  }

  public function getService() {
    return $this->service;
  }

  public function addFrequency($firstTT, $lastTT, $frequency) {
    if (!isset($this->frequencies)) {
      $this->frequencies = array();
    }
        
    $this->frequencies[] = array(
      'start'     => $firstTT,
      'end'       => $lastTT,
      'frequency' => intval($frequency),
    );
  }
  
  public function hasFrequencies() {
    return isset($this->frequencies);
  }
  
  public function getFrequency($time) {
    $frequency = false;
    
    if (isset($this->frequencies)) {
      foreach ($this->frequencies as $index => $frequencyInfo) {
        if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
          $frequency = $frequencyInfo['frequency'];
          break;
        } else if (!$frequency) {
          $frequency = $frequencyInfo['frequency'];
        }
      }
    }
    return $frequency;
  }
  
  public function addStop($stopID, $sequenceNumber) {
    $this->stops[] = array(
      'stopID'    => $stopID,
      'i'         => intval($sequenceNumber),
      'hasTiming' => false,
    );
    $this->stopsSorted = false;
  }
  
  private function getIndexForStop($stopID) { 
    foreach ($this->stops as $index => $stop) {
      if ($stopID == $stop['stopID']) {
        return $index;
      }
    }
    return false;
  }

  public function setStopTimes($stopID, $arrivesTT, $departsTT) {
    $index = $this->getIndexForStop($stopID);
    if ($index !== false) {
      $this->stops[$index]['arrives'] = $arrivesTT;
      $this->stops[$index]['departs'] = $departsTT;
      $this->stops[$index]['hasTiming'] = true;
    }
  }
  
  public function setStopPredictions($stopID, $predictions) {
    $index = $this->getIndexForStop($stopID);
    if ($index !== false) {
      if (!$this->hasPredictions && count($predictions)) {
        $this->hasPredictions = true;
      }
      $this->stops[$index]['predictions'] = $predictions;
      $this->stops[$index]['hasTiming'] = count($predictions) > 0;
    }
  }
  
  private static function sortStops($a, $b) {
    if ($a["i"] == $b["i"]) { 
      return 0; 
    }
    return ($a["i"] < $b["i"]) ? -1 : 1;
  }
  
  private function sortStopsIfNeeded() {
    if (!$this->stopsSorted) {
      usort($this->stops, array(get_class($this), 'sortStops'));
      $this->stopsSorted = true;
    }
  }
  
  public function getStops() {
    $this->sortStopsIfNeeded();
    return $this->stops;
  }
  
  public function hasPredictions() {
    return $this->hasPredictions;
  }
  
  public function isRunning($time) {
    $this->sortStopsIfNeeded();

    if ($this->hasPredictions) {
      return true; // live service with predictions
    
    } else if ($this->service->isRunning($time)) {
      if (isset($this->frequencies)) {
        foreach ($this->frequencies as $index => $frequencyInfo) {
          if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
            return true;
          }
        }
      } else {
        $firstStop = reset($this->stops);
        $lastStop  = end($this->stops);
        
        if (isset($firstStop['arrives'], $lastStop['departs'])) {
          if (TransitTime::isTimeInRange($time, $firstStop['arrives'], $lastStop['departs'])) {
            return true;
          }
        }
      }
    }
    
    return false;
  }
  
  public function getArrivalTimesForStop($stopID=null) {
    $arrivalTimes = array(); 
    $index = 0;
    if (isset($stopID)) {
      $index = $this->getIndexForStop($stopID);
    }
    
    if ($index !== false && isset($this->stops[$index])) {
      $now = TransitTime::getCurrentTime();
      $stop = $this->stops[$index];
      
      if (isset($stop['predictions']) && count($stop['predictions'])) {
        foreach ($stop['predictions'] as $prediction) {
          $arrivalTimes[] = $prediction + $now;
        }
      } else if (isset($stop['arrives'])) {
        $arrivalTimes[] = TransitTime::getTimestampOnDate($stop['arrives'], $now);
      }
    }
    return $arrivalTimes;
  }
  
  public function getNextArrivalTime($time, $stopIndex) {
    $this->sortStopsIfNeeded();

    $arrivalTime = 0; // noticeable error state

    $stop = $this->stops[$stopIndex];

    if (isset($this->frequencies)) {
      $firstFrequency = reset($this->frequencies);
      
      $firstLoopStopTime = $firstFrequency['start'];
      TransitTime::addTime($firstLoopStopTime, $stop['arrives']);
      
      $arrivalTime = TransitTime::getTimestampOnDate($firstLoopStopTime, $time);
      //error_log("Stop {$stop['stopID']} default arrival time will be ".$firstLoopStopTime->getString()." start is ".$firstFrequency['range']->getStart()->getString()." offset is ".$stop['arrives']->getString());

      $foundArrivalTime = false;
      foreach ($this->frequencies as $frequencyInfo) {
        $currentTT = $frequencyInfo['start']; // loop start
        TransitTime::addTime($currentTT, $stop['arrives']); // stop offset from loop start
        
        while (TransitTime::compare($currentTT, $frequencyInfo['end']) <= 0) {
          $testTime = TransitTime::getTimestampOnDate($currentTT, $time);
          //error_log("Looking at ".$currentTT->getString()." is ".($testTime > $time ? 'after now' : 'before now'));
          if ($testTime > $time && (!$foundArrivalTime || $testTime < $arrivalTime)) { 
            $arrivalTime = $testTime; 
            $foundArrivalTime = true;
            break;
          }
          TransitTime::addSeconds($currentTT, $frequencyInfo['frequency']);
        }
      }
      
    } else if ($this->hasPredictions && count($stop['predictions'])) {
      $now = TransitTime::getCurrentTime();
      
      foreach ($stop['predictions'] as $prediction) {
        $testTime = $now + $prediction;
        if ($testTime > $time) {
          $arrivalTime = $testTime;
          break;
        }
      }
    
    } else if (isset($stop['arrives'])) { 
      $arrivalTime = TransitTime::getTimestampOnDate($stop['arrives'], $time);
    }
    
    return $arrivalTime;
  }
}

//
// Stops
//

class TransitStop {
  private $id = null;
  private $name = null;
  private $description = null;
  private $latitude = null;
  private $longitude = null;
  
  function __construct($id, $name, $description, $latitude, $longitude) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->latitude = floatVal($latitude);
    $this->longitude = floatVal($longitude);
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getCoordinates() {
    return array(
      'lat' => $this->latitude, 
      'lon' => $this->longitude,
    );
  }
}


//
// Paths
//

class TransitPath {
  private $id = null;
  private $points = array();
  
  function __construct($id, $points) {
    $this->id = $id;
    
    $pathPoints = array();
    foreach ($points as &$point) {
      $pathPoints[] = array(
        'lat' => floatVal(reset($point)),
        'lon' => floatVal(end($point)),
      );
    }
    $this->points = $pathPoints;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getPoints() {
    return $this->points;
  }
}
