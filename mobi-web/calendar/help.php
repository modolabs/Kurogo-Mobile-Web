<?php

$header = "Events Calendar";
$module = "calendar";

$help = array(
  'Find out what&apos;s going around campus. You can find events and exhibits in two ways:',

  '1. <strong>Browse</strong> by any category shown on the Events Calendar homepage',

  '2. <strong>Search</strong> by keyword and timeframe',

  'Once you&apos;ve found an event, get one-click access to find its location on the Campus Map. You can also search easily for similar events by clicking on one of the links under &lsquo;Categorized as:&rsquo; at the bottom of the event-detail screen.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
