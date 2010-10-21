<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/Page.php";
require WEBROOT . "page_builder/page_tools.php";
require WEBROOT . "page_builder/counter.php";

$page = Page::factory();

//find which page is being requested
preg_match('/\/((\w|\-)+)\/[^\/]*?$/', $_SERVER['REQUEST_URI'], $match);
$content = $match[1];

PageViews::increment($content, $page->platform);

class DataServerException extends Exception {
}

class DeviceNotSupported extends Exception {
}

// use php default error handler for the dev version of the web site
// unccmment the line below to use the custom exception handler
// set_exception_handler("exception_handler");

function exception_handler($exception) {

  if(is_a($exception, "DataServerException")) {
    $error_query = "code=data&url=" . urlencode($_SERVER['REQUEST_URI']);
  } else if(is_a($exception, "DeviceNotSupported")) {
    $error_query = "code=device_notsupported";
  } else {
    $error_query = "code=internal";
  }
  $error_url = "../error-page/?{$error_query}";

  // a text representation of the exception
  ob_start();
    var_dump($exception);
  $text = ob_get_contents();
  ob_end_clean();

  if(!Page::is_spider()) {
    mail(
      DEVELOPER_EMAIL, 
      "mobile web page experiencing problems",
      "the following url is throwing exceptions: http://mobi.mit.edu{$_SERVER['REQUEST_URI']}\n" .
      "Exception:\n" . 
      "$text\n" .
      "The User-Agent: \"{$_SERVER['HTTP_USER_AGENT']}\"\n" .
      "The referer URL: \"{$_SERVER['HTTP_REFERER']}\""
    );
  }

  header("Location: {$error_url}");
  die(0);
}


?>
