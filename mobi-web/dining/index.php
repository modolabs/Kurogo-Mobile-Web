<?php

require_once LIBDIR . "/harvard_dining.php";
require_once LIBDIR . "/diningHrs.php";

function day_info($time, $offset=0) {
  $time += $offset * 24 * 60 * 60;
  return array(
    "weekday"       => date('l', $time),
    "month"         => date('F', $time),
    "month_3Let"    => date('M', $time),
    "day_num"       => date('j', $time),
    "year"          => date('Y', $time),
    "month_num"     => date('m', $time),
    "day_3Let"      => date('D', $time),
    "day_num_2dig"  => date('d', $time),
    "date"          => date('Y/m/d', $time),
    "time"          => strtotime(date("Y-m-d 12:00:00", $time))
  );
}

function dayURL($day, $type) {
  return "index.php?time={$day['time']}&type=$type";
}

$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();

$day = date('Y-m-d', $time);

$current = day_info($time);
$next = day_info($time, 1);
$prev = day_info($time, -1);


$food_items = array(
    "breakfast" => DINING_DATA::getDiningData($day, "BRK"),
    "lunch" => DINING_DATA::getDiningData($day, "LUN"),
    "dinner" => DINING_DATA::getDiningData($day, "DIN"),
);

foreach($food_items as $meal => $items) {
    $food_items[$meal] = collectFoodByCategory($items);
}

$hour = intval(date('G'));

if($hour < 12) {
    $current_meal = "breakfast";
} else if ($hour < 15) {
    $current_meal = "lunch";
} else {
    $current_meal = "dinner";
}

require "$page->branch/index.html";

$page->output();


function collectFoodByCategory($items) {
    $food_categories = array();

    foreach($items as $item) {
        // this is a hack for now just to fill in some of the data
        $item["properties"] = array("vegetarian", "local");
        
        if(!array_key_exists($item[category], $food_categories)) {
            $food_categories[$item[category]] = array($item);
        } else {
            $food_categories[$item[category]][] = $item;
        }
    }

    return $food_categories;
}
?>
