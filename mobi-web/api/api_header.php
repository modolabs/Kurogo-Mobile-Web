<?

$docRoot = getenv("DOCUMENT_ROOT");

class DataServerException extends Exception {
}

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/counter.php";

$APIROOT = dirname(__FILE__);

function log_api($module) {
  $ua = $_SERVER['HTTP_USER_AGENT'];

  if (preg_match('/MIT[\s%20]+Mobile[\s\/]+([\w\.]+)\s*(.+)/', $ua, $matches)) {
    if (strpos($matches[2], 'Android')) {
      $platform = 'android';
    } else {
      $platform = 'iphone';
    }
  } elseif ($ua == 'Apache-HttpClient/UNAVAILABLE') {
      $platform = 'android';
  } else {
    $platform = reset(explode(" ", $ua));
  }

  PageViews::log_api($module, $platform);
}

?>
