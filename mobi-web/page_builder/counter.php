<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . 'home/Modules.php';
require_once LIBDIR . 'db.php';
define("PAGE_VIEWS_TABLE", 'mobi_web_page_views');

class PageViews {

  public static function increment($module, $platform, $time=NULL) {
    if($platform == 'spider') {
      // do not count spiders as page views
      return;
    }

    if ($time === NULL)
      $time = time();

    $db = db::$connection;
    $today = date('Y-m-d', $time);

    // if a $platform device already accessed $module, increment viewcount by 1
    // otherwise create row with viewcount 1
    $row = self::getTimeSeries($today, $platform, $module);
    $db->query('LOCK TABLE ' . PAGE_VIEWS_TABLE . ' WRITE');
    if(!$row) {
      $sql = 'INSERT INTO ' . PAGE_VIEWS_TABLE . ' (day, platform, module, viewcount)' .
	" VALUES ('$today', '$platform', '$module', 1)";
    } else {
      $current_cnt = $row[0]['viewcount'] + 1;
      $sql = 'UPDATE ' . PAGE_VIEWS_TABLE . ' SET viewcount=' . $current_cnt .
	" WHERE day='$today' AND platform='$platform' AND module='$module'";
    }

    //debug($sql);
    $db->query($sql);

    $db->query("UNLOCK TABLE");
  }

  /* get total viewcount for platform $platform (default all platforms),
   * module $module (default all modules),
   * between dates $start and $end (any string compatible with strtotime)
   */
  private static function getTimeSeries($start, $platform=NULL, $module=NULL, $end=NULL) {
    $db = db::$connection;
    $sql_fields = Array();
    if (($end === NULL) || (strtotime($end) - strtotime($start) == 86400)) {
      $sql_criteria = Array("day='$start'");
    } else {
      $sql_criteria = Array("day >= '$start' AND day < '$end'");
      $groupby = Array();
    }

    if ($platform !== NULL) {
      $sql_criteria[] = "platform='$platform'";
    } else {
      $sql_fields[] = 'platform';
    }

    if ($module !== NULL) {
      $sql_criteria[] = "module='$module'";
    } else {
      $sql_fields[] = 'module';
    }

    if (count($sql_fields) == 2 && !isset($groupby)) {
      $sql_fields[] = 'viewcount';
    } else {
      $groupby = $sql_fields;
      $sql_fields[] = 'SUM(viewcount) AS viewcount';
    }
    $sql = "SELECT " . implode(', ', $sql_fields);
    array_pop($sql_fields);
    $sql .= ' FROM ' . PAGE_VIEWS_TABLE . ' WHERE ' . implode(' AND ', $sql_criteria);
    $sql .= (isset($groupby) && count($groupby)) ? ' GROUP BY ' . implode(', ', $groupby) : '';

    //var_dump($sql);
    $result = $db->query($sql);

    $output = Array();
    // results are returned as (not necessarily in this order):
    // Array('module' => ..., 'platform' => ..., 'viewcount' => ...)
    // one row per platform/module combo
    while($row = $result->fetch_assoc()) {
      $output[] = $row;
    }

    if (count($output) == 1 && $output[0]['viewcount'] === NULL) {
      return NULL;
    }
    return $output;
  }

  public static function view_past($time_unit, $duration) {
    $increments = Array(0); // the first element never gets used

    // figure out value to use for $begin in sql query
    // and number of seconds for each $increment
    $time = time();
    switch ($time_unit) {
    case 'day':
      $begin = $time - $duration * 86400;
      $increments = array_pad($increments, $duration + 1, 86400);
      break;
    case 'week':
      $begin = $time - $duration * 86400 * 7;
      $increments = array_pad($increments, $duration + 1, 86400 * 7);
      break;
    case 'month':
      $month = date('n', $time) + 1; // start from 11 months ago
      $year = date('Y', $time);
      if ($month == 13) {
	$begin = mktime(0, 0, 0, 1, 1, $year);
      } else {
	$begin = mktime(0, 0, 0, $month, 1, $year - 1);
      }
      $last_begin = $begin;
      for ($i = 1; $i <= $duration; $i++) {
	if ($month + $i > 12) {
	  $next_begin = mktime(0, 0, 0, $month + $i - 12, 1, $year);
	} else {
	  $next_begin = mktime(0, 0, 0, $month + $i, 1, $year - 1);
	}
	$increments[] = $next_begin - $last_begin;
	$last_begin = $next_begin;
      }
      break;
    }

    $views = Array();
    for ($i = 0; $i < $duration; $i++) {
      $sql_start_date = date('Y-m-d', $begin);
      $end = $begin + $increments[$i + 1];
      $sql_end_date = date('Y-m-d', $end);

      $new_view = Array('date' => $begin, 'total' => 0);

      // array below has index for each module, bucket
      // and the index 'day' for the day or first day of week/month
      $results = self::getTimeSeries($sql_start_date, NULL, NULL, $sql_end_date);
      foreach ($results as $row) {
	if (array_key_exists('platform', $row)) {
          if (!array_key_exists($row['platform'], $new_view))
            $new_view[$row['platform']] = 0;
	  $new_view[$row['platform']] += $row['viewcount'];
        }
	if (array_key_exists('module', $row)) {
          if (!array_key_exists($row['platform'], $new_view))
            $new_view[$row['platform']] = 0;
	  $new_view[$row['module']] += $row['viewcount'];
        }
	$new_view['total'] += $row['viewcount'];
      }
      $views[] = $new_view;
      $begin = $end;
    }
    return $views;
  }

}

?>
