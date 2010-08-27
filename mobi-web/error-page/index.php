<?php


switch($_REQUEST['code']) {
  case "data":
    header("Status: 504 Gateway Timeout");
    $message = "We are sorry the server is currently experiencing errors. Please try again later.";
     break;

  case "internal":
    header("Status: 500 Internal Server Error");
    $message = "Internal Server Error"; 
    break;

  case "notfound":
    header("Status: 404 Not Found");
    $message = "URL Not Found";
    break;

  case "forbidden":
    header("Status: 403 Forbidden");
    $message = "Not authorized to view this page";
    break;

  case "device_notsupported":
    $message = "This functionality is not supported on this device";
    break;

}

$url = $_REQUEST['url'];
$page->module('error-page');
require "$page->branch/index.html";

$page->help_off();
$page->output();

?>
