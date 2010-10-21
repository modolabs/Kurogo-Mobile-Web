<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once WEBROOT . "page_builder/page_tools.php";

require_once LIBDIR . "NewsOffice.php";
require_once "story_request_lib.php";

$story = getStoryFromRequest();
$story_html = new HTMLFragment($story['body']);
$pages = $story_html->pages();
$page_count = sizeof($pages);
$current_page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$page_html = $pages[$current_page];
$page_content = $page_html->getBody();

$previous_page = ($current_page != 0) ? $current_page-1 : NULL;
$next_page = ($current_page+1 != $page_count) ? $current_page+1 : NULL;

$email_query = "mailto:?subject=" . rawurlencode($story['title']);
$email_query .= "&body=" . rawurlencode($story['description'] . "\n\n" . NewsOffice::story_link($story['story_id']));

if(isset($story['main_image']) && $story['main_image']) {
  $main_image = $story['main_image'];
  $total_photos = sizeof($story['images']) + 1;
}

if($total_photos == 1) {
  $see_instructions = "See 1 photo";
} else {
  $see_instructions = "See {$total_photos} photos";
}

require "{$page->branch}/detail.html";

$page->output();


function postDay($story) {
  return date('M j, Y', $story['unixtime']);
}

?>