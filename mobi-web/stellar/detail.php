<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "StellarData.php";
require_once WEBROOT . "stellar/stellar_lib.php";

$class_id = $_REQUEST['id'];
$class = StellarData::get_subject_info($class_id);
$term = StellarData::get_term_text();
$term_id = StellarData::get_term();

$tabs = new Tabs(selfURL(), 'tab', array('News', 'Info', 'Staff'));

$back = $_REQUEST['back'];

/* My Stellar actions */
$mystellar = getMyStellar()->allTags;
$class_data = $class_id . " " . $term_id;

if(in_array($class_data, $mystellar)) { 
  $toggle = "ms_on";
  $mystellar_img = 'mystellar-on';
  $action = 'remove';
} elseif (!in_array($class_data, $mystellar)) {
  $toggle = "ms_off";
  $mystellar_img = 'mystellar-off';
  $action = 'add';
}

if($_REQUEST['action'] == 'add') {
  if (!in_array($class_data, $mystellar)) {
    $mystellar[] = $class_data;
    header("Location: " . selfURL());
  }
}

if($_REQUEST['action'] == 'remove') {
  if (in_array($class_data, $mystellar)) {
    array_splice($mystellar, array_search($class_data, $mystellar), 1);
    header("Location: " . selfURL());
  } else {
    foreach ($mystellar as $item) {
      if (strpos($item,$class_id) !== false) {
	array_splice($mystellar, array_search($item, $mystellar), 1);
	header("Location: index.php");
      }
    }
  }
}

setMyStellar($mystellar);

if (!$class) {
  // no such class or none entered
  $not_found_text = "Sorry, class '$class_id' not found for the $term term";
  $page->prepare_error_page('Stellar', 'stellar', $not_found_text);

} elseif (!has_stellar_site($class)) {
  // no stellarSite; show page with tabs disabled except Info
  $no_stellar_site = TRUE;
  $class['times'] = Array(); // empty array so foreach doesn't complain

  // tab options for Touch/Basic pages
  $tabs->hide('News');
  $tabs->hide('Staff');
  $tabs_html = $tabs->html($page->branch);
  $tab = $tabs->active();

  require "$page->branch/detail.html";

} else {
  // tab options for Touch/Basic pages
  $tabs_html = $tabs->html($page->branch);
  $tab = $tabs->active();

  $stellar_url = stellarURL($class);
  $announcements = StellarData::get_announcements($class_id);
  $has_news = count($announcements) > 0;
  $has_old_news = count($announcements) > 5;

  //start session (used to save class details)
  session_start();
  $_SESSION['class'] = $class;
  $_SESSION['announcements'] = $announcements;

  if($_REQUEST['all']) {
    $all = true;
    $items = $announcements;
  } else {
    $all = false;
    $items = array_slice($announcements, 0, 5);
  }

  if (isset($_REQUEST['index'])) {
    $item = $announcements[ $_REQUEST['index'] ];

    // from announcement.php
    $itemtext = htmlentities($item['text'], ENT_QUOTES, 'UTF-8');
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
  $all = $all ? $all : $_REQUEST['all'];
  $query = http_build_query(array(
    "id"   => $_REQUEST['id'],
    "all"  => $all,
    "back" => $_REQUEST['back']
  ));
  return "detail.php?$query";
}

function announceURL($index) {
  return "announcement.php?index=$index&sess=" . session_id();
}

function summary($item) {
  return summary_string($item['text']);
}

function is_long_text($item) {
  return is_long_string($item['text']);
}

function full($item) {
  return htmlentities($item['text'], ENT_QUOTES, 'UTF-8');
}

function sDate($item) {
  return short_date($item['date']);
}

function stellarURL($class) {
  return 'http://stellar.mit.edu/' . $class['stellarUrl'];
}

?>
