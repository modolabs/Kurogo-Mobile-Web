<?php

require_once LIBDIR . "/harvard_calendar.php";
require "calendar_lib.inc";

// copied from api/HarvardCalendar.php
$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
$date1 = date('Ym', $time);
$month = "?startdate=" .$date1 ."01&months=1";
$date = date('Ymd', $time);
$url = HARVARD_EVENTS_ICS_BASE_URL .$month;
$event = getIcalEvent($url, $date, $_REQUEST['id']);
$date_str = $event->get_range()->format('D M j, Y');
if ($event->get_end() - $event->get_start() == -1) {
  $time_of_day = date('g:i a', $event->get_start());
} else {
  $time_of_day = $event->get_range()->format('g:i a');
}


$arr = $event->get_customFields();
$categories = explode('\,',$arr['"Gazette Classification"']);

// Get Lat Long info
$jsonString= json_encode($arr);
$latLongArray = explode('<\/Latitude>',$jsonString);
$tempArray = explode(">", $latLongArray[0]);
$tempArray_count = count($tempArray);
$tempArray2 = explode("<\/Longitude>", $latLongArray[1]);

$latitude = $tempArray[$tempArray_count - 1];
$longitude = $tempArray2[0];

 function phoneURL($number) {
  if($number) {

    // add the local area code if missing
    if(preg_match('/^\d{3}-\d{4}/', $number)) {
      $number = '617' . $number;
    }

    // remove all non-word characters from the number
    $number = preg_replace('/\W/', '', $number);
    return "tel:1$number";
  }
}

function URLize($web_address) {
  if(preg_match('/^http\:\/\//', $web_address)) {
    return $web_address;
  } else {
    return 'http://' . $web_address;
  }
}

require "$page->branch/detail.html";
$page->output();

?>
