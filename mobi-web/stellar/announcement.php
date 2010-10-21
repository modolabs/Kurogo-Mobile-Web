<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "StellarData.php";
require_once WEBROOT . "stellar/stellar_lib.php";

//start session (used to save class details)
session_id($_REQUEST['sess']);
session_start();
$class = $_SESSION['class'];
$announcements = $_SESSION['announcements'];
$item = $announcements[ $_REQUEST['index'] ];

require "$page->branch/announcement.html";
$page->output();

/* functions */

function paragraphs($item) {
  $text = htmlentities($item['text'], ENT_QUOTES, 'UTF-8');
  return explode("\n", $text);
}

function longDate($item) {
  return date("l, F j, Y G:i", $item['unixtime']);
}

?>
