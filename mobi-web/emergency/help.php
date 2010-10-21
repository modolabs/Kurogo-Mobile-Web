<?php

$header = "Emergency Info";
$module = "emergency";

$help = array(
  'Stay updated on campus emergencies, and get one-click access to campus police, medical services and other emergency phone numbers.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
