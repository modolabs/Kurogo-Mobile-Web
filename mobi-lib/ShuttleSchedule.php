<?php

/* this file interacts with the published shuttle schedule
 * data is entered in the file shuttle_schedule.json
 *
 * note to php devs: the functions 
 * assert_exists, get_route_info, get_route_list, and get_stop_list
 * are similar to NextBusReader
 * still considering making these children classes of the same parent
 * or implementations of the same interface
 *
 */
$docRoot = getenv("DOCUMENT_ROOT");

define("SHUTTLE_SCHEDULE_SOURCE", LIBDIR . 'shuttle_schedule.json');

/* constants used in constants.php: TIMEZONE */
require_once($docRoot . '/mobi-config/mobi_lib_constants.php');
require_once(LIBDIR . 'TimeRange.php');
require_once(LIBDIR . 'datetime_lib.php');
require_once(LIBDIR . 'holiday_data.php');

class ShuttleSchedule {

  private static $routes = Array();

  private static function assert_exists($routeName) {
    self::init();
    if (!array_key_exists($routeName, self::$routes)) {
      throw new Exception("invalid route name '$routeName'");
    }
  }

  // this function does as much work as possible
  // to populate the static data
  public static function init() {
    if (!self::$routes) {
      $routes = json_decode(file_get_contents(SHUTTLE_SCHEDULE_SOURCE), TRUE);
      foreach ($routes as $route => $routeInfo) {
	// most fields can just be takend directly from json data
	self::$routes[$route] = Array(
          'title' => $routeInfo['title'],
	  'nextBusId' => $routeInfo['nextBusId'],
	  'summary' => $routeInfo['summary'],
	  'interval' => $routeInfo['interval'] * 60,
	  'stops' => $routeInfo['stops'],
	  );

	if (array_key_exists('isSafeRide', $routeInfo))
	  self::$routes[$route]['isSafeRide'] = $routeInfo['isSafeRide'];
	if (array_key_exists('excludeHolidays', $routeInfo))
	  self::$routes[$route]['excludeHolidays'] = $routeInfo['excludeHolidays'];
	if (array_key_exists('smsTitle', $routeInfo))
	  self::$routes[$route]['smsTitle'] = $routeInfo['smsTitle'];

	// replace 'runs' field with extrapolated list of day-by-day runs
	$runs = Array();
	foreach ($routeInfo['runs'] as $run) {
	  foreach ($run['days'] as $day) {
	    if (!array_key_exists($day, $runs))
	      $runs[$day] = Array();
	    $first = seconds_since_midnight($run['first']);
	    $last = seconds_since_midnight($run['last']);
	    if ($last < $first)
	      $last += 86400; // run ends past midnight, e.g. 1am or 2am 
	    $runs[$day][] = Array('first' => $first, 'last' => $last);
	  }
	}
	self::$routes[$route]['runs'] = $runs;

	// replace human-readable 'dates' field with unix times
	$dates = Array();
	foreach ($routeInfo['dates'] as $datestr) {
	  $dateparts = explode('-', $datestr);
	  $start = day_of(strtotime($dateparts[0]), TIMEZONE); // see datetime_lib.php
	  $end = day_of(strtotime($dateparts[1]), TIMEZONE) + $routeInfo['interval'] * 60;

	  // shuttles don't end at 12am of the ending date -- find out actual ending time
	  $endDay = date('D', $end);
	  if (array_key_exists($endDay, $runs)) {
	    $runEnds = Array();
	    foreach ($runs[$endDay] as $run) {
	      $runEnds[] = $run['last'];
	    }
	    $end += max($runEnds);
	  }
	  // date ranges for when the shuttle is scheduled to run
	  $dates[] = new TimeRange($start, $end);
	}
	self::$routes[$route]['dates'] = $dates;

      }
    }
  }

  public static function get_route_list() {
    self::init();
    return array_keys(self::$routes);
  }

  public static function is_running($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    if (!self::is_running_today($routeName, $time))
      return FALSE;
    // test whether the first run after now
    // is more than $interval seconds in the future
    return (min(self::get_next_scheduled_loop_start($routeName, $time)) - $time <= self::get_interval($routeName));
  }

  public static function is_running_today($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    $routeInfo = self::get_route_info($routeName);
    if (array_key_exists('excludeHolidays', $routeInfo)
	&& (is_holiday($time) && is_holiday($time + 86400)))
      return FALSE;

    // for year-round shuttles
    if (count($routeInfo['dates']) == 0)
      return TRUE;

    // shuttles with limite date ranges
    foreach ($routeInfo['dates'] as $dateRange) {
      // allow them to view times up to 1 day from now
      if ($dateRange->contains_point($time) || $dateRange->contains_point($time + 86400))
	return TRUE;
    }
    return FALSE;
  }

  public static function get_active_routes() {
    return array_filter(self::get_route_list(), Array('self', 'is_running_today'));
  }

  public static function get_route_info($routeName) {
    self::assert_exists($routeName);
    return self::$routes[$routeName];
  }

  public static function get_interval($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return $routeInfo['interval'];    
  }

  public static function is_safe_ride($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return array_key_exists('isSafeRide', $routeInfo);
  }

  public static function get_summary($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return $routeInfo['summary'];
  }

  public static function get_title($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return $routeInfo['title'];
  }

  public static function get_sms_title($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if (array_key_exists('smsTitle', $routeInfo))
      return $routeInfo['smsTitle'];
    return $routeInfo['title'];
  }

  public static function get_nextbus_id($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return $routeInfo['nextBusId'];
  }

  public static function get_stop_list($routeName) {
    $routeInfo = self::get_route_info($routeName);
    return $routeInfo['stops'];
  }

  // functions for upcoming schedule

  private static function get_next_scheduled_loop_start($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    if (!self::is_running_today($routeName, $time)) {
      warn("route $routeName is not currently available");
      return;
    }

    $routeInfo = self::get_route_info($routeName);
    $interval = self::get_interval($routeName);
    $sSinceMidnight = $time - day_of($time, TIMEZONE);
    $day = date('D', $time);
    // use case for people checking saferides after midnight
    if (array_key_exists('isSafeRide', $routeInfo) && $sSinceMidnight < 5 * 3600) {
      $sSinceMidnight += 86400;
      $day = date('D', $time - 86400);
    }

    $nextStarts = Array();
    if (array_key_exists($day, $routeInfo['runs'])) {
      $runsToday = $routeInfo['runs'][$day];
      foreach ($runsToday as $run) {
	if ($run['first'] >= $sSinceMidnight || $run['last'] + $interval < $sSinceMidnight)
	  continue;
	$sSinceStart = $sSinceMidnight - $run['first'];
	$sSinceLastRun = $sSinceStart % $interval;

	// let get_next_scheduled_loop figure out this is expired
	$nextStarts[] = ($sSinceStart - $sSinceLastRun + $interval < $run['last']) ?
	  $time - $sSinceLastRun + $interval : $time - $sSinceLastRun;
      }
    }
    if (count($nextStarts) == 0) {
      for ($i = 0; $i < 7; $i++) {
	$time += 86400;
	$day = date('D', $time);
	if (array_key_exists($day, $routeInfo['runs'])) {
	  $runs = $routeInfo['runs'][$day];
	  $runStarts = Array();
	  foreach ($runs as $run) {
	    $runStarts[] = $run['first'];
	  }
	  $nextStart = day_of($time, TIMEZONE) + min($runStarts);
	  break;
	}
      }
      if ($nextStart && self::is_running_today($routeName, $time))
	$nextStarts[] = $nextStart;      
    }
    return $nextStarts;
  }

  public static function get_next_scheduled_loop($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    $nextStarts = self::get_next_scheduled_loop_start($routeName, $time);
    $stopList = self::get_stop_list($routeName);
    $interval = self::get_interval($routeName);
    $loopInfo = Array();
    foreach ($stopList as $stop) {
      $stopInfo = Array(
        'title' => $stop['title'],
	'nextBusId' => $stop['nextBusId'],
	);
      $nextTimes = Array();
      foreach ($nextStarts as $nextStart) {
	$nextTime = $nextStart + $stop['offset'] * 60;
	if ($nextTime - $interval > $time && day_of($nextTime, TIMEZONE) == day_of($time, TIMEZONE))
	  $nextTime -= $interval;
	$nextTimes[] = $nextTime;
      }
      $nextTime = min($nextTimes);
      $stopInfo['nextScheduled'] = ($nextTime < $time) ? 'finished' : $nextTime;
      $loopInfo[] = $stopInfo;
    }
    return $loopInfo;
  }

  public static function get_next_scheduled_loops($routeName, $time=NULL, $limit=3) {
    if ($time === NULL)
      $time = time();
    $interval = self::get_interval($routeName);
    $loopInfo = self::get_next_scheduled_loop($routeName, $time);
    foreach ($loopInfo as $index => $loop) {
      $loopInfo[$index]['nextScheduled'] = Array($loop['nextScheduled']);
    }
    $limit -= 1;
    $time += $interval;
    while ($limit > 0) {
      foreach (self::get_next_scheduled_loop($routeName, $time) as $index => $nextLoop) {
	$loopInfo[$index]['nextScheduled'][] = $nextLoop['nextScheduled'];
      }
      $time += $interval;
      $limit -= 1;
    }
    return $loopInfo;
  }

  public static function get_next_scheduled_time($routeName, $stopName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    $nextStart = self::get_next_scheduled_loop_start($routeName, $time);
    $stopList = self::get_stop_list($routeName);
    $interval = self::get_interval($routeName);
    foreach ($stopList as $stopInfo) {
      if ($stopName == $stopInfo['title']) {
	$nextTime = $nextStart + $stopInfo['offset'] * 60;
	if ($nextTime - $interval > $time)
	  $nextTime -= $interval;
	return $nextTime;
      }
    }
  }

}

?>
