<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once LIBDIR . "stellar-constants.php";
require_once LIBDIR . "StellarData.php";

StellarData::init();
var_dump(StellarData::$subscriptions);
$log = CACHE_DIR . 'stellarDaemonLog.txt';

$pid = pcntl_fork();

// fork process and run in the background
echo "we are process $pid\n";

if ( $pid == -1 ) {
  die('could not fork');
} elseif ( $pid > 0 ) {
  // this is parent process
  exit;
} else {
  // this is child process
  $loghandle = fopen($log, 'w');

  while (1) {
    $updates = StellarData::check_subscriptions();
    var_dump($updates);
    foreach ($updates as $uid => $subjects) {
      //fwrite($loghandle, $uid . ': ' . implode(', ', $subjects) . "\n");
    }
    sleep(STELLAR_FEED_CACHE_TIMEOUT);
  }
}


?>