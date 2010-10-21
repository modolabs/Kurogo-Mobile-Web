<?php

$header = "Download";
$module = "download";

$help = array(
  'Platform-by-platform instructions',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
