<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";

$sms_announcement = 'MIT SMS (Beta) is no longer in service. <a href="http://ist.mit.edu/news/smsretirement">More information</a>';

$sms_instructions = new SMSInstructions(
  module("People Directory",
   "Search all or part of a name, email address, or phone number.",
   "dir",
   array(
     "Keywords" => items("dir, ppl"),
     "For help" => items("dir [help]"),
     "Examples" => items("dir hockfield", "dir 6172531000&shy;", "ppl charles vest"))),
  module("Shuttle Schedule",
   "Get an up-to-the-minute schedule by shuttle name or two-letter abbreviation.",
   "bus",
   array(
     "Keywords" => items("bus, shuttle"),
     "For help" => items("bus [help]"),
     "Examples" => items("bus ce", "bus cambridge east", "bus nw", "bus northwest", "bus bd", "bus boston daytime"),
     "Route codes" => items("Boston Daytime: BD", "Boston East: BE", "Boston West: BW", "Cambridge East: CE", "Cambridge West: CW", "Northwest Shuttle: NW", "Tech Shuttle: TS"))),
  module("Stellar",
   "Get course information and latest announcements by class number.",
   "cls",
   array(
     "Keywords" => items("cls, class, stellar"),
     "For help" => items("cls [help]"),
     "Examples" => items("cls 6.002", "class 8.03", "stellar 18.06"))),
  module("Events Calendar",
   "Get the upcoming events on the MIT calendar.",
   "events",
   array(
     "Keywords" => items("events", "cal", "calendar"),
     "Examples" => items("events", "cal 11pm"))),
  module("Emergency Info",
   "Get the latest status on campus emergencies.",
   "sos",
   array(
     "Keywords" => items("sos", "emergency"),
     "Example" => items("sos"))),
  module("3Down",
   "Get the latest status on various campus services.",
   "3dwn",
   array(
     "Keywords" => items("3dwn", "3down"),
     "Example" => items("3dwn")))
);

if($page->branch == "Basic" && isset($_REQUEST['module'])) {
  $module = $sms_instructions->getModule($_REQUEST['module']);
  require "$page->branch/module.html";
} else {
  require "$page->branch/index.html";
}

$page->output();


class SMSInstructions {
  
  public $modules;
  public function __construct() {
    $this->modules = func_get_args();
  }

  public function getModule($encodedName) {
    foreach($this->modules as $module) {
      if($module->getEncodedName() == $encodedName) {
        return $module;
      }
    }
  }
}

class Module {

  public $name;
  public $text;
  public $keyword;
  public $elements;

  public function __construct($name, $text, $keyword, $elements) {
    $this->name = $name;
    $this->text = $text;
    $this->keyword = $keyword;
    $this->elements = $elements;
  }

  public function getEncodedName() {
    $encodedName = strtolower($this->name);
    $pos = strpos($encodedName, ' ');
    if($pos === False) {
      return $encodedName;
    } else {
      return substr($encodedName, 0, $pos);
    }
  }
}

function module($name, $text, $keyword, $elements) {
  return new Module($name, $text, $keyword, $elements);
}

function items() {
  return func_get_args();
}

function url($module) {
  return "./?module=$module";
}

?>
