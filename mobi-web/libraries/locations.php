<?

require_once LIBDIR . '/LibraryInfo.php';
require "libraries_lib.inc";

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
