<?php

require "WhatsNew.inc";

// dynamic pages need to include dynamics scripts
$pageParam = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';

switch($pageParam) {

  // static cases
  case "about":
  case "about_site":
    $device_phrases = array(
      "Webkit" => "iPhone, Android, and Palm webOS phones",
      "Touch" => "touchscreen phones",
      "Basic" => "non-touchscreen phones"
    );
    $device_phrase = $device_phrases[$page->branch];
    require "$page->branch/$pageParam.html";
    $page->output();
    break;
    

  default:
    require "$page->branch/index.html";
    $page->output();
    break;
}

?>
