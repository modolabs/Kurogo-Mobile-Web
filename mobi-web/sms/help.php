<?php

$header = "MIT SMS (BETA)";
$module = "sms";

$help = array(
  'MIT SMS (BETA) can be used on any mobile device with text messaging plan.',
  'For suggestions and questions, send email to <a href="mailto:mobiwebt@mit.edu">mobiweb@mit.edu.</a>',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
