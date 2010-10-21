<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/security.php";
require WEBROOT . "page_builder/page_header.php";
require LIBDIR . "tech_cash.php";

ssl_required();
if(!$page->certs) {

  $error_text = 'The mobile browser that you are using does not support MIT personal certicates. At this time only the iPhone, Windows Mobile Internet Explorer, and BlackBerry browsers support certificates. Please use one of the above browsers or use your computer (<a href="http://techcash.mit.edu">http://techcash.mit.edu</a>) to access yourTechCASH account.';

  $page->prepare_error_page('TechCASH', 'techcash', $error_text);

} else {

  $fullname = get_fullname();
  $username = get_username();

  $techcash = new TechCash();
  $techcash->init();
  $mit_id = getMitID($techcash, $username);
  //$mit_id = "111010083";

  $accounts = $techcash->getAccountNumbers($mit_id);

  foreach($accounts as $index => $row) {
    $balance = $techcash->getLatestBalance($mit_id, $row['ACCOUNTNUMBER']);
    $accounts[$index]['BALANCE'] = TechCash::dollar_string($balance);
    $accounts[$index]['NAME'] = $techcash->getAccountName($row['ACCOUNTNUMBER']);
  }

  $techcash->close();

  require "$page->branch/index.html";
}

$page->output();

function last_4($mit_id) {
  return substr($mit_id, 5, 4);
}

function detailURL($account) {
  return "./detail.php?id={$account['ACCOUNTNUMBER']}&type={$account['ACCOUNTTYPE']}";
}

?>