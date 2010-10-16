<?php

require_once WEBROOT . "/home/Modules.inc";

$platforms = Array(
    'iphone' => 'iPhone',
    'android' => 'Android',
    'webos' => 'webOS',
    'winmo' => 'Windows Mobile',
    'blackberry' => 'BlackBerry',
    'bbplus' => 'Advanced BlackBerry',
    'symbian' => 'Symbian',
    'palmos' => 'Palm OS',
    'featurephone' => 'Other Phone',
    'computer' => 'Computer',
  );

$service_types = Array('web' => 'Website', 'api' => 'Native App');
$interval_types = Array(
  'day' => Array('duration' => 7, 'title' => 'Week', 'numdays' => 7),
  'week' => Array('duration' => 12, 'title' => '12 Weeks', 'numdays' => 84),
  'month' => Array('duration' => 12, 'title' => 'Year', 'numdays' => 365),
  'quarter' => Array('duration' => 12, 'title' => '3 Years', 'numdays' => 1095),
  );

// default params
$url_params = Array('page' => 'statistics', 'service' => 'web', 'interval' => 'day');

foreach ($url_params as $param => $value) {
  if(isset($_GET[$param])) {
    $url_params[$param] = $_GET[$param];
  }
}

$duration = $interval_types[$url_params['interval']]['duration'];

$all_data = PageViews::view_past($url_params['service'], $url_params['interval'], $duration);
if ($url_params['service'] == 'web') {
  $graphs = array(
    summary_total($all_data, "total", "total page views"),
    trend($all_data, "total", 
	    'Page Views by ' . ucfirst($url_params['interval']), 
	    $url_params['interval']),
    bar_percentage( platform_data($all_data), "Traffic by Platform"),
    list_items(generate_popular_content('web', $all_data), "Most Popular Content", "page views"),
    );
} else { // api
  $graphs = array(
    //summary_total(PageViews::count_iphone_tokens(), "total", "active users"),
    summary_total($all_data, "total", "total API requests"),
    trend($all_data, "total", 
	    'API Requests by ' . ucfirst($url_params['interval']), 
	    $url_params['interval']),
    list_items(generate_popular_content('api', $all_data), "Most Popular Modules", "requests"),
    );
}

$name = $service_types[$url_params['service']];

// set states of fake segmented control
$statclasses = Array();
foreach ($interval_types as $type => $attrs) {
  $stclass = Array();
  $stclass['interval'] = $type;
  if ($url_params['interval'] == $type) {
    $stclass['active'] = ' class="active"';
  } else {
    $stclass['active'] = '';
  }
  $stclass['title'] = $attrs['title'];
  $statclasses[$type] = $stclass;
}

require "$page->branch/statistics.html";
$page->output();

/* web functions */

function generate_popular_content($system, $data) {
  $viewcounts = array();
  if ($system == 'web') {
    $modules = array();
    foreach (Modules::$module_data as $module => $mdata) {
      $modules[$module] = $mdata['title'];
    }
  } else { // api
    $modules = array(
      "people" => "Directory",
      "map" => "Map", 
      "calendar" => "Events",
      "courses" => "Courses", 
      "news" => "News",
      "dining" => "Dining",
      "shuttles" => "ShuttleTracker",
      );
  }

  foreach ($modules as $module => $title) {
    $viewcounts[$module] = 0;
  }

  foreach($data as $datum) {
    foreach ($datum as $field => $count) {
      if (array_key_exists($field, $viewcounts))
	$viewcounts[$field] += $count;
    }
  }

  $popular_pages = Array();
  foreach ($viewcounts as $module => $count) {
    $module_stats = array(
      'name' => $modules[$module],
      'count' => $count,
      );
    if ($system == 'web') {
      $module_stats['name'] = Modules::title($module);
      $module_stats['link'] = Modules::url($module);
    }

    $popular_pages[] = $module_stats;
  }
  return $popular_pages;
}

/* general */

function compare_content($content1, $content2) {
  if($content1['count'] < $content2['count']) {
    return 1;
  }
  if($content1['count'] > $content2['count']) {
    return -1;
  }
  return 0;
}

function per_cent($part, $total) {
  return round(100 * $part / $total);
}

function determine_scale($values, $field) {
  // find the largest number of views in the days
  $max_views = 0;
  foreach($values as $datum) {
    if($datum[$field] > $max_views) {
      $max_views = $datum[$field];
    }
  }

  // determine the maximum to use for the bar graph
  $limits = array(1, 2, 4, 5);

  $found = False;
  $scale = 10;
  while(!$found) {
    foreach($limits as $limit) {
      if($limit * $scale > $max_views) {
        $max_scale = $limit * $scale;
        $found = True;
        break;
      }
    }
    $scale *= 10;
  }  
  return $max_scale;
}

function format_intervals($data, $max_scale, $field, $interval_type) {
  $intervals = array();
  foreach($data as $datum) {
    $new_interval = Array();
    $new_interval['day'] = date('D', $datum['date']);
    if (($interval_type != 'day') && ($max_scale > 1000)) {
      $num_digits = min(2, max(0, 6 - strlen($datum[$field])));
     $new_interval['count'] = number_format($datum[$field]/1000, $num_digits);
     } else {
      $new_interval['count'] = $datum[$field];
    }
    $new_interval['percent'] = per_cent($datum[$field], $max_scale);
    switch ($interval_type) {
    case 'day':
      $new_interval['date'] = date('n/j', $datum['date']);
      break;
    case 'week':
      $new_interval['date'] = date('n/j/Y', $datum['date']);
      break;
    case 'month':
      $new_interval['date'] = date('M', $datum['date']);
      break;
    case 'quarter':
      $new_interval['date'] = 'Q' . ((date('n', $datum['date']) + 2) / 3) . date("\ny", $datum['date']);
      break;
    }

    $intervals[] = $new_interval;
  }
  return $intervals;
}

function summary_total($data, $field, $title) { 
  $total = 0;
  foreach($data as $datum) {
    if (is_array($datum)) {
      $total += $datum[$field];
    } else {
      $total += (int)$datum;
    }
  }
  return array("type"=>"TOTAL", "title"=>$title, "total"=>$total);
}

function trend($data, $field, $title, $interval_type) {
  $max_scale = determine_scale($data, $field);
  if (($interval_type != 'day') && ($max_scale > 1000)) {
    $title = '1000s of ' . $title;
  }
  return array(
    "type" => "TREND",
    "days" => format_intervals($data, $max_scale, $field, $interval_type),
    "title" => $title,
  );
}

function bar_percentage($data, $title) {
  $new_data = array();
  $total = array_sum(array_values($data));
  foreach($data as $key => $count) {
    $new_data[$key] = per_cent($count, $total);
  }

  return array(
    "type" => "BAR-PERCENTAGE",
    "data" => $new_data,
    "title" => $title,
  );
}

function list_items($data, $title, $label) {
  usort($data, 'compare_content');
  $data = array_slice($data, 0, 10);
  return array("type"=>"LIST-ITEMS", "data"=>$data, "title"=>$title, "label"=>$label);
}
			
function platform_data($data) {
  global $platforms;

  // views by device
  $traffic = Array();
  foreach ($platforms as $platform => $title) {
    $traffic[$platform] = 0;
  }
  foreach($data as $datum) {
    foreach ($datum as $field => $count) {
      if (array_key_exists($field, $traffic))
	$traffic[$field] += $count;
    }
  }
  return $traffic;
}


?>
