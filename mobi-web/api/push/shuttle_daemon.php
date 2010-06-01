#!/usr/bin/php
<?
/**** 
 * this daemon regularly polls for stop predictions on each active route
 * doing this keeps caches fresh as a side effect
 * though this isn't the most logical way to keep caches fresh
 *
 * we will check for route-stop subscriptions and notify users
 * whose predictions are below SHUTTLE_NOTIFY_THRESHOLD
 */

define("SHUTTLE_NOTIFY_THRESHOLD", 320);

require_once("DaemonWrapper.php");

$daemon = new DaemonWrapper("shuttle");
$daemon->start($argv);


require_once dirname(__FILE__) . "/../../../mobi-config/mobi_lib_constants.php";
require_once LIB_ROOT . "ShuttleSchedule.php";
require_once LIB_ROOT . "NextBusReader.php";
require_once LIB_ROOT . "db.php";
require_once "apns_lib.php";

ShuttleSchedule::init();
NextBusReader::init();

$all_routes = ShuttleSchedule::get_route_list();

while ($daemon->sleep(10)) {
  db::ping(); // keep the database connection running

  $time = time();
  $too_old = $time - 7200; // arbitrary 2 hour timeout threshold for now

  // only keep track of shuttles that are running
  $routes = array_filter($all_routes, array('ShuttleSchedule', 'is_running'));

  if (count($routes)) {
    foreach ($routes as $route) {
      // force NextBusReader to cache
      NextBusReader::get_predictions($route);
      NextBusReader::get_coordinates($route);
    }

    $sql = "SELECT device_id, device_type, route_id, stop_id, start_time FROM ShuttleSubscription WHERE ("
      . implode(' OR ', array_map('wrap_route_criterion', $routes))
      .     ") AND start_time <= $time AND start_time > $too_old";

    if (!$result = db::$connection->query($sql)) {
      d_error("sql failed: {$db->errno} {$db->error} in $sql");
    } else {
      while ($row = $result->fetch_assoc()) {
	$route_id = $row['route_id'];

	// skip rows whose start times are more than 1.5 loops ago
	if ($time - $row['start_time'] > 1.5 * ShuttleSchedule::get_interval($route_id))
	  continue;

	$stop_id = $row['stop_id'];
	$next_seconds = -1;
	$source = 'null';

	if ($route_preds = NextBusReader::get_predictions($route_id)) {
	  $stop_preds = $route_preds[$stop_id];
	  if ($stop_preds) {
	    $source = 'nextbus';
	    $next_seconds = $stop_preds[0];
	    $next_time = $time + $next_seconds;
	  }
	} else {
	  $stop_times = ShuttleSchedule::get_next_scheduled_loop($route_id, $row['start_time']);
	  foreach ($stop_times as $stop_time) {
	    if ($stop_time['nextBusId'] == $stop_id) {
	      $source = 'schedule';
	      $next_time = $stop_time['nextScheduled'];
	      $next_seconds = $next_time - $time;
	      $stopname = $stop_time['title'];
	      break;
	    }
	  }
	}

	if ($next_seconds > 0 && $next_seconds < SHUTTLE_NOTIFY_THRESHOLD) {
	  $shuttle = ShuttleSchedule::get_title($route_id);
	  $minutes = intval($next_seconds / 60);
	  $timestr = date('g:ia', $next_time);

	  switch ($source) {
	  case 'nextbus':
	    $stopname = ShuttleSchedule::get_stop_title($route_id, $stop_id);
	    $message = "$shuttle arriving at $stopname in $minutes minutes ($timestr)";

	    break;
	  case 'schedule':
	    // $stopname was defined earlier
	    $message = "$shuttle (NOT GPS TRACKED) scheduled to arrive at $stopname in $minutes minutes ($timestr)";
	    break;
	  }

	  //$stopname = ShuttleSchedule::get_stop_title($route_id, $stop_id);
	  switch ($row['device_type']) {
	  case 'apple':
	    $aps = array('aps' => 
			 array('alert' => $message,
			       'sound' => 'default'));
	    APNS_DB::create_notification($row['device_id'], "shuttletrack:$route_id:$stop_id", $aps, False);
	    break;
	  }

	  // make sure to unsubscribe this person so they don't 
	  // get a message every 10 seconds until their subscription times out
	  $sql = "DELETE FROM ShuttleSubscription "
	    .     "WHERE device_id='" . $row['device_id'] . "' "
	    .       "AND device_type='" . $row['device_type'] . "' "
	    .       "AND route_id='" . $route_id . "' "
	    .       "AND stop_id='" . $stop_id . "'";
	  if (!db::$connection->query($sql)) {
	    d_error("unsubscribe failed: {$db->errno} {$db->error} in $sql");
	  }

	} // if

      } // while
    } // else
  }

  /*
  // purge all subscriptions that have expired
  $sql = "SELECT * FROM ShuttleSubscription WHERE expire_time < $time";
  if (!$result = db::$connection->query($sql)) {
    d_error("sql failed: {$db->errno} {$db->error} in $sql");
  } else {
    while ($row = $result->fetch_assoc()) {
      d_echo('deleting expired subscription with fields: ' . serialize($row));
    }
    $sql = "DELETE FROM ShuttleSubscription WHERE expire_time < $time";
    if (!db::$connection->query($sql))
      d_error("sql failed: {$db->errno} {$db->error} in $sql");
  }
  */

}

$daemon->stop();

function wrap_route_criterion($route) {
  return "route_id='$route'";
}

?>
