<?php

$main = array(
  i("6172531212", "Campus Police"),
  i("6172531311", "MIT Medical"),
  i("6172537669", "Emergency Status")
);

$others = array(
  i("6172531311", "MIT Medical", "24-hour urgent care"),
  i("617253SNOW", "Emergency Closings", "recorded updates"),
  i("6172532823", "International SOS", "emergency medical and security evacuation services for those traveling abroad on MIT business"),
  i("6172534948", "Facilities", "24-hour emergency repairs"),
  i("6172538800", "Nightline", "MIT student hotline 7pm-7am"),
  i("6172532997", "Safe Ride", "campus transportation 6pm-3am"),
  i("6172532700", "MIT News Office"),
  i("6172534795", "Information Center"),
  i("6172531212", "MIT Police"),
  i("6172537276", "MIT Police - Guest Parking"),
  i("6172539753", "MIT Police - Lost and Found"),
  i("617253DOWN", "Computer and Communications Outages"),
  i("6174523477", "Environment, Health & Safety"),
  i("6172587366", "Security and Emergency Management Office"),
  i("6172538000", "Message Center - Fax Service"),
  i("6172533692", "Message Center - Emergency Number"),
  i("6172531000", "Telephone Service - MIT Directory Assistance"),
  i("6172534357", "Telephone Service - Service Problems"),  
  i("6172536311", "Travel directions to MIT"),
  i("6172539200", "Bates Linear Accelerator Center"),
  i("7819815555", "Haystack Observatory"),
  i("7819813333", "Lincoln Laboratory Emergencies", "security desk"),
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once LIBDIR . "rss_services.php";

$emergency_message = "Coming Soon: Emergency Updates"; 
$Emergency = new Emergency();
//$emergency = $Emergency->get_feed();
$emergency = $Emergency->get_feed_html();

if($emergency === False) {
  $paragraphs = array('Emergency information is currently not available');
} else {
  $paragraphs = array($emergency[0]['text']);
  //$text = explode("\n", $emergency[0]['text']);
  //$paragraphs = array();
  //foreach($text as $paragraph) {
  //  if($paragraph) {
  //    $paragraphs[] = htmlentities($paragraph);
  //  }
  //}
}

// the logic to implement the page begins here
$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/page_header.php";


if(isset($_REQUEST['contacts'])) {
  require "$page->branch/contacts.html";
} else {
  require "$page->branch/index.html";
}

$page->output();

function contactsURL() {
  return "./?contacts=true";
}

class EmergencyItem {
  private $number;
  private $label;
  private $message;

  // letters on a phone key-pad
  private static $letters = array(
    "A-C" => 2,
    "D-F" => 3, 
    "G-I" => 4,
    "J-K" => 5,
    "M-O" => 6,
    "P-S" => 7,
    "T-V" => 8,
    "W-Z" => 9
  );

  public function __construct($number, $label, $message) {
    $this->number = $number;
    $this->label = $label;
    $this->message = $message;
  }
  
  public function call_number() {
    $init = $this->number;
    foreach(self::$letters as $letters => $digit) {
      $init = preg_replace("/[$letters]/", $digit, $init);
    }
    return $init;
  }

  public function number_text() {
    return substr($this->number, 0, 3) . "." . substr($this->number, 3, 3) . "." . substr($this->number, 6, 4);
  }

  public function label() {
    return htmlentities($this->label);
  }

  public function message_text() {
    if($this->message) {
      return htmlentities($this->message . ": ");
    } else {
      return "";
    }
  }
}

function i($number, $label, $message=NULL) {
  return new EmergencyItem($number, $label, $message);
}



?>
