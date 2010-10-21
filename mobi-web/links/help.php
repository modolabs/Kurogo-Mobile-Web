<?php

$header = "Useful Links";
$module = "links";

$help = array(
  'Get quick access to mobile-optimized websites of organizations providing affiliated services to the MIT community.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
