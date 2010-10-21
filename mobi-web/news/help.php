<?php

$header = "MIT News";
$module = "news";

$help = array(
  'Read the latest about MIT research, innovation, institute announcements and more.',
  
  'See all top news or view articles by topic: Campus News, Engineering, ' .
  'Science, Management, Architecture, and Humanities. If a narrower focus is ' .
  'desired, MIT News is searchable by keyword(s).',

  'Articles can be shared via email.  At the beginning of every article is a ' .
  'mail icon. Tapping the icon brings up an email containing a link to the ' .
  'article and once addressed is ready to send. ',
);


$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
