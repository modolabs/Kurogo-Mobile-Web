<?php

require "WhatsNew.inc";

$whats_new = new WhatsNew();
$new_items = $whats_new->get_items();
WhatsNew::setLastTime();

$start = !isset($_REQUEST['start']) ? 0 : intval($_REQUEST['start']);
$pager = new Pager($page->max_list_items, $new_items, $start);
$arrows = $pager->prev_next_html($_SERVER['SCRIPT_NAME'], array(), "start");
$items = $pager->items();

require "$page->branch/new.html";
$page->output();

/*
function text($data) {
  return htmlentities($data['body']);
}
*/

function date_string($data, $format) {
  return date($format, $data['unixtime']);
}

?>
