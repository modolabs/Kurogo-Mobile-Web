<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once WEBROOT . "page_builder/page_tools.php";
require_once LIBDIR . "NewsOffice.php";
require_once "story_request_lib.php";

require "$page->branch/channels.html";
$page->output();


?>