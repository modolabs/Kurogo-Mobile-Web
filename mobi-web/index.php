<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/Page.php";

$page = Page::factory();

if ($page->is_computer() || $page->is_spider()) {
  header("Location: ./about/");
} else {
  header("Location: ./home/");
}

?>
