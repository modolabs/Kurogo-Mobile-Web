<?php

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

$back = isset($_REQUEST['back']) ? $_REQUEST['back'] : '';
$courseID = stripslashes($_REQUEST['id']);

$filter           = isset($_REQUEST['filter'])           ? stripslashes($_REQUEST['filter']) : '';
$courseGroup      = isset($_REQUEST['courseGroup'])      ? stripslashes($_REQUEST['courseGroup']) : '';
$courseGroupShort = isset($_REQUEST['courseGroupShort']) ? stripslashes($_REQUEST['courseGroupShort']) : '';
$courseName       = isset($_REQUEST['courseName'])       ? stripslashes($_REQUEST['courseName']) : '';
$courseNameShort  = isset($_REQUEST['courseNameShort'])  ? stripslashes($_REQUEST['courseNameShort']) : '';

$class = CourseData::get_subject_details($courseID);
$term = CourseData::get_term();
$term_id = $term;

$tabs = new Tabs(selfURL(), 'tab', array('Info', 'Instructor(s)'));

$back = isset($_REQUEST['back']) ? $_REQUEST['back'] : '';
$no_stellar_site = FALSE;



/* My Stellar actions */
$mystellar = getMyStellar()->allTags;
$class_data = $courseID . " " . $term_id;

if(in_array($class_data, $mystellar)) {
  $toggle = "ms_on";
  $mystellar_img = 'bookmark-on';
  $action = 'remove';
} elseif (!in_array($class_data, $mystellar)) {
  $toggle = "ms_off";
  $mystellar_img = 'bookmark-off';
  $action = 'add';
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
  if (!in_array($class_data, $mystellar)) {
    $mystellar[] = $class_data;
    header("Location: " . selfURL());
  }
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'remove') {
  if (in_array($class_data, $mystellar)) {
    array_splice($mystellar, array_search($class_data, $mystellar), 1);
    header("Location: " . selfURL());
  } else {
    foreach ($mystellar as $item) {
      if (strpos($item,$courseID) !== false) {
	array_splice($mystellar, array_search($item, $mystellar), 1);
	header("Location: index.php");
      }
    }
  }
}

setMyStellar($mystellar);


if (!$class) {
  // no such class or none entered
  $not_found_text = "Sorry, class '$courseID' not found for the $term term";
  $page->prepare_error_page('Courses', 'stellar', $not_found_text);

}

else {
  // tab options for Touch/Basic pages
  $tabs_html = $tabs->html($page->branch);
  $tab = $tabs->active();

  $stellar_url = stellarURL($class);
  $announcements = Array();
  $has_news = count($announcements) > 0;
  $has_old_news = count($announcements) > 5;

  //start session (used to save class details)
  session_start();
  $_SESSION['subjectID'] = $courseID;  // Cannot use $class because it contains objects
  $_SESSION['announcements'] = $announcements;

  if(isset($_REQUEST['all']) && $_REQUEST['all']) {
    $all = true;
    $items = $announcements;
  } else {
    $all = false;
    $items = array_slice($announcements, 0, 5);
  }

  if (isset($_REQUEST['index'])) {
    $item = $announcements[ $_REQUEST['index'] ];

    // from announcement.php
    $itemtext = htmlentities($item['text']);
    $itemtext = str_replace('&Acirc;', '', $itemtext);
    $itemtext = str_replace("\n", '<br/>', $itemtext);
  }

  require "$page->branch/detail.html";
}

$page->output();

/* functions */

function personURL($name) {
  $name = preg_replace('/\s+/', ' ', $name);
  $name = str_replace('.', '', $name);
  return "../people/?filter=" . urlencode($name);
}

function selfURL($all=NULL) {
  return "detail.php?".http_build_query(array(
    "id"               => $_REQUEST['id'],
    "courseGroup"      => isset($_REQUEST['courseGroup'])      ? stripslashes($_REQUEST['courseGroup']) : '',
    "courseGroupShort" => isset($_REQUEST['courseGroupShort']) ? stripslashes($_REQUEST['courseGroupShort']) : '',
    "courseName"       => isset($_REQUEST['courseName'])       ? stripslashes($_REQUEST['courseName']) : '',
    "courseNameShort"  => isset($_REQUEST['courseNameShort'])  ? stripslashes($_REQUEST['courseNameShort']) : '',
    "back"             => isset($_REQUEST['back'])             ? stripslashes($_REQUEST['back']) : '',
  ));


}

function announceURL($index) {
  return "announcement.php?".http_build_query(array(
    'index' => $index,
    'sess' => session_id(),
  ));
}

function summary($item) {
  return summary_string($item['text']);
}

function is_long_text($item) {
  return is_long_string($item['text']);
}

function full($item) {
  return htmlentities($item['text']);
}

function sDate($item) {
  return short_date($item['date']);
}

function stellarURL($class) {
  //$class['stellarUrl'];
}

?>
