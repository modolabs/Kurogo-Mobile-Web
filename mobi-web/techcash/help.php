<?php

$header = "TechCASH";
$module = "techcash";

$help = array(
  'View your current balance and most recent TechCASH transactions.',
  'To view TechCASH on your mobile device, you should already have a TechCASH account.',
  'You can open a TechCASH account by visiting <a href="http://techcash.mit.edu/">http://techcash.mit.edu</a> from your computer and clicking on "Open Account"',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
