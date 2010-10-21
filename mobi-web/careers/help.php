<?php

$header = "Career Services";
$module = "careers";

$help = array(
  'Find times and locations of on-campus workshops and company presentations organized by Student Career Services, and locate those events in the Campus Map.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
