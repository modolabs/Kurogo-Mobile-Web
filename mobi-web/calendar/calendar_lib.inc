<?php

function day_info($time, $offset=0) {
  $time += $offset * 24 * 60 * 60;
  return array(
    "weekday"       => date('l', $time),
    "month"         => date('F', $time),
    "month_3Let"    => date('M', $time),
    "day_num"       => date('j', $time),
    "year"          => date('Y', $time),
    "month_num"     => date('m', $time),
    "day_3Let"      => date('D', $time),
    "day_num_2dig"  => date('d', $time),
    "date"          => date('Y/m/d', $time),
    "time"          => strtotime(date("Y-m-d 12:00:00", $time))
  );
}

class SearchOptions {
  private static $options = array(
    array("phrase" => "in the next 7 days",   "offset" => 7),
    //array("phrase" => "in the next 15 days",  "offset" => 15),
    //array("phrase" => "in the next 30 days",  "offset" => 30),
    //array("phrase" => "in the past 15 days",  "offset" => -15),
    //array("phrase" => "in the past 30 days",  "offset" => -30),
    //array("phrase" => "this school term",     "offset" => "term"),
    //array("phrase" => "this school year",     "offset" => "year")
  );

  public static function get_options($selected = 0) {
    $out_options = self::$options;
    $out_options[$selected]['selected'] = true;
    return $out_options;
  }

  public static function search_dates($option) {
    $offset = self::$options[$option]["offset"];
    $time = time();
    $day1 = day_info($time);

    if(is_int($offset)) {
      $day2 = day_info($time, $offset);
      if($offset > 0) {
        return array("start" => $day1['date'], "end" => $day2['date']);
      } else {
        return array("start" => $day2['date'], "end" => $day1['date']); 
      }
    } else {
      switch($offset) {
        case "term":
          if($day1['month_num'] < 7) {
            $end_date = "{$day1['year']}/07/01";
	  } else {
            $end_date = "{$day1['year']}/12/31";
          }
          break;

        case "year": 
          if($day1['month_num'] < 7) {
            $end_date = "{$day1['year']}/07/01";
	  } else {
            $year = $day1['year'] + 1;
            $end_date = "$year/07/01";
          }
          break;
      }    
      return array("start" => $day1['date'], "end" => $end_date); 
    }
  }
}

// URL DEFINITIONS
function dayURL($day, $type) {
  return "day.php?".http_build_query(array(
    'time' => $day['time'],
    'type' => $type,
  ));
}

function categoryDayURL($day, $categoryID, $name) {
  return "category.php?".http_build_query(array(
    'time' => $day['time'],
    'id' => $categoryID,
    'name' => $name, 
  ));
}

function academicURL($year) {
  return "academic.php?".http_build_query(array(
    'year' => $year,
  ));
}

function holidaysURL($year=NULL) {
  if(!$year) {
    $year = $_REQUEST['year'];
  }
  return "holidays.php?".http_build_query(array(
    'year' => $year,
  ));
}

function religiousURL($year=NULL) {
  if(!$year) {
    $year = $_REQUEST['year'];
  }
  return "holidays.php?".http_build_query(array(
    'page' => 'religious',
    'year' => $year,
  ));
}

function categorysURL() {
  return "categorys.php";
}

function categoryURL($category) {
  $id = is_array($category) ? $category['catid'] : $category->get_cat_id();
  $name = is_array($category) ? $category['name'] : ucname($category->get_name());
  return "category.php?".http_build_query(array(
    'id' => $id,
    'name' => $name,
  ));
}

function subCategorysURL($category) {
  $id = is_array($category) ? $category['catid'] : $category->get_cat_id();
  return "sub-categorys.php?".http_build_query(array(
    'id' => $id,
  ));
}

function detailURL($event) {
  $args = array(
    'id' => $event->get_uid(),
    'time' => isset($_REQUEST['time']) ? $_REQUEST['time'] : time(),
  );
  if (isset($GLOBALS['calendarExtraDetailParams'])) {
    $args = array_merge($args, $GLOBALS['calendarExtraDetailParams']);
  }
  
  return "detail.php?".http_build_query($args);
}

// convenience functions

// for academic and holiday calendars
function formatDayTitle(ICalEvent $event) {
  $start = $event->get_start();
  $end = $event->get_end();
  if ($start != $end) {
    // all of acad calendar time units are in days so
    // we can end 23:59:00 instead of 00:00:00 next day
    $timeRange = new TimeRange($start, $end - 1);
    $dateTitle = $timeRange->format('l F j');
  } else {
    $dateTitle = date('l F j', $start);
  }
  return $dateTitle;
}

function formatDayTitleForAcademicEvents(ICalEvent $event) {
    $start = $event->get_start();
    $end = $event->get_end();
    
    // Trumba data quirk for single day events.  Academic calendar events are
    // all day affairs (at least at the moment), and that's being captured as 
    // a midnight to midnight event.
    $ONE_DAY = 24*60*60;
    if ($end - $start < $ONE_DAY) {
        $dateTitle = date('l F j', $end);
    }
    else {
        $dateTitle = date('l F j', $start + $ONE_DAY) . " - " . date('l F j', $end);
    }
    return $dateTitle;
}


function timeText($event) {
  if ($event->get_end() - $event->get_start() == -1) {
    return $event->get_range()->format('D M j').' '.date('g:i a', $event->get_start());
  }
  return $event->get_range()->format('D M j g:i a');

  /*
  $out = substr($event->start->weekday, 0, 3) . ' ';
  $out .= substr($event->start->monthname, 0, 3) . ' ';
  $out .= (int)$event->start->day . ' ';

  $out .= MIT_Calendar::timeText($event);
  return $out;
  */
}
  
function briefLocation($event) {
  //if($loc = $event->shortloc) {
  //  return $loc;
  //} else {
    return $event->get_location();
  //}
}

function ucname($name) {
  $new_words = array();
  foreach(explode(' ', $name) as $word) {
    $new_word = array();
    foreach(explode('/', $word) as $sub_word) {
      $new_word[] = ucwords($sub_word);
    }
    $new_word = implode('/', $new_word);
    $new_words[] = $new_word;
  } 
  return implode(' ', $new_words);
}

class CalendarForm extends Form {

  protected $catid;
  protected $search_options;

  public function __construct(Page $page, $search_options, $catid=NULL) {
    $this->branch = $page->branch;
    $this->catid = $catid;
    $this->search_options = $search_options;
  }

  public function out($total=NULL) {
    $catid = $this->catid;
    $search_options = $this->search_options;
    require "{$this->branch}/form.html";
  }
}

?>
