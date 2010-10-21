<?php

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . '/mobi-config/mobi_web_constants.php';
require_once WEBROOT . "api/api_header.php";
require_once LIBDIR . "NewsOffice.php";
log_api('newsoffice');

$news_office = new NewsOffice();

$channel_id = $_REQUEST['channel'] ? $_REQUEST['channel'] : 0;

$story_id = isset($_REQUEST['story_id']) ? $_REQUEST['story_id'] : NULL;

echo $news_office->get_news_xml($channel_id, $story_id)->saveXML();;

?>
