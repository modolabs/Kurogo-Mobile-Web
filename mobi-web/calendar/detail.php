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

$descriptionString = $event->get_description();

if (strlen($descriptionString) > 0)
   $descriptionString = $descriptionString .'<br /><br />';

$all_keys = array_keys($arr);
$ind = 0;
foreach($arr as $key) {
    if (($key != $arr['"Event Type"']) && ($key != $arr['"Gazette Classification"'])
        && ($key != $arr['"Contact Info"']) && ($key != $arr['"Location"'])
          && ($key != $arr['"Ticket Web Link"'])) {

            if (strlen(str_replace('"', '', $all_keys[$ind])) > 0) {
                $descriptionString = $descriptionString . str_replace('"', '', $all_keys[$ind]) .': ';
                $descriptionString = $descriptionString . $key . '<br /><br />';
            }
          }

    $ind++;
}

$url = URLize($event->get_url());
if ($url == "http://")
    $url = '';

$phoneNum = phoneURL($arr['"Contact Info"']->phone[0]);
$email = $arr['"Contact Info"']->email[0];

$ticketWebLink = URLize($arr['"Ticket Web Link"']);
if ($ticketWebLink == "http://")
    $ticketWebLink = '';

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
  $web_address = str_replace("http://http://", "http://", $web_address);

  if(preg_match('/^http\:\/\//', $web_address)) {
    return $web_address;
  } else {
    return 'http://' . $web_address;
  }
}

require "$page->branch/detail.html";
$page->output();

?>
