#!/usr/bin/php
<?php
require_once "DaemonWrapper.php";

$daemon = new DaemonWrapper("emergency");
$daemon->start($argv);



require_once dirname(__FILE__) . "/../../../mobi-config/mobi_lib_constants.php";
require_once LIB_ROOT . "rss_services.php";
require_once LIB_ROOT . "db.php";
require_once "apns_lib.php";

$date = NULL;
$emergency = new Emergency();
$emergency->use_cache = False;

$version = get_version();
  
while($daemon->sleep(15)) {
  $data = $emergency->get_feed();
  if($data !== False) {
    $new_version = intval($data[0]['version']);
  }

  if($version && ($new_version > $version)) {
    // there is emergency unfortunately we now have to notify ALL devices

    db::ping();
    $emergency_apns = array('aps' => 
      array('alert' => substr($data[0]['text'], 0, 100), 'sound' => 'default')
    );

    $result = APNS_DB::get_all_devices();
    while($row = $result->fetch_assoc()) {
      APNS_DB::create_notification($row['device_id'], "emergencyinfo:", $emergency_apns);
    }
    $result->close();
  }

  if($new_version > 0) {
    $version = $new_version;
    save_version($version);
  }
}

$daemon->stop();


/* these functions are for saving and grabbing the emergency version from disk
 that way the daemon is robust to being restarted */

function version_file_name() {
  return getenv('WSETCDIR') . "/pushd/emergency/last_emergency_version";
}

function get_version() {
  if(file_exists(version_file_name())) {
    return intval(file_get_contents(version_file_name()));
 } else {
    return NULL;
 }
}

function save_version($version) {
  file_put_contents(version_file_name(), $version);
}

?>
