<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

// this populates the $infoItems data
require "shuttle_info.inc";

$id = $_REQUEST['id'];

foreach($infoItems as $infoItem) {
  if($infoItem['id'] == $id) {
    $content = $infoItem['html'];
    $name = $infoItem['name'];
    break;
  }
}

require "$page->branch/info.html";
$page->output();

?>
