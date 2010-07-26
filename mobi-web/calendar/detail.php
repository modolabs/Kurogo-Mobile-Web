<?php

require_once LIBDIR . "/harvard_calendar.php";
require "calendar_lib.inc";

//$event = MIT_Calendar::getEvent($_REQUEST['id']);
//$time_of_day = MIT_Calendar::timeText($event);

//$day_num = (string)(int)$event->start->day;
//$date_str = "{$event->start->weekday}, {$event->start->monthname} {$day_num}, {$event->start->year}"; 

//$event->urlize = URLize($event->infourl);

// copied from api/HarvardCalendar.php
$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
$date1 = date('Ym', $time);
$month = "?startdate=" .$date1 ."01&months=1";
$date = date('Ymd', $time);
$url = HARVARD_EVENTS_ICS_BASE_URL .$month;
error_log($_REQUEST['id']);
$event = getIcalEvent($url, $date, $_REQUEST['id']);
$date_str = $event->get_range()->format('D M j, Y');
if ($event->get_end() - $event->get_start() == -1) {
  $time_of_day = date('g:i a', $event->get_start());
} else {
  $time_of_day = $event->get_range()->format('g:i a');
}

$categories = explode(',', $event->get_categories());

function phoneURL($number) {
  if($number) {

    // add the local area code if missing
    if(preg_match('/^\d{3}-\d{4}/', $number)) {
      $number = '617' . $number;
    }

    // check if the number is short number such as x4-2323, 4-2323, 42323
    if(preg_match('/^\d{5}/', $number)) {
      $first_digit = substr($number, 0, 1);
    } elseif(preg_match('/^x\d/', $number)) {
      $number = substr($number, 1);
      $first_digit = substr($number, 0, 1);
    } elseif(preg_match('/^\d-\d{4}/', $number)) {
      $first_digit = substr($number, 0, 1);
    }

    // if short number add the appropriate prefix and area code
    $prefixes = array('252', '253', '324', '225', '577', '258');
    if($first_digit) {
      foreach($prefixes as $prefix) {
        if(substr($prefix, -1) == $first_digit) {
          $number = "617" . substr($prefix, 0, 2) . $number;
          break;
        }  
      }
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
