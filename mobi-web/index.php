<?php

require_once "mobi-config/mobi_web_constants.php";
require "page_builder/Page.php";

$page = Page::factory();

if ($page->is_computer() || $page->is_spider()) {
  header("Location: ./about/");
} else {
  header("Location: ./home/");
}

?>
