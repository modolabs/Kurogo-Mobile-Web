<?php
$base = "/var/";
$recipients = array(
  "mobile-project@mit.edu",
);

$fatal_error_regex = preg_quote("PHP Fatal error:", "/");
$base_regex = preg_quote($base, "/");

$recipient_str = implode(", ", $recipients);

$error_file_name = "/var/log/httpd/error_log";
$processed_error_name = "/var/log/httpd/processed_error_log";

if(is_readable($error_file_name)) {
  $in = fopen($error_file_name, "r");
  $out = fopen($processed_error_name, "a+");

  while($line = fgets($in)) {
    $result = preg_match("/{$fatal_error_regex}.*{$base_regex}/", $line);
    if($result) {
      mail(
	$recipient_str,
        "PHP error found in logs",
        $line );
    }
    fwrite($out, $line);
  }
}
fclose($in);
fclose($out);

$error_log = fopen($error_file_name, "w");
flock($error_log, LOCK_EX);
fwrite($error_log,"");
fclose($error_log);

?>
