<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";
require WEBROOT . "libraries/libraries_lib.php";
require LIBDIR . "LibraryInfo.php";

require "$page->branch/index.html";
$page->output();

?>