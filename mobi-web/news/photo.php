<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once WEBROOT . "page_builder/page_tools.php";
require_once LIBDIR . "NewsOffice.php";

require_once "story_request_lib.php";

$story = getStoryFromRequest();

if($_REQUEST['id'] == 'main') {
  $photo = $story['main_image'];
  $current_photo_index = 0;
} else {
  $photo = $story['images'][$_REQUEST['id']];
  $current_photo_index = $_REQUEST['id'] + 1;
}

$width = $photo->full_dimensions['width'];
$height = $photo->full_dimensions['height'];

$total_photos = sizeof($story['images']) + 1;

if($photo->caption) {
  $caption = htmlentities($photo->caption, ENT_QUOTES, 'UTF-8');
}

$credits = htmlentities($photo->credits, ENT_QUOTES, 'UTF-8');


require "{$page->branch}/photo.html";
$page->output();

function photoURL($index) {
  if($index == 0) {
    $id = 'main';
  } else {
    $id = $index - 1;
  }
  
  return "./photo.php?" . storyPathQuery() ."&id=$id";   
}

?>