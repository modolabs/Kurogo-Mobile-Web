<?php
// Template related
define('INSTITUTION_NAME', "Harvard University");
define('COPYRIGHT_NOTICE', '<a href="http://www.harvard.edu/?fullsite=yes" class="copyright">&copy;2010 The President and Fellows of Harvard College</a>');
define('FEEDBACK_EMAIL', "digitalcomms@harvard.edu");
define('APP_NAME', "Harvard Mobile");
define('SITE_NAME', "Harvard Mobile Web");
define('SITE_URL', "http://m.harvard.edu"); // This should be env. specific

// per-installation constants.

define('WEBROOT', '/var/www/html');
define('LIBDIR', '/opt/mitmobile/mobi-lib');

/* path where cookies should be stored on the users' browser. */
define('HTTPROOT', '/');

date_default_timezone_set('America/New_York');

// fetch per-server settings.

require_once LIBDIR . '/lib_constants.inc';
if (MOBILE_DEV_SERVER) {
  $ini = parse_ini_file("web_constants_dev.ini");
} elseif (MOBILE_STAGE_SERVER) {
  $ini = parse_ini_file("web_constants_stage.ini");
} else {
  $ini = parse_ini_file("web_constants_prod.ini");
}

require WEBROOT . '/page_builder/Page.inc';
require WEBROOT . '/page_builder/page_tools.inc';

if (strpos($_SERVER['SCRIPT_NAME'], 'api/') === FALSE) {
  $page = Page::factory();
} 

class DataServerException extends Exception {
}

class DeviceNotSupported extends Exception {
}

if($ini['USE_PRODUCTION_ERROR_HANDLER']) {
  set_exception_handler("exception_handler");
} else {
  set_exception_handler("dev_exception_handler");
}

function dev_exception_handler($exception) {

  if(is_a($exception, "DataServerException")) {
    $error_query = "code=data&url=" . urlencode($_SERVER['REQUEST_URI']);
  } else if(is_a($exception, "DeviceNotSupported")) {
    $error_query = "code=device_notsupported";
  } else {
    $error_query = "code=internal";
  }
  $error_url = HTTPROOT . "error-page/?{$error_query}";

  error_log(print_r($exception, TRUE));
  header("Location: {$error_url}");
}

function exception_handler($exception) {
  global $ini;

  if(is_a($exception, 'DataServerException')) {
    $error_query = 'code=data&url=' . urlencode($_SERVER['REQUEST_URI']);
  } else if(is_a($exception, 'DeviceNotSupported')) {
    $error_query = 'code=device_notsupported';
  } else {
    $error_query = 'code=internal';
  }
  $error_url = HTTPROOT . "error-page/?{$error_query}";

  // a text representation of the exception
  ob_start();
    var_dump($exception);
  $text = ob_get_contents();
  ob_end_clean();

  if(!Page::factory()->is_spider()) {
    mail(
      $ini['DEVELOPER_EMAIL'], 
      "mobile web page experiencing problems",
      "the following url is throwing exceptions: http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}\n" .
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
