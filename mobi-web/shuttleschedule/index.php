<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once LIBDIR . "/TranslocReader.php";

$now = time();

$reader = new TranslocReader();

require "$page->branch/index.html";
$page->output();
?>
