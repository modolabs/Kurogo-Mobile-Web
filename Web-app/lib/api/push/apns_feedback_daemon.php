#!/usr/bin/php
<?php

define("APNS_FEEDBACK_REST_TIME", 5*60*60);

require_once "DaemonWrapper.php";

$daemon = new DaemonWrapper("apns_feedback");
$daemon->start($argv);

require_once 'apns_lib.php';

$apns_server = new ApplePushNotificationConnection();

while($daemon->sleep(APNS_FEEDBACK_REST_TIME)) {
  // this is a daemon so loop forever
  $apns_server->open_feedback_connection();
  $messages = $apns_server->get_feedback_messages();

  db::ping();  
  foreach($messages as $message) {
    d_echo("received a deactivate message from apple for:{$message['device_token']}");
    APNS_DB::record_device_uninstalled_app($message['device_token'], $message['unixtime']);
  }
  $apns_server->close_feedback_connection();
}

$daemon->stop();

?>
