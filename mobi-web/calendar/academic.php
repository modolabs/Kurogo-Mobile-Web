<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "academic.php";

$month = $_REQUEST['month'];
$year = $_REQUEST['year'];
$time = time();
if(!$month) {
  $month = strtoupper(date('F', $time));
}
if(!$year) {
  $year = date('Y', $time);
}

$prev = prev_month($month, $year);
$next = next_month($month, $year);
$prev_yr = $prev['year'];
$next_yr = $next['year'];
$prev_month = Month($prev['month']);
$next_month = Month($next['month']);
$Month = Month($month); 
$prev_url = "academic.php?year={$prev_yr}&month={$prev['month']}";
$next_url = "academic.php?year={$next_yr}&month={$next['month']}";
$days = $academic->years[$year][$month];
$has_prev = isset($academic->years[$prev_yr][ $prev['month'] ]);
$has_next = isset($academic->years[$next_yr][ $next['month'] ]);

require "$page->branch/academic.html";
$page->output();

class month_data {
  public static $months = array(
    "JANUARY",  "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", 
    "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"
  );
}

function next_month($month, $year) {
  $number = array_search($month, month_data::$months);
  $number++; 
  if($number == 12) {
    $year++;
    $number = 0;
  }
  return array("year" => $year, "month" => month_data::$months[$number]);
}

function prev_month($month, $year) {
  $number = array_search($month, month_data::$months);
  $number--; 
  if($number == -1) {
    $year--;
    $number = 11;
  }
  return array("year" => $year, "month" => month_data::$months[$number]);
}

?>