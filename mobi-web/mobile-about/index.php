<?php

require "WhatsNew.inc";

// dynamic pages need to include dynamics scripts
$pageParam = isset($_REQUEST['page']) ? $_REQUEST['page'] : 'about';

switch($pageParam) {

  // dynamic cases
  case "statistics":
    require "statistics.php";
    break;

  // static cases
  case "background":
    $device_phrases = array(
      "Webkit" => "iPhone, Android, and Palm webOS phones",
      "Touch" => "touchscreen phones",
      "Basic" => "non-touchscreen phones"
    );
    if ($page->platform == 'bbplus') {
      $device_phrase = "high-resolution BlackBerry devices";
    } else {
      $device_phrase = $device_phrases[$page->branch];
    }
    $backgroundInfo = "The Harvard mobile website is part of a broader initiative to improve the mobile experience of students, faculty, staff, visitors, and neighbors who interact with Harvard's campus and community. We will continue to develop and improve the mobile website, and welcome your ideas and feedback.";

  case "requirements":
    require "$page->branch/{$_REQUEST['page']}.html";
    $page->cache();
    $page->output();
    break;
    

  default:
    require "$page->branch/index.html";
    $page->output();
    break;
}

?>
