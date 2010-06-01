<?


require_once "../mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/counter.php";
require_once WEBROOT . "home/Modules.php";

$logdir = '/var/log/httpd/';

// do current log file first
$log = fopen('/tmp/access_log', 'r'); // copied there by setup.sh
echo date('H:i:s', time()) . " reading /tmp/access_log\n";
while (!feof($log)) {
  $line = fgets($log, 1024);
  parse_line($line);
}
fclose($log);

$logfiles = scandir($logdir);
foreach ($logfiles as $logfile) {
  if (strpos($logfile, 'access_log.') === 0) {
    echo date('H:i:s', time()) . " reading $logdir$logfile\n";
    // log files on mobi and mobi-stage are gzipped
    if (strpos($logfile, 'gz') > 0) { 
      $log = gzopen($logdir . $logfile, 'r');
      while (!gzeof($log)) {
	parse_line(gzgets($log, 1024));
      }
      gzclose($log);
    } else { // log files on mobi2 are not gzipped
      $log = fopen($logdir . $logfile, 'r');
      while (!feof($log)) {
	parse_line(fgets($log, 1024));
      }
      fclose($log);
    }
  }
}

function parse_line($line) {
  // ignore nagios checks and localhost calls
  if (strpos($line, '127.' === 0) || strpos($line, 'check_http') !== FALSE)
    return;
  if (preg_match('/GET ' . str_replace('/', '\/', HTTPROOT) . '([\w\-]+)\/?( HTTP|[\w\-]+\.php)/', $line, $matches)) {
    $module = $matches[1];
    if ($module == 'home' || array_key_exists($module, Modules::full_list())) {
      preg_match('/\[(\d+\/\w+\/\d{4})/', $line, $matches);
      $day = strtotime(str_replace('/', '-', $matches[1]));
      $ua_end = strrpos($line, '"');
      $ua_start = strrpos(substr($line, 0, $ua_end), '"') + 1;
      $ua = substr($line, $ua_start, $ua_end - $ua_start);
      $request = Array('ua' => $ua, 'action' => 'classify');
      $result = json_decode(file_get_contents(MOBI_SERVICE_URL . '?' . http_build_query($request)), TRUE);
      $platform = $result['platform'];
      PageViews::increment($module, $platform, $day);
      //echo time() . ' ' . date('Y-m-d', $day) . ' ' . $module . ' ' . $platform . "\n";
    }
  }
  return;
}

?>
