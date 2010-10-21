<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . 'page_builder/page_header.php';
require_once LIBDIR . 'mit_ical_lib.php';
require_once LIBDIR . 'LibraryInfo.php';
require WEBROOT . "libraries/libraries_lib.php";

$libraries = LibraryInfo::get_libraries();
$hours_today = Array();

foreach ($libraries as $library) {
  $hourstr = hours_today($library);
  if ($hourstr != 'Closed') {
    $hours_today[$library] = 'Open ' . $hourstr;
  } else {
    $hours_today[$library] = 'Closed';
  }
}

require "$page->branch/locations.html";

$page->output();

?>