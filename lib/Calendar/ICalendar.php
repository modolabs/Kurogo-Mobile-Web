<?php
/**
  * ICalendar
  * The ICal* classes in this file together partially implement RFC 2445.
  * @package ExternalData
  * @subpackage Calendar
 */

/**
 * ICalendar
 * @package Exceptions
 */
class ICalendarException extends Exception {
}

/**
  * @package ExternalData
  * @subpackage Calendar
  */
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

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalTodo extends ICalObject {
  public function __construct() {
    $this->classname = 'VTODO';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalJournal extends ICalObject {
  public function __construct() {
    $this->classname = 'VJOURNAL';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalFreeBusy extends ICalObject {
  public function __construct() {
    $this->classname = 'VFREEBUSY';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
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

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalDaylight extends ICalTimeZone {
  public function __construct() {
    $this->classname = 'DAYLIGHT';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalStandard extends ICalTimeZone {
  public function __construct() {
    $this->classname = 'STANDARD';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalAlarm extends ICalObject {
  public function __construct() {
    $this->classname = 'VALARM';
  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalEvent extends ICalObject implements KurogoObject {

  protected $uid;
  protected $sequence;
  protected $recurid = NULL;
  protected $range;
  protected $starttime;
  protected $summary;
  protected $description;
  protected $location;
  protected $tzid;
  protected $url;
  protected $created;
  protected $updated;
  protected $dtstamp;
  protected $status;
  protected $transparency;
  protected $categories=array();
  protected $properties=array();
  protected $rrules=array();
  protected $exdates = array();

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

  public function get_tzid() {
    return $this->tzid;
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
    return $this->range->get_end();
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
    return count($this->rrules)>0;
  }

  /* returns an array of occurrences that occur in the given range */
  public function getOccurrencesInRange(TimeRange $range, $limit=null)
  {
    $occurrences = array();

    /* check the "base" event */    
    if ($this->range->overlaps($range)) {
        $occurrences[$this->get_start()] = $this;
    }
    
    foreach ($this->rrules as $rrule) {
        foreach ($rrule->occurrences($this, $range, $limit) as $occurrence) {
            if (!in_array($occurrence->get_start(), $this->exdates)) {
                $occurrences[$occurrence->get_start()] = $occurrence;
            }
        }
    }
    
    ksort($occurrences);
    return array_values($occurrences);
  }

  public function overlaps(TimeRange $range) {
    return $this->range->overlaps($range);
  }

  public function contains(TimeRange $range) {
    return $this->range->contains($range);
  }

  public function contained_by(TimeRange $range) {
    return $this->range->contained_by($range);
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
  
  public function setRange(TimeRange $range) {
    $this->range = $range;
    $this->starttime = $range->get_start();
  }
  
  public function setSummary($summary) {
    $this->summary = $summary;
  }
  
  public function setDescription($description) {
    $this->description = $description;
  }

  public function setUID($uid) {
    $this->uid = $uid;
  }
  
  public function setLocation($location) {
    $this->location = $location;
  }
  
  public function set_attribute($attr, $value, $params=NULL) {
    switch ($attr) {
    case 'UID':
      if (strpos($value, '@') !== FALSE) {
        $this->setUID(substr($value, 0, strpos($value, '@')));
      } else {
        $this->setUID($value);
      }
      break;
    case 'RECURRENCE-ID':
      $this->recurid = $value;
      break;
    case 'DESCRIPTION':
      $this->setDescription(iCalendar::ical_unescape_text($value));
      break;
    case 'LOCATION':
      $this->setLocation(iCalendar::ical_unescape_text($value));
      break;
    case 'SUMMARY':
      $this->setSummary(iCalendar::ical_unescape_text($value));
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
    case 'SEQUENCE':
        $this->sequence = $value;
        break;
    case 'STATUS':
        $this->status = $value;
        break;
    case 'CREATED':
        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }
        $this->created = $datetime->format('U');
        break;
    case 'LAST-MODIFIED':
        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }
        $this->updated = $datetime->format('U');
        break;
    case 'DTSTAMP':
        if (array_key_exists('TZID', $params)) {
            $datetime = new DateTime($value, new DateTimeZone($params['TZID']));
        } else {
            $datetime = new DateTime($value);
        }
      $this->dtstamp = $datetime->format('U');
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
            $this->setRange(new TimeRange($timestamp));
        } else {
            $this->setRange(new DayRange($timestamp));
        }
        
        if (isset($this->properties['duration'])) {
              $this->range->set_end($this->get_start() + $this->properties['duration']);
              unset($this->properties['duration']);
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
                $this->starttime= $timestamp;
                break;
            case 'DTEND':
                $this->range->set_end($timestamp);
                break;
         }
      }
      break;
    case 'TRANSP':
        $this->transparency = $value;
        break;
    case 'DURATION':
      // todo:
      // if this tag comes before DTSTART we will break
      if (preg_match('/^P([0-9]{1,2}[W])?([0-9]{1,3}[D])?([T]{0,1})?([0-9]{1,2}[H])?([0-9]{1,2}[M])?([0-9]{1,2}[S])?/', $value, $bits)) {
            $value = 0;
            switch (count($bits)) {
                case 7:
                    $value += $bits[6]; //seconds
                case 6:
                    $value += (60*$bits[5]); //minutes
                case 5:
                    $value += (3600*$bits[4]); //hours
                case 4:
                case 3:
                    $value += (86400*$bits[2]); //days
                case 2:
                   $value += (604800*$bits[1]);  //weeks
            }
      }

      if ($this->range) {
          $this->range->set_end($this->get_start() + $value);
      } else {      
          $this->properties['duration'] = $value;
      }
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

        $this->exdates[] = $datetime->format('U'); // start time
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

  public function clear_rrules()
  {
    $this->rrules = array();
  }

  protected function add_rrule($rrule_string) 
  {
    $rrule = new ICalRecurrenceRule($rrule_string);
    $this->rrules[] = $rrule;
  
    return;
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
            if ($this->range instanceOf DayRange)  {
                $this->addLine($output_string, "DTSTART", date('Ymd', $this->range->get_start()));
                $this->addLine($output_string, "DTEND", date('Ymd', $this->range->get_end()));
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

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalRecurrenceRule extends ICalObject {
  const MAX_OCCURRENCES=PHP_INT_MAX; // provided as a safety net
  protected $classname='RECURRENCE';
  protected $type;
  protected $limit=-1;
  protected $limitType='COUNT';
  protected $interval = 1;
  private static $dayIndex = Array('SU'=>0, 'MO'=>1, 'TU'=>2, 'WE'=>3, 'TH'=>4, 'FR'=>5, 'SA'=>6 );

  private $frequencies = Array(
      'SECONDLY',
      'MINUTELY',
      'HOURLY',
      'DAILY',
      'WEEKLY',
      'MONTHLY',
      'YEARLY'
      );
  
  function __construct($rule_string) 
  {
    $rules = explode(';', $rule_string);

    foreach ($rules as $rule) {
      $namevalue = explode('=', $rule);
      $rulename = $namevalue[0];
      $rulevalue = $namevalue[1];
      switch ($rulename) {
      case 'FREQ': // always present
        if (in_array($rulevalue, $this->frequencies)) {
            $this->type = $rulevalue;
        } else {
            throw new Exception("Invalid frequency $rulevalue");
        }
        
        break;
      case 'INTERVAL':
        $this->interval = $rulevalue;
        break;
      case 'UNTIL':
        $this->limitType = 'UNTIL';
        $datetime = new DateTime($rulevalue);
        $this->limit = $datetime->format('U');
        break;
      case 'COUNT':
        $limitType = 'COUNT';
        $this->limit = $rulevalue;
        break;
      case (substr($rulename, 0, 2) == 'BY'):
        throw new Exception("$rulename rules not handled yet ($rule_string)");
        $occurs_by_list[$rulename] = explode(',', $rulevalue);
        break;
      default:
        throw new Exception("Unknown recurrence rule property $rulename found");
        break;
      }
    }

    if (empty($this->type)) {
        throw new Exception("Invalid Frequency");
    }
  }
  
  private function nextIncrement($time, $type, $interval=1)
  {
      switch ($type)
      {
        case 'SECONDLY': 
            $time += $interval; 
            break;
        case 'MINUTELY': 
            $time += ($interval * 60); 
            break;
        case 'HOURLY'  : 
            $time += ($interval * 3600);
            break;
        case 'DAILY':
            $hour = date('H', $time);
            $minute = date('i', $time);
            $second = date('s', $time);
			for ($i=0; $i<$interval; $i++) {
			    //can't assume 24 "hours" in a day due to daylight savings. start at midnight and add 28 hours to be in the next day
				$timestamp = mktime(0,0,0, date('m', $time), date('d', $time), date('Y', $time)) + 100800; 
				$time =  mktime($hour, $minute, $second, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
			}
            break;
        case 'WEEKLY':
            $time = self::nextIncrement($time, 'DAILY', 7*$interval);
            break;
        case 'MONTHLY':
            throw new Exception("MONTHLY increment Not handled yet");
            break;
        case 'YEARLY':
            $time = mktime(date('H', $time), date('i', $time), date('s', $time), date('m', $time), date('d', $time), date('Y', $time)+$interval);
            break;
        default:
            throw new Exception("Invalid type $type");
      }
      
      return $time;
  }

  /* takes an event and range as parmeters and returns an array of occurrences DOES NOT include the original event */
  function occurrences(ICalEvent $event, TimeRange $range=null, $max=null)
  {
    $occurrences = array();
    $time = $event->get_start();
    $diff = $event->get_end()-$event->get_start();
    $limitType = $this->limitType;
    $limit = $this->limit;
    $count = 0;

//    echo date('m/d/Y H:i:s', $time) . "<br>\n";

    $time = $this->nextIncrement($time, $this->type, $this->interval);
    while ($time <= $range->get_end())
    {
  //      echo date('m/d/Y H:i:s', $time) . "<br>\n";
        $occurrence_range = new TimeRange($time, $time+$diff);
        if ($occurrence_range->overlaps($range)) {
            $occurrence = clone $event;
            $occurrence->setRange($occurrence_range);
            $occurrence->clear_rrules();
            $recurrence_id = strftime("%Y%m%dT%H%M%S",$time);
            if ($tzid = $occurrence->get_tzid()) {
                $recurrence_id = sprintf("TZID=%s:%s", $tzid, $recurrence_id);
            }
            $occurrence->set_attribute('RECURRENCE-ID', $recurrence_id);
            $occurrences[] = $occurrence;
        }
        if ( ($limitType=='COUNT') && ($count < $limit) ) { break; }
        if ( ($limitType=='UNTIL') && ($time > $limit) ) { break; }
        if ( $count > ICalRecurrenceRule::MAX_OCCURRENCES) { break; }
        if ( !is_null($max) && count($occurrences)>=$max) { break; }
        $time = $this->nextIncrement($time, $this->type, $this->interval);
        $count++;
    }
    
    return $occurrences;

  }
}

/**
  * @package ExternalData
  * @subpackage Calendar
 */
class ICalendar extends ICalObject implements CalendarInterface {
  protected $properties;
  public $timezone = NULL;
  protected $events=array();
  protected $eventStartTimes=array();

  public function add_event(ICalEvent $event) {
    $uid = $event->get_uid();
    $this->events[$uid] = $event;

    // use event start times so we can return events in starting order
    $this->eventStartTimes[$uid] = $event->get_start();
  }
  
  public function getEvents() {
    return $this->events;
  }
  
  public function getEvent($id) {
    return isset($this->events[$id]) ? $this->events[$id] : null;
  }
  
  /* returns an array of events keyed by uid containing an array of occurrences keyed by start time */
  public function getEventsInRange(TimeRange $range=null, $limit=null) {
    $events = $this->events;
    
    // sort event times
    asort($this->eventStartTimes);
    
    $occurrences = array();

    foreach ($this->eventStartTimes as $id => $startTime) {
        $event = $this->events[$id];
        $eventOccurrences = $event->getOccurrencesInRange($range, $limit);
        
        foreach ($eventOccurrences as $occurrence) {
            
            $uid = $occurrence->get_uid();
            if (!array_key_exists($uid, $occurrences)) {
                $occurrences[$uid] = array();
            }
            
            $occurrences[$uid][$occurrence->get_start()] = $occurrence;
        }
    }
    
    return $occurrences;
  }
  
  public function set_attribute($attr, $value, $params=null)
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

  public static function ical_unescape_text($text) {
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
