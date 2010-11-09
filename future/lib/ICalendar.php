<?php

/*
 * The ICal* classes in this file together partially implement RFC 2445.
 *
 */

require_once('TimeRange.php');

class ICalendarException extends Exception {
}

abstract class ICalObject {
  protected $classname;

  public function get_name() {
    return $this->classname;
  }

  public function set_attribute($attr, $value, $params=null) {
  }

  public function get_attribute($attr) {
  }
}

class ICalTodo extends ICalObject {
  public function __construct() {
    $this->classname = 'VTODO';
  }
}

class ICalJournal extends ICalObject {
  public function __construct() {
    $this->classname = 'VJOURNAL';
  }
}

class ICalFreeBusy extends ICalObject {
  public function __construct() {
    $this->classname = 'VFREEBUSY';
  }
}

class ICalTimeZone extends ICalObject {
  public $tzid;

  public function __construct() {
    $this->classname = 'VTIMEZONE';
  }

  public function set_attribute($attr, $value, $params=NULL) {
    switch ($attr) {
    case 'TZID':
      $this->tzid = $value;
      break;
    }
  }
}

class ICalDaylight extends ICalTimeZone {
  public function __construct() {
    $this->classname = 'DAYLIGHT';
  }
}

class ICalStandard extends ICalTimeZone {
  public function __construct() {
    $this->classname = 'STANDARD';
  }
}

class ICalAlarm extends ICalObject {
  public function __construct() {
    $this->classname = 'VALARM';
  }
}

class ICalEvent extends ICalObject {

  protected $uid;
  protected $recurid = NULL;
  protected $range;
  protected $summary;
  protected $description;
  protected $location;
  protected $tzid;
  protected $url;
  protected $updated;
  protected $categories=array();
  protected $properties=array();

  // attributes that will be populated only if recurring
  protected $recur = FALSE;
  protected $occurrences; // start times only
  protected $until;
  protected $exdates = Array();
  protected $incrementor;
  protected $interval = 1;
  
  public function getEventCategories() {
    return array();
  }

  protected function standardAttributes()
  {
    return array(
    'summary', 
    'location', 
    'description', 
    'uid', 
    'start', 
    'end', 
    'url', 
    'categories',
    'datetime',
  );
  }
  
  public function apiArray()
  {
    
	 $arr= array (
	 	'id'=>crc32($this->get_uid()) >>1,
	 	'title'=>$this->get_summary(),
	 	'start'=>$this->get_start(),
	 	'end'=>$this->get_end()
	 );

    if ($urlLink = $this->get_url()) {
        $arr['url'] = $urlLink;
    }
    if ($location = $this->get_location()) {
        $arr['location'] = $location;
    }
    if ($description = $this->get_description()) {
        $arr['description'] = $description;
    }
    
	 return $arr;

  }
  
  public function get_uid() {
    return $this->uid;
  }

  public function get_recurid() {
    return $this->recurid;
  }

  public function get_range() {
    return $this->range;
  }

  public function get_series_range() {
    return new TimeRange($this->get_start(), $this->get_end());
  }

  public function get_start() {
    return $this->range->get_start();
  }

  public function get_end() {
    if ($this->recur) {
      return $this->until;
    } else {
      return $this->range->get_end();
    }
  }

  public function get_summary() {
    return $this->summary;
  }

  public function get_url() {
    return $this->url;
  }

  public function get_description() {
    return $this->description;
  }

  public function get_location() {
    return $this->location;
  }
  
  public function get_categories() {
    return $this->categories;
  }

  public function is_recurring() {
    return $this->recur;
  }

  public function get_occurrences() {
    return $this->occurrences;
  }

  protected function compare_ranges(TimeRange $range, $compare_type) {
    if ($this->recur) {
      // check if $range is within this series at all
      $event_range = new TimeRange($this->get_start(), $this->get_end());
      if (!$event_range->$compare_type($range)) {
    return Array();
      } else {
    $duration = $this->range->get_end() - $this->range->get_start();
    $results = Array();
    $starts = $this->occurrences;
    while ($starts[0] < $this->until) {
      foreach ($starts as $start) {
        if ($start > $this->until)
          break 2;
        if (in_array($start, $this->exdates))
          continue;
        $event_range = new TimeRange($start, $start + $duration);
        if ($event_range->$compare_type($range)) {
          $results[] = new ICalEvent($this->summary, $event_range);
        }
      }
      $starts = $this->increment_set($starts);
    }
      }
      return $results;
    } else {
      return $this->range->$compare_type($range);
    }
  }

  public function overlaps(TimeRange $range) {
    return $this->compare_ranges($range, 'overlaps');
  }

  public function contains(TimeRange $range) {
    return $this->compare_ranges($range, 'contains');
  }

  public function contained_by(TimeRange $range) {
    return $this->compare_ranges($range, 'contained_by');
  }
  
  public function get_attribute($attr) {
    if (in_array($attr, $this->standardAttributes())) {
      if ($attr == 'datetime') {
        return $this->range;
      } else {
        $method = "get_$attr";
        return $this->$method();
      }
    } else {
      return isset($this->properties[$attr]) ? $this->properties[$attr] : null;
    }
  }
  
  public function get_all_attributes() {
    return array_merge($this->standardAttributes(), array_keys($this->properties));
  }

  public function set_attribute($attr, $value, $params=NULL) {
    switch ($attr) {
    case 'UID':
      if (strpos($value, '@') !== FALSE) {
        $this->uid .= substr($value, 0, strpos($value, '@'));
      } else {
        $this->uid .= $value;
      }
      break;
    case 'RECURRENCE-ID':
      $this->recurid = $value;
      break;
    case 'DESCRIPTION':
      $this->description = iCalendar::ical_unescape_text($value);
      break;
    case 'LOCATION':
      $this->location = iCalendar::ical_unescape_text($value);
      break;
    case 'SUMMARY':
      $this->summary = iCalendar::ical_unescape_text($value);
      break;
    case 'CATEGORIES':
        $categories = explode(',', $value);
        $this->categories = array();
        foreach ($categories as $category) {
            $this->categories[] = trim(iCalendar::ical_unescape_text($category));
        }
        break;
    case 'URL':
        $this->url = iCalendar::ical_unescape_text($value);
        break;
    case 'DTSTAMP':

        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }
      $this->updated = $datetime->format('U');
        break;
    case 'DTSTART':
    case 'DTEND':
        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }

      $timestamp = $datetime->format('U');

      if (!$this->range) {
        if (strpos($value, 'T')!== FALSE) {
            $this->range = new TimeRange($timestamp);
        } else {
            $this->range = new DayRange($timestamp);
        }
      } else {
        if ($attr=='DTEND' && ($timestamp > $this->get_start()) && (($timestamp - $this->get_start()) % 86400 == 0)) {
            // make all day events end at 11:59:59 so they don't overlap next day
            $timestamp -= 1;
         }
         
         switch ($attr)
         {
            case 'DTSTART':
                $this->range->set_start($timestamp);
                break;
            case 'DTEND':
                $this->range->set_end($timestamp);
                break;
         }
      }
      break;
    case 'DURATION':
      // todo:
      // if this tag comes before DTSTART we will break
      $this->range->set_end($this->get_start() + $value);
      break;
    case 'RRULE':
      $this->add_rrule($value);
      break;
    case 'EXDATE':
        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }

        $this->exdates[] = $datetime->format('U');
      break;
    case 'TZID': // this only gets called by ICalendar::__construct
      $this->tzid = $value;
      break;
    default:
        $this->properties[$attr] = iCalendar::ical_unescape_text($value);
        break;
    }
  }

  protected function increment_set($set) {
    return array_map(
      $this->incrementor,
      $set,
      array_fill(0, count($set), $this->interval)
      );
  }

  protected function add_rrule($rrule_string) {
    $rules = explode(';', $rrule_string);
    $this->recur = TRUE;
    if ($this->occurrences === NULL) {
      $this->occurrences = Array();
    }

    // read attributes from rrule_string
    $limit_type = '';
    $limit = '';

    $incrementors = Array(
      'SECONDLY' => 'increment_second',
      'MINUTELY' => 'increment_minute',
      'HOURLY' => 'increment_hour',
      'DAILY' => 'increment_day',   
      'WEEKLY' => 'increment_week',
      'MONTHLY' => 'increment_month',
      'YEARLY' => 'increment_year',
      );

    $occurs_by_list = Array();

    foreach ($rules as $rule) {
      $namevalue = explode('=', $rule);
      $rulename = $namevalue[0];
      $rulevalue = $namevalue[1];
      switch ($rulename) {
      case 'FREQ': // always present
    $this->incrementor = $incrementors[$rulevalue];
    break;
      case 'INTERVAL':
    $this->interval = $rulevalue;
        break;
      case 'UNTIL':
    $limit_type = 'UNTIL';
    $this->until = ICalendar::ical2unix($rulevalue);
    break;
      case 'COUNT':
    $limit_type = 'COUNT';
    $limit = $rulevalue;
    break;
      case (substr($rulename, 0, 2) == 'BY'):
    $occurs_by_list[$rulename] = explode(',', $rulevalue);
    break;
      }
    }
    // finished reading attributes from rrule_string

    $occursByTypes = Array(
      'BYMONTH' => Array('func' => 'increment_month', 'format' => 'n'),
      'BYWEEKNO' => Array('func' => 'increment_week', 'format' => 'W'),
      'BYYEARDAY' => Array('func' => 'increment_day', 'format' => 'z'),
      'BYMONTHDAY' => Array('func'=> 'increment_day', 'format' => 'j'),
      'BYDAY' => Array('func' => 'increment_day', 'format' => 'w'),
      'BYHOUR' => Array('func' => 'increment_hour', 'format' => 'G'),
      'BYMINUTE' => Array('func' => 'increment_minute', 'format' => 'i'),
      'BYSECOND' => Array('func' => 'increment_second', 'format' => 's'),
      );

    // create the first occurrence set
    $occur_unit = Array($this->range->get_start());
    foreach ($occursByTypes as $byfreq => $attribs) {
      // we loop through occurByTypes so we can be sure to
      // act on each "by frequency" rule in order of decreasing grain size

      if (array_key_exists($byfreq, $occurs_by_list)) {
    $new_occur_unit = Array();

    // every "when" within the "by frequency"
    // e.g. MO,TU,WE for BYDAY
    // needs to be a separate element of the occurrence set
    foreach($occurs_by_list[$byfreq] as $when) {
      $occurs_when = ($byfreq == 'BYDAY') ? ICalendar::$dayIndex[$when] : $when;

      // if the set of occurrences already has multiple elements
      // they will be multiplied
      // e.g. MO,TU for BYDAY and 1,2,3 for BYMONTH
      // yields 6 elements in the occurence set
      foreach ($occur_unit as $start) {     
        $count = 0; // for debugging below
        while (intval(date($attribs['format'], $start)) != $occurs_when) {
          $start = call_user_func($attribs['func'], $start);

          // haven't seen this happen yet but who knows
          if ($count > 366) {
        throw new ICalendarException("maximum loop count exceeded");
          }
          $count += 1;
        }
        $new_occur_unit[] = $start;
      }
    }
    $occur_unit = $new_occur_unit;
      }
    }

    // BYSETPOS limits us to particular indices of the occurrence set
    if (array_key_exists('BYSETPOS', $occurs_by_list)) {
      $new_occur_unit = Array();
      $setposlist = $occurs_by_list['BYSETPOS'];
      foreach ($setposlist as $setpos) {
    if ($setpos < 0) {
      $setpos = count($occur_unit) + $setpos;
    } else {
      $setpos = $setpos - 1;
    }
    $new_occur_unit[] = $occur_unit[$setpos];
      }
      $occur_unit = $new_occur_unit;
    }

    $this->occurrences = $occur_unit;

    // duplicate the occurrence set for COUNT limits
    // this is approximate
    if ($limit_type == 'COUNT') {
      $num_increments = $limit / count($this_occurrences);
      $end = end($occur_unit);
      while ($num_increments > 0) {
    $end = $this->incrementor($end);
    $num_increments -= 1;
      }
      $this->until = $end;
    }
  }
  
  private function addLine(&$string, $prop, $value)
  {
        $string .= sprintf("%s:%s\n", $prop, iCalendar::ical_escape_text($value));
  }

  public function outputICS()
  {
        $output_string = '';
        $this->addLine($output_string, "BEGIN", 'VEVENT');
        if ($this->uid) {
            $this->addLine($output_string, "UID", $this->uid);
        }

        if ($this->summary) {
            $this->addLine($output_string, "SUMMARY", $this->summary);
        }

        if ($this->location) {
            $this->addLine($output_string, "LOCATION", $this->location);
        }

        if ($this->description) {
            $this->addLine($output_string, "DECRIPTION", $this->description);
        }
        
        if ($this->range) {
            if (is_a($this->range, 'DayRange'))  {
                $this->addLine($output_string, "DTSTART", date('Ymd', $this->range->get_start()));
            } else {
                $this->addLine($output_string, "DTSTART", strftime('%Y%m%dT%H%M%S', $this->range->get_start()));
                $this->addLine($output_string, "DTEND", strftime('%Y%m%dT%H%M%S', $this->range->get_end()));
            }
        }
        
        $this->addLine($output_string, 'END', 'VEVENT');
        return $output_string;
  }

  public function __construct($summary=NULL, TimeRange $range=NULL) {
    $this->classname = 'VEVENT';
    if ($summary !== NULL) {
      $this->summary = $summary;
    }
    if ($range !== NULL) {
      $this->range = $range;
    }
  }
}

class ICalendar extends ICalObject {
  protected $properties;
  public $timezone = NULL;
  protected $events;

  // corresponds to date('w', $date)
  public static $dayIndex = Array('SU'=>0, 'MO'=>1, 'TU'=>2, 'WE'=>3, 'TH'=>4, 'FR'=>5, 'SA'=>6 );

  public static function ical2unix($icaltime, $tzid=NULL) {
    if ($tzid === NULL) {
      $time = new DateTime($icaltime);
    } else {
      $tz = new DateTimeZone($tzid);
      $time = new DateTime($icaltime, $tz);
    }
    return $time->format('U');
  }

  public function search_events($title=NULL, TimeRange $range=NULL) {
    $events = Array();
    foreach ($this->events as $id => $event){
      if ($event->get_recurid() !== NULL) // event is a duplicate
    continue;
      if (($title === NULL || stripos($event->get_summary(), $title) !== FALSE)
      && ($range === NULL || $event->overlaps($range))) {
    $events[] = $event;
      }
    }
    return $events;
  }

  public function search_by_range(Timerange $range) {
    /*
    $events = Array();
    foreach ($this->events as $id => $event){
      if ($event->get_recurid() !== NULL) // event is a duplicate
    continue;
      if ($event->overlaps($range)) {
    $events[] = $event;
      }
    }
    return $events;
    */
    return $this->search_events(NULL, $range);
  }

  public function search_by_title($title) {
    /*
    $events = Array();
    foreach ($this->events as $id => $event) {
      if ($event->get_recurid() !== NULL) // event is a duplicate
    continue;
      if (stripos($event->get_summary(), $title) !== FALSE) {
    $events[] = $event;
      }
    }
    return $events;
    */
    return $this->search_events($title, NULL);
  }

  public function get_day_events($time=NULL) {
    if ($time === NULL) {
      $time = time();
    }
    $day = new DayRange($time, $this->timezone->tzid);

    $events = Array();
    foreach ($this->events as $id => $event) {
      if ($event->is_recurring()) {
    $day_events = $event->overlaps($day);
    foreach ($day_events as $day_event) {
      $events[] = $day_event;
    }
      } elseif ($event->overlaps($day)) {
    $events[] = $event;
      }
    }
    return $events;
  }

  public function add_event(ICalEvent $event) {
    $uid = $event->get_uid();
    $this->events[$uid] = $event;
  }
  
  public function get_events()
  {
    return $this->events;
  }
  
  public function get_extra_attributes()
  {
  }

  public function set_attribute($attr, $value)
  {
    $this->properties[$attr] = $value;
  }

  public function __construct($url=FALSE) {
    $this->properties = Array();
    $this->events = Array();
    $this->classname = 'VCALENDAR';
  }

  private function addLine(&$string, $prop, $value)
  {
        $string .= sprintf("%s:%s\n", $prop, self::ical_escape_text($value));
  }

  public function ical_escape_text($text) {
    $text = str_replace(array("\"","\\",",",";","\n"), array("DQUOTE","\\\\", "\,","\;","\\n"), $text);
    return $text;
  }

  public function ical_unescape_text($text) {
    $text = str_replace(array("DQUOTE","\\\\", "\,","\;","\\n"), array("\"","\\",",",";","\n"), $text);
    return $text;
  }
  
  public function outputICS()
  {
        $output_string = '';
        $this->addLine($output_string, 'BEGIN','VCALENDAR');
        $this->addLine($output_string, 'CALSCALE','GREGORIAN');
        foreach ($this->events as $event) {
            $output_string .= $event->outputICS();
        }

        $output_string .= 'END:VCALENDAR';
        return $output_string;
  }

}

