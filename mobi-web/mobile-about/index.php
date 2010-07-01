<?php

require "WhatsNew.inc";

// dynamic pages need to include dynamics scripts
switch($_REQUEST['page']) {

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
    $device_phrase = $device_phrases[$page->branch];

  case "requirements":
  case "credits":
    require "$page->branch/{$_REQUEST['page']}.html";
    $page->cache();
    $page->output();
    break;

  case "about":
  default:
    $whats_new = new WhatsNew();
    $whats_new_count = $whats_new->count(WhatsNew::getLastTime());
    require "$page->branch/index.html";
    $page->output();
}


?>
