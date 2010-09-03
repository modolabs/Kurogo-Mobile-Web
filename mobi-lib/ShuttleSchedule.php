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
require_once $docRoot . "/mobi-config/mobi_lib_constants.php";

define("SHUTTLE_SCHEDULE_SOURCE", LIBDIR . 'shuttle_schedule.json');

/* constants used in constants.php: TIMEZONE */
require_once 'TimeRange.php';
require_once 'datetime_lib.php';
require_once 'AcademicCalendar.php';

class ShuttleSchedule {

  private static $routes = Array();

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
    if (!self::is_running_today($routeName, $time, TRUE))
      return FALSE;
    // test whether the first run after now
    // is more than $interval seconds in the future

    $next_starts = self::get_next_scheduled_loop_start($routeName, $time);
    if (count($next_starts) == 0) {
      return FALSE;
    }
    $next_start = min($next_starts);
    return ($next_start - $time <= self::get_interval($routeName));
  }

  public static function is_running_today($routeName, $time=NULL, $strict=FALSE) {
    if ($time === NULL)
      $time = time();
    $routeInfo = self::get_route_info($routeName);
    if (!$routeInfo)
      return FALSE;

    if (array_key_exists('excludeHolidays', $routeInfo)) {
      if (AcademicCalendar::is_holiday($time))
        if ($strict || AcademicCalendar::is_holiday($time + 86400))
	  return FALSE;
    }

    // for year-round shuttles
    if (count($routeInfo['dates']) == 0)
      return TRUE;

    // shuttles with limite date ranges
    foreach ($routeInfo['dates'] as $dateRange) {
      // allow them to view times up to 1 day from now
      if ($dateRange->contains_point($time) 
	  || (!$strict && $dateRange->contains_point($time + 86400)))
	return TRUE;
    }
    return FALSE;
  }

  public static function get_active_routes() {
    return array_filter(self::get_route_list(), Array('self', 'is_running_today'));
  }

  public static function get_route_info($routeName) {
    if (array_key_exists($routeName, self::$routes)) {
      return self::$routes[$routeName];
    }
    return NULL;
  }

  public static function get_interval($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      return $routeInfo['interval'];    
    }
  }

  public static function count_shuttles_running($routeName, $time) {
    $runsToday = self::get_runs_today($routeName, $time);
    $midnight = day_of($time, TIMEZONE);
    $numshuttles = 0;
    foreach ($runsToday as $run) {
      $timeRange = new TimeRange($midnight + $run['first'], $midnight + $run['last']);
      if ($timeRange->contains_point($time))
	$numshuttles++;
    }
    return $numshuttles;
  }

  public static function is_safe_ride($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      return array_key_exists('isSafeRide', $routeInfo);
    }
  }

  public static function get_summary($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      return $routeInfo['summary'];
    }
  }

  public static function get_title($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      return $routeInfo['title'];
    }
  }

  public static function get_stop_title($routeName, $stopName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      foreach ($routeInfo['stops'] as $stop) {
	if ($stop['nextBusId'] == $stopName) {
	  return $stop['title'];
	}
      }
    }
  }

  public static function get_sms_title($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      if (array_key_exists('smsTitle', $routeInfo))
	return $routeInfo['smsTitle'];
      return $routeInfo['title'];
    }
  }

  public static function get_stop_list($routeName) {
    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo !== NULL) {
      return $routeInfo['stops'];
    }
  }

  // functions for upcoming schedule

  private static function get_runs_today($routeName, $time) {
    $runsToday = Array();
    if (self::is_running_today($routeName, $time, TRUE)) {
      $routeInfo = self::get_route_info($routeName);
      if ($routeInfo !== NULL) {
	$sSinceMidnight = $time - day_of($time, TIMEZONE);
	$day = date('D', $time);
	if (array_key_exists($day, $routeInfo['runs'])) {
	  $runsToday = $routeInfo['runs'][$day];
	}
      }
    }

    return $runsToday;
  }

  public static function get_last_run($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    $runsToday = self::get_runs_today($routeName, $time);
    $last = 0;
    foreach ($runsToday as $run) {
      if ($run['last'] > $last)
	$last = $run['last'];
    }
    return $last + day_of($time, TIMEZONE);
  }

  private static function get_next_scheduled_loop_start($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();

    $routeInfo = self::get_route_info($routeName);
    if ($routeInfo === NULL)
      return NULL;

    $interval = self::get_interval($routeName);
    $sSinceMidnight = $time - day_of($time, TIMEZONE);
    // use case for people checking saferides after midnight
    if (array_key_exists('isSafeRide', $routeInfo) && $sSinceMidnight < 5 * 3600) {
      $sSinceMidnight += 86400;
      $runsToday = self::get_runs_today($routeName, $time - 86400);
    } else {
      $runsToday = self::get_runs_today($routeName, $time);
    }

    $nextStarts = Array();
    foreach ($runsToday as $run) {
      if ($run['last'] + $interval < $sSinceMidnight)
	continue;
      $sSinceStart = $sSinceMidnight - $run['first'];

      if ($sSinceStart < 0) { // route has not started for the day
	$nextStarts[] = day_of($time, TIMEZONE) + $run['first'];
      } else if ($sSinceStart < $run['last']) {
	$nextStarts[] = $time - ($sSinceStart % $interval) + $interval;
      } // otherwise, route is done for the day
    }

    if (count($nextStarts) == 0) { // route doesn't run today, get earliest day
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
      if ($nextStart && self::is_running_today($routeName, $time, TRUE))
	$nextStarts[] = $nextStart;
    }
    return $nextStarts;
  }

  public static function get_next_scheduled_loop($routeName, $time=NULL) {
    if ($time === NULL)
      $time = time();
    $nextStarts = self::get_next_scheduled_loop_start($routeName, $time);
    if (!$nextStarts)
      return array();

    $stopList = self::get_stop_list($routeName);
    $interval = self::get_interval($routeName);
    $loopInfo = Array();
    foreach ($stopList as $stop) {
      $stopInfo = Array(
        'title' => $stop['title'],
	'nextBusId' => $stop['nextBusId'],
        'smsTitle' => $stop['smsTitle'],
	);
      $nextTimes = Array();
      foreach ($nextStarts as $nextStart) {
	$nextTime = $nextStart + $stop['offset'] * 60;
	if ($nextTime - $interval > $time 
	    && $nextTime - 2 * $interval <= $time
	    && $nextTime - $interval > $nextStart)
	  $nextTime -= $interval;
	$nextTimes[] = $nextTime;
      }
      $nextTime = (count($nextTimes)) ? min($nextTimes) : 0;
      $stopInfo['nextScheduled'] = ($nextTime < $time) ? 0 : $nextTime;
      $loopInfo[] = $stopInfo;
    }
    return $loopInfo;
  }

  /* not sure if any of the functions after this are being used
   * should make sure at some point 
   */

  public static function get_next_scheduled_loops($routeName, $time=NULL, $limit=3) {
    if ($time === NULL)
      $time = time();
    $interval = self::get_interval($routeName);
    if (!$interval)
      return array();

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
    if (!$nextStart)
      return FALSE;

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
