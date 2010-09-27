<?php

require_once "push_lib.php";
define("APNS_PUSH_TIMEOUT", 90*60);
define("APNS_MINIMUM_WAIT", 0.5);

/*
 Since we have to wait .5 seconds to determine if the previous payload caused an APNS server
 disconnect, each connection is effectively limited to a thruput of 2 messages/second
 to overcome that difficulty we can have many connections open at once..
 this class does all the logic parcel messages thru the pool of connections
*/

class ApplePushServersPool {
  protected $push_connections;
  protected $push_connection_last_times;
  protected $pool_size = 0;

  public function __construct($pool_size) {
    for($cnt = 0; $cnt < $pool_size; $cnt++) {
      $this->push_connections[] = new ApplePushNotificationConnection();
      $this->push_connection_last_times[] = 0;
    }
    $this->pool_size = $pool_size;
  }

  public function send_push_notification($data, $device_token) {
    $connection_index = $this->get_next_connection_index();
    $connection = $this->push_connections[$connection_index];
    $connection_last_time = $this->push_connection_last_times[$connection_index];

    $connection_age = microtime(True) - $connection_last_time;    
    if($connection->is_push_open()) {
      if(($connection_age) > APNS_PUSH_TIMEOUT) {
        // since PHP does not let us implement SO_KEEPALIVE for SSL sockets we timeout the sockets
        $connection->close_push_connection();
      }
    }

    if(!$connection->is_push_open()) {
      // connecting and disconnecting too rapidly maybe interpreted by apple as
      // a denial of service attack
      sleep(1);  
      $connection->open_push_connection();
    }
     
    // waiting gives apple time to disconnect the connection if the previous
    // message failed, i.e. waiting prevents silent failures
    if($connection_age < APNS_MINIMUM_WAIT) {
      usleep(intval(1000000*APNS_MINIMUM_WAIT));
    }

    // we will try each message twice 
    // since the first failure is only related to the previous message sent
    // on the connection

    if(!$connection->send_push_notification($data, $device_token)) {
      $connection->close_push_connection();
      sleep(1);
      $connection->open_push_connection();

      // retry sending message
      $connection->send_push_notification($data, $device_token);
    }
    
    $this->push_connection_last_times[$connection_index] = microtime(True);
  }
  
  private function get_next_connection_index() {
    $time = microtime(True);
    $oldest = NULL;
    $oldest_index = NULL;

    // try to use the oldest connection which has not yet timed out
    // and which is older than the minimum wait time.
    foreach($this->push_connection_last_times as $index => $last_time) {
      $age = $time - $last_time;
      if( ($age > APNS_MINIMUM_WAIT) && ($age < APNS_PUSH_TIMEOUT) ) {
	if(!$oldest || ($time < $oldest) ) {
	  $oldest = $time;
          $oldest_index = $index;
        }
      }
    }

    if($oldest_index !== NULL) {
      // found a usable connection
      return $oldest_index;
    }

    // no reusable connection found just find the oldest one
    $oldest = min($this->push_connection_last_times);
    return array_search($oldest, $this->push_connection_last_times);
  }

  public function close() {
    foreach($this->push_connections as $connection) {
      if($connection->is_push_open()) {
        $connection->close_push_connection();
      }
    }
  }
}

class ApplePushNotificationConnection {

  protected $mode;
  protected $certificate_path;
  protected $certificate_pass;
  protected $context;
 
  protected $push_socket;
  protected $feedback_socket;

  static private $timeout=60;

  static $urls = array(
    'sandbox' => array(
      'push' => 'ssl://gateway.sandbox.push.apple.com:2195',
      'feedback' => 'ssl://feedback.sandbox.push.apple.com:2196'
    ),
    'production' => array(
      'push' => 'ssl://gateway.push.apple.com:2195',
      'feedback' => 'ssl://feedback.push.apple.com:2196'
    )
  );
  
  public function __construct() {
    $this->mode = APNS_SANDBOX ? 'sandbox' : 'production';      
    $this->certificate_path = APNS_SANDBOX ? APNS_CERTIFICATE_DEV : APNS_CERTIFICATE_PROD;
    $this->certificate_pass = APNS_SANDBOX ? APNS_CERTIFICATE_DEV_PASSWORD : APNS_CERTIFICATE_PASSWORD_PROD;

    // ssl options
    $this->context = stream_context_create();
    stream_context_set_option($this->context, 'ssl', 'local_cert', $this->certificate_path); 
    stream_context_set_option($this->context, 'ssl', 'passphrase', $this->certificate_pass);  
  }

  public function is_push_open() {
    if($this->push_socket) {
      return true;
    } else {
      return false;
    }
  }

  public function open_push_connection() {
    if($this->push_socket) {
      Throw new Exception("Push Server connection already open");
    }
  
    $this->push_socket = $this->open_ssl_socket(self::$urls[$this->mode]['push']);
  }

  public function close_push_connection() {
    if(!$this->push_socket) {
      Throw new Exception("Push Server connection already closed");
    }
    fclose($this->push_socket);
    $this->push_socket = NULL;
  }

  public function send_push_notification($data, $device_token) {
    $payload = json_encode($data);

    
    //  1B command = 0
    //  2B token length = 32   
    // 32B token                                 
    //  2B payload length                                                                            
    // ??B payload                   
    $message = chr(0) . chr(0) . chr(32) . pack('H*', $device_token) . chr(0) . chr(strlen($payload)) . $payload;

    // write data to socket
    if (!fwrite($this->push_socket, $message)) {
      d_error("failed to send payload=$payload");
      return False;
    }
    return True;
  }

  public function open_feedback_connection() {
    if($this->feedback_socket) {
      Throw new Exception("Feedback Server connection already open");
    }

    $this->feedback_socket = $this->open_ssl_socket(self::$urls[$this->mode]['feedback']);
  }

  public function close_feedback_connection() {
    if(!$this->feedback_socket) {
      Throw new Exception("Feedback Server connection already closed");
    }

    fclose($this->feedback_socket);

    $this->feedback_socket = NULL;
  }

  public function get_feedback_messages() {
    $feedback_messages = array();

    while ($raw = fread($this->feedback_socket, 38)) { // 4B timestamp, 2B token length, 32B deviceToken                 
      $arr = unpack("H*", $raw); 
      $rawhex = trim(implode("", $arr));
      $time = hexdec(substr($rawhex, 0, 8)); 
      
     
      $feedback_messages[] = array(
	'unixtime' => $time,
        'date' => date('Y-m-d H:i', $time),
        'token_length' => hexdec(substr($rawhex, 8, 4)), 
        'device_token' => substr($rawhex, 12, 64)
      );
    }
    return $feedback_messages;
  }

  private function open_ssl_socket($url) {
    $socket = stream_socket_client($url, $error, $errorString, self::$timeout, STREAM_CLIENT_CONNECT, $this->context);

    if(!$socket) {
      Throw new Exception("Failed to connect to $url (error $error): $errorString");
    }
    return $socket;
  }
}

class APNS_MessageRejected extends Exception {}

class APNS_DB {
  
  static public function get_unsent_notifications() {
    return db::$connection->query(
      "SELECT * FROM ApplePushNotification WHERE "
	. "(sent_unixtime is NULL) AND (undeliverable_unixtime is NULL)");
  }

  static public function fetch_notification($notifications) {
    // called from daemon so robust to db connection being lost
    if($notifications) {
      $notification = $notifications->fetch_assoc();
      if($notification) {
        $notification['payload'] = json_decode($notification['payload']);
      }
      return $notification;
    }
  }

  static public function close_notifications($notifications) {
    // called from daemon so robust to db connection being lost
    if($notifications) {
      $notifications->close();
    }
  }

  static public function create_notification($device_id, $tag, $data, $has_badge=True) {
    $json = db::$connection->real_escape_string(json_encode($data));
    $time = time();
    $has_badge = $has_badge ? 1 : 0;
    db::$connection->query("INSERT INTO ApplePushNotification "
      . "(device_id, payload, tag, created_unixtime, has_badge) "
      . "VALUES ({$device_id}, '$json', '$tag', $time, {$has_badge})");
  }


  static public function create_device_pass_key() {
    $pass_key = rand(0, 9999999999);
    db::$connection->query("INSERT INTO AppleDevice (pass_key) VALUES ('{$pass_key}')");
    return array('pass_key' => $pass_key, 'device_id' => db::$connection->insert_id);
  }

  static public function register_device_token($device_token, $device_id, $app_id) {
    if($app_id == APPLE_RELEASE_APP_ID) {
      if(!self::verify_device_token_format($device_token)) {
        throw new Exception("device token:'{$device_token}' not correctly formatted");
      }
      $device_token = db::escape($device_token);
      $device_id = db::escape($device_id);
      $time = time();
      db::$connection->query("UPDATE AppleDevice "
        . "SET device_token='{$device_token}', active=1, last_updated=$time "
        . " WHERE device_id='{$device_id}'");
     
      // this enforce the uniqueness of device tokens (which prevents us from repeating messages
      // to the same device
      db::$connection->query("UPDATE AppleDevice SET "
        . "device_token=NULL, active=0 "
	. "WHERE device_token='{$device_token}' AND device_id<>'{$device_id}'");
    }
  }

  static public function verify_device_token_format($device_token) {
    preg_match('/^[0-9a-f]{64}$/', $device_token, $matches);
    return (sizeof($matches) > 0);
  }

  static public function verify_device_id($device_id, $pass_key) {
    $device_id = db::escape($device_id);
    $pass_key = db::escape($pass_key);
    $result = db::$connection->query("SELECT COUNT(*) FROM AppleDevice WHERE device_id={$device_id} AND pass_key='{$pass_key}'");
    $row = $result->fetch_assoc();
    $result->close();
    return ($row['COUNT(*)'] > 0);
  }

  static public function get_device($device_id) {                                                
    $result = db::$connection->query(
      "SELECT * FROM AppleDevice WHERE device_id={$device_id}");
    $device = $result->fetch_assoc();
    $device['unread_notifications'] = json_decode($device['unread_notifications']);
    $result->close();
    return $device;
  }

  static public function get_all_devices() {
    return db::$connection->query("SELECT device_id FROM AppleDevice");
  }

  static public function record_device_uninstalled_app($device_token, $revoked_time) {
    db::$connection->query(
      "UPDATE AppleDevice SET active=0 "
      ."WHERE device_token='{$device_token}' AND last_updated < {$revoked_time}");
  }

  static public function record_notification_attempt($success, $message_id) {
    $field = $success ? 'sent_unixtime' : 'undeliverable_unixtime';
    db::$connection->query(
      "UPDATE ApplePushNotification SET $field=" . time() . " WHERE id={$message_id}");
  }

  static public function save_unread_notifications($device_id, $unreads) {
    // make sure the array json
    while(strlen(json_encode($unreads)) > 500) {
      array_splice($unreads, 0, 1);
    }

    $json = db::$connection->real_escape_string(json_encode($unreads));
    db::$connection->query("UPDATE AppleDevice "
      . " SET unread_notifications='$json' WHERE device_id={$device_id}");
  }

  static public function set_module_setting($device_id, $module, $enabled) {
    $module = db::escape($module);

    if($enabled) {
      // delete any rows disabling this module
      db::$connection->query(
        "DELETE FROM DisabledModule WHERE "
        . "device_id={$device_id} AND device_type='apple' AND module='$module'");
    } else {
      // do not worry about creating duplicates (duplicates are ugly but cause no problems)
      db::$connection->query(
        "INSERT INTO DisabledModule (device_id, device_type, module) VALUES "
        . "({$device_id},'apple','$module')");
    }
  }

  static public function is_module_enabled($device_id, $module) {
    $module = db::escape($module);
    $result = db::$connection->query(
      "SELECT COUNT(*) FROM DisabledModule WHERE device_id={$device_id} AND module='$module'");
    
    // method needs not to be robust to down db connections
    if($result) {
      $row = $result->fetch_assoc();
      $enabled = intval($row['COUNT(*)']) == 0;
      $result->close();
      return $enabled;
    } else {
      return False;
    }
  }
}

class APNSSubscriber extends NotificationSubscriber {

  public static function create() {
    $pass_key = intval($_REQUEST['pass_key']);
    $device_id = intval($_REQUEST['device_id']);       
    $device_type = $_REQUEST['device_type'];

    if ($device_type != 'apple') {
      error_log("wrong device type $device_type for APNS");
      return FALSE;
    }

    if (!APNS_DB::verify_device_id($device_id, $pass_key)) {
      error_log("invalid pass key $pass_key for device $device_id");
      return FALSE;
    }

    return new self($device_id, $device_type);
  }

}

?>
