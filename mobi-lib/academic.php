<?php

class AcademicCalendar {
  
  public $years = array();
  private $current_year;
  private $current_month;
  private $current_day;

  public function year($year) {
    $this->current_year = $year;
    $this->years[$year] = array();
    return $this;
  }

  public function month($month) {
    $this->current_month = $month;
    $this->years[$this->current_year][$month] = array();
    return $this;
  }

  public function day($first, $second=NULL) {
    $this->current_day = new DayList($first, $second);
    $this->years[$this->current_year][$this->current_month][] = $this->current_day;
    return $this;
  }

  function item($str) {
    $this->current_day->add_item($str);
    return $this;
  }

  function hilite_item($str1, $str2="") {
    $this->current_day->add_hilite_item($str1, $str2);
    return $this;
  }

  function title_item($str1, $str2) {
    $this->current_day->add_title_item($str1, $str2);
    return $this;
  }
}

class DayList {
  private $first;
  private $second;
  public $items = array();

  public function __construct($first, $second) {
    $this->first = $first;
    $this->second = $second;
  }

  public function add_item($str) {
    $this->items[] = new PlainItem($str);
  }

  public function add_title_item($str1, $str2) {
    $this->items[] = new TitleItem($str1, $str2);
  }

  public function add_hilite_item($str1, $str2) {
    $this->items[] = new HiliteItem($str1, $str2);
  }

  public function day_text($year, $month) {
    $month = Month($month);
    $time1 = strtotime("$month {$this->first}, $year");
    if($this->second) {
      $time2 = strtotime("$month {$this->second}, $year");
    }
    
    $day1 = date('l', $time1);
    if($time2) {
      $day2 = date('l', $time2);
      if($this->second-$this->first == 1) {
        return "$day1,$day2 $month {$this->first},{$this->second}";
      } else {
        return "$day1-$day2 $month {$this->first}-{$this->second}";
      }
    } else {
      return "$day1 $month {$this->first}";
    }
  } 
}

abstract class Item { 
  abstract public function html();
}

class PlainItem extends Item {

  private $item;
  
  public function __construct($item) {
    $this->item = $item;
  }

  public function html() {
    return htmlentities($this->item);
  }

}

class HiliteItem extends Item {

  private $hilite;
  private $plain;
  
  public function __construct($hilite, $plain) {
    $this->hilite = $hilite;
    $this->plain = $plain;
  }

  public function html() {
    return '<strong>' . htmlentities($this->hilite) . '</strong> ' . htmlentities($this->plain);
  }

}

class TitleItem extends Item {

  private $title;
  private $body;
  
  public function __construct($title, $body) {
    $this->title = $title;
    $this->body = $body;
  }

  public function html() {
    return '<strong>' . htmlentities($this->title) . ':</strong> ' . htmlentities($this->body);
  }


}

function Month($month) {
  return ucwords(strtolower($month));
}
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
$academic = new AcademicCalendar();
require LIBDIR . "academic_data.php";

?>