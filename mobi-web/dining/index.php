<?php

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

$time = $_REQUEST['time'];

$current = day_info($time);
$next = day_info($time, 1);
$prev = day_info($time, -1);

require "$page->branch/index.html";

$page->output();

?>
