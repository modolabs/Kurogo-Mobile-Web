<?php

$header = "Stellar";
$module = "stellar";

$help = array(
  'Get the latest news, announcements, and faculty and staff listings for any class with a Stellar site. You can find a class in two ways:',

  '1. <strong>Browse</strong> by course and class number',

  '2. <strong>Search</strong> by part or all of the class name or number',

  'Each class detail screen includes the latest news and announcements as published by the class faculty and staff on the class Stellar page. You can also view the class description and faculty and staff listings by using the tabs at the top of the page. Click on the name of any faculty or staff member to look them up in the People Directory.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
