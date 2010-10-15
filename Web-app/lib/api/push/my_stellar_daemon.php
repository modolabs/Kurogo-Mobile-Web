#!/usr/bin/php
<?php

require_once "DaemonWrapper.php";
$daemon = new DaemonWrapper("my_stellar");
$daemon->start($argv);

require_once dirname(__FILE__) . "/../../../mobi-config/mobi_lib_constants.php";
require_once LIB_ROOT . "db.php";
require_once LIB_ROOT . "StellarData.php";
require_once "apns_lib.php";

while($daemon->sleep(STELLAR_FEED_CACHE_TIMEOUT)) {
  db::ping();  //make sure the db keeps running

  $term = StellarData::get_term();
  $subject_ids = StellarData::check_subscriptions($term);

  foreach($subject_ids as $subject_id) {
    $announcements = StellarData::get_announcements($subject_id);

    if(count($announcements) > 0 ) {
      $notification_types = array(
	'apple' => new StellarAppleNotification($subject_id, $announcements)
      );

      foreach(StellarData::subscriptions_for_subject($subject_id, $term) as $subscription) {
        $notification_types[$subscription['device_type']]->queue_notification($subscription['device_id']);
      }
    }
  }
}
 
$daemon->stop();

Class StellarNotification {
  protected $announcements;
  protected $subject_id;
  
  public function __construct($subject_id, $announcements) {
    $this->subject_id = $subject_id;
    $this->announcements = $announcements;
  }
}

Class StellarAppleNotification extends StellarNotification {
  protected $data;

  public function __construct($subject_id, $announcements) {
    parent::__construct($subject_id, $announcements);
    
    // construct the announcement text, for simplicity we truncate it 100 characters
    // could do something more eloborate but difficult to use every byte of the 256 bytes
    // allowed in an apple push message

    $text = "Class {$this->subject_id}\n";
    $text .= $announcements[0]['title'];
    if($announcements[0]['text']) {
      $text .= ": " . $announcements[0]['text'];
    }
    if(strlen($text) > 100) {
      $text = trim(substr($text, 0, 100));
      $text .= "...";
    }
    $this->data = array('aps' => array('alert' =>$text));
  }
    
  public function queue_notification($device_id) {
    APNS_DB::create_notification($device_id, "stellar:{$this->subject_id}", $this->data);    
  }
}

?>
