<?

/*
 * The ICal* classes in this file together partially implement RFC 2445.
 *
 */

require_once('TimeRange.php');
require_once('HarvardContactInfoParser.php');

class ICalendarException extends Exception {
}

abstract class ICalObject {
  protected $classname;

  public function get_name() {
    return $this->classname;
  }

  public function set_attribute($attr, $value, $param_name=NULL, $param_value=NULL) {
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

  public function set_attribute($attr, $value, $param_name=NULL, $param_value=NULL) {
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

  private $uid;
  private $recurid = NULL;
  private $range;
  private $url;
  private $summary;
  private $description;
  private $location;
  private $tzid;
  private $categories;
  private $customFields = array();

  // attributes that will be populated only if recurring
  private $recur = FALSE;
  private $occurrences; // start times only
  private $until;
  private $exdates = Array();
  private $incrementor;
  private $interval = 1;

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

  public function set_start($str) {
    $this->range->set_start($str);
  }
 

  public function get_end() {
    if ($this->recur) {
      return $this->until;
    } else {
      return $this->range->get_end();
    }
  }

    public function set_end($ed) {
    $this->range->set_end($ed);
  }

  public function get_url() {
      return $this->url;
  }

  public function get_summary() {
    return $this->summary;
  }

  public function get_description() {
    return $this->description;
  }

  public function get_location() {
    return $this->location;
  }

  public function get_customFields() {
    return $this->customFields;
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

  private function compare_ranges(TimeRange $range, $compare_type) {
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

  public function set_attribute($attr, $value, $param_name=NULL, $param_value=NULL) {
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
      $this->description = str_replace('\\',"", str_replace('\n', "", $value));
      break;
    case 'LOCATION':
      $this->location = str_replace('\\', "", $value);
      break;

    case 'CATEGORIES':
      $this->categories = $value;
      break;

    case 'X-TRUMBA-CUSTOMFIELD':
        if ($param_value == "\"Contact Info\"") {
               $contact = new ContactInfo($value);
                $this->customFields[$param_value] = $contact;
        }
        else {
             $this->customFields[$param_value] = $value;
        }
      break;

    case 'URL':
        $pos = strpos("http",$value);

    if($pos === false) {
        $urlLink = "http://" .$value;
    }
    else {
        $urlLink = $value;
    }

    $urlLink = str_replace("http://http://", "http://", $urlLink);
        $this->url = $urlLink;
        break;

    case 'SUMMARY':
      $this->summary = str_replace('\\', "", $value);
      break;
    case 'DTSTART':
      // for dtstart and dtend the param is always time zone id

        if ($param_value == 'DATE') {
            $value = $value .'T000000';
        }
        
       $start = ICalendar::ical2unix($value, NULL);
     // $start = ICalendar::ical2unix($value, $param_value);
      if (!$this->range) {
	$this->range = new TimeRange($start);
      } else {
	$this->range->set_start($start);
      }

      break;

    case 'DTEND':
      //$end = ICalendar::ical2unix($value, $param_value);

      if ($param_value == 'DATE'){
            $value = $value .'T000000';
        }
           
                
      $end = ICalendar::ical2unix($value, NULL);
      if (!$this->range) {
	$this->range = new TimeRange($end);
      } else {
	if (($end - $this->get_start()) % 86400 == 0) {
	  // make all day events end at 11:59:59 so they don't overlap next day
	  $end -= 1;
	}
	$this->range->set_end($end);
      }
      break;
    case 'DURATION':
      // todo:
      // if this tag comes before DTSTART we will break
      $this->range->set_end($this->get_start() + $value);
      break;
    case 'RRULE':
      //$this->add_rrule($value);
      break;
    case 'EXDATE':
      $this->exdates[] = ICalendar::ical2unix($value, $param_value);
      break;
    case 'TZID': // this only gets called by ICalendar::__construct
      $this->tzid = $value;
      break;
    default:
      break;
    }
  }

  private function increment_set($set) {
    return array_map(
      $this->incrementor,
      $set,
      array_fill(0, count($set), $this->interval)
      );
  }

  private function add_rrule($rrule_string) {
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
      $time = new DateTime($icaltime, new DateTimeZone('America/New_York'));
    } else {
      $tz = new DateTimeZone($tzid);
      $time = new DateTime($icaltime, $tz);
    }
    $time = new DateTime($icaltime, new DateTimeZone('America/New_York'));
    return (datetime2unix($time) - (5*60*60));
  }

  public function get_event($uid) {
    foreach ($this->events as $event) {
      if ($event->get_uid() == $uid || (crc32($event->get_uid()) >> 1) == $uid) {
        return $event;
      }
    }
  }

  public function search_events($title=NULL, TimeRange $range=NULL) {
    $events = Array();
    
    foreach ($this->events as $id => $event){
      /*if ($event->get_recurid() !== NULL) // event is a duplicate
	continue;*/
     /* if (($title === NULL || stripos($event->get_summary(), $title) !== FALSE)
	  && ($range === NULL || $event->overlaps($range))) {
	$events[] = $event;
      }*/

      /*if ($title === NULL || stripos($event->get_summary(), $title) !== FALSE)
              $events[] = $event;*/

        $events[] = $event;
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
  
    $day = new DayRange($time, isset($this->timezone) ? $this->timezone->tzid : NULL);

    $events = Array();
    foreach ($this->events as $id => $event) {
      /* Making sure the events that start at 0000-0500hrs GMT
      	 are still correctly captured as today's events */
        if  ((($event->get_start() - 5*60*60 >= $day->get_start()) &&
                ($event->get_start() - 5*60*60 <= $day->get_end())) ||

               (($event->get_end() - 5*60*60 >= $day->get_start()) &&
                ($event->get_end() - 5*60*60 <= $day->get_end())) ||

                (($event->get_start() - 5*60*60 <= $day->get_start()) &&
                ($event->get_end()  - 5*60*60 >= $day->get_end()))) {

                    $events[] = $event;
            
                }

    }
    return $events;
  }

  public function add_event(ICalEvent $event) {
    $uid = $event->get_uid();
    $this->events[$uid] = $event;
  }

  public function set_attribute($attr, $value, $param_name=NULL, $param_value=NULL) {
    $this->properties[$attr] = $value;
  }

  public function __construct($url=FALSE) {
    $this->properties = Array();
    $this->events = Array();
    $this->classname = 'VCALENDAR';
    
    if ($url !== FALSE) {
      $this->read_from_url($url);
    }
  }

  protected function read_from_url($url) {
    $nesting = Array();
    $lines = explode("\n", $this->unfold(file_get_contents($url)));
    
    foreach ($lines as $line) {
      $contentline = $this->contentline($line);

      $contentname = $contentline['name'];

      $value = $contentline['value'];
      switch($contentname) {
      case 'BEGIN':
	switch ($value) {
	case 'VEVENT':
	  $nesting[] = new ICalEvent();
	  break;
	case 'VCALENDAR':
	  $nesting[] = $this;
	  break;
	case 'VTIMEZONE':
	  $nesting[] = new ICalTimeZone();
	  break;
	case 'DAYLIGHT':
	  $nesting[] = new ICalDaylight();
	  break;
	case 'STANDARD':
	  $nesting[] = new ICalStandard();
	  break;
	case 'VTODO':
	  $nesting[] = new ICalTodo();
	  break;
	case 'VJOURNAL':
	  $nesting[] = new ICalJournal();
	  break;
	case 'VFREEBUSY':
	  $nesting[] = new ICalFreeBusy();
	  break;
	case 'VALARM':
	  $nesting[] = new ICalAlarm();
	  break;
	default:
	  throw new ICalendarException('unknown component type ' . $value);
	  break;
	}
	break;
      case 'END':
	$last_object = array_pop($nesting);
	$last_obj_name = $last_object->get_name();
	if ($last_obj_name != $value) {
	  throw new ICalendarException("BEGIN $last_obj_name ended by END $value");
	}
	switch ($value) {
	case 'VEVENT':
	  $id = $last_object->get_start();
	  $last_object->set_attribute('TZID', isset($this->timezone) ? $this->timezone->tzid : NULL);
	  while (array_key_exists($id, $this->events)) {
	    $id += 1;
	  }
	  $this->events[$id] = $last_object;
	  break;
	case 'VTIMEZONE':
	  $this->timezone = $last_object;
	  break;
	case 'VCALENDAR':
	  break 3;
	}
	break;
      default:
	if (array_key_exists('param_name', $contentline)) {
	 $param_name = $contentline['param_name'];
	 $param_value = $contentline['param_value'];
	  end($nesting)->set_attribute($contentname, $value, $param_name, $param_value);
	}
	else {
            if ($contentname !== 'RRULE')
                end($nesting)->set_attribute($contentname, $value);
	}
	break;
      }
    }
  }

  protected function contentline($line) {
    $contentline = Array();
    $sep_pos = strpos($line, ':');
    $nameparam = explode(';', substr($line, 0, $sep_pos));
    $contentline['name'] = $nameparam[0];
    if (count($nameparam) > 1) {
      $param = explode('=', $nameparam[1]);
      $contentline['param_name'] = $param[0];
      $contentline['param_value'] = $param[1];
    }
    $contentline['value'] = trim(substr($line, $sep_pos + 1));
    return $contentline;
  }

  protected function unfold($text) {
    return str_replace("\r\n ","",$text);  
  }

}

?>
