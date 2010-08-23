<?php

require_once LIBDIR . "/StellarData.php";
require_once "stellar_lib.inc";

//start session (used to save class details)
session_id($_REQUEST['sess']);
session_start();
$subjectID = $_SESSION['subjectID'];
$announcements = $_SESSION['announcements'];
$item = $announcements[ $_REQUEST['index'] ];

require "$page->branch/announcement.html";
$page->output();

/* functions */

function paragraphs($item) {
  $text = htmlentities($item['text']);

  //this hack fixes some strange encoding problem
  $text = str_replace('&Acirc;', '', $text);
  return explode("\n", $text);
}

function longDate($item) {
  return date("l, F j, Y G:i", $item['unixtime']);
}

?>
