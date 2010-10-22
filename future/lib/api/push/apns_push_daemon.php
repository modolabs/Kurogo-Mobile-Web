#!/usr/bin/php
<?php
define("APNS_PUSH_REST_TIME", 15);


require_once dirname(__FILE__) . "/../../config/mobi_web_constants.php";

require_once "DaemonWrapper.php";

$daemon = new DaemonWrapper("apns_push");
$daemon->start($argv);

require_once 'apns_lib.php';

$apns_server = new ApplePushServersPool(APNS_CONNECTIONS_LIMIT);

// this is a daemon so loop forever
d_echo("push daemon activated");

while($daemon->sleep(APNS_PUSH_REST_TIME)) {
  d_echo("waiting for messages to send...", False);

  db::ping();
  $messages = APNS_DB::get_unsent_notifications();
  while($message = APNS_DB::fetch_notification($messages)) {
    $data = $message['payload'];
    $device_id = $message['device_id'];
    $device = APNS_DB::get_device($device_id); 
    $module_name = get_module_name($message['tag']);

    // we only send to devices that have not been deactived
    // and we only send to enabled modules
    if(($device['active'] == 1) && APNS_DB::is_module_enabled($device_id, $module_name)) {

      // need to compute the number of unread messages
      // to be displayed on "badge" on the device
      $unreads = $device['unread_notifications'];
      
      // look for an old version of this message
      $old_position = array_search($message['tag'], $unreads);      
      if($old_position !== FALSE) {
        array_splice($unreads, $old_position, 1);
      }
      if(intval($message['has_badge'])) {
        $unreads[] = $message['tag'];
      } else {
        $data->noBadge = 1;
      }

      $data->aps->badge = count($unreads);
      $data->tag = $message['tag'];

      d_echo("sending message: " . json_encode($data));
      $apns_server->send_push_notification($data, $device['device_token']);
      APNS_DB::record_notification_attempt(TRUE, $message['id']);
        
    } else {
      APNS_DB::record_notification_attempt(FALSE, $message['id']);
    }

    // backend needs to keep track of which messages are not yet read
    APNS_DB::save_unread_notifications($device['device_id'], $unreads);      
  }

  APNS_DB::close_notifications($messages);
}

$apns_server->close();
$daemon->stop();

function get_module_name($tag) {
  $parts = split(":", $tag);
  return $parts[0];
}

?>
