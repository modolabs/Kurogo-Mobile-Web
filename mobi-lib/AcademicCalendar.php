<?

$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once "mit_ical_lib.php";

define("ACADEMIC_CALENDAR_ICS", 'http://web.mit.edu/registrar/calendar/AcademicCalendar.ics');
define("ACADEMIC_CALENDAR_CACHE_FILE", CACHE_DIR . "ACADEMIC_CALENDAR");
define("ACADEMIC_CALENDAR_CACHE_LIFESPAN", 86400 * 30);

class AcademicCalendar {
  private static $ical;
  private static $terms = NULL;

  public static function is_holiday($time) {
    self::init();
    $events = self::$ical->get_day_events($time);
    foreach ($events as $event) {
      if (stripos($event->get_summary(), 'holiday') !== FALSE
	  || stripos($event->get_summary(), 'vacation') !== FALSE)
	return TRUE;
    }
    return FALSE;
  }

  public static function get_holidays($year) {
    self::init();
    $holidays = self::$ical->search_by_title('holiday');
    $vacation = self::$ical->search_by_title('vacation');
    $data = Array();

    // here we take advantage of the fact that the acad calendar
    // does not have overlapping dates for vacations and holidays
    // if they do we need to account for that
    foreach ($holidays as $day) {
      $start = $day->get_start();
      if (date('Y', $start) == $year) {
	$data[$start] = $day;
      }
    }
    foreach ($vacation as $day) {
      $start = $day->get_start();
      if (date('Y', $start) == $year) {
	$data[$start] = $day;
      }
    }

    ksort($data);
    return array_values($data);
  }

  public static function get_events($month=NULL, $year=NULL) {
    self::init();

    if ($year === NULL) {
      $year = date('Y');
    }

    if ($month === NULL) {
      $month = date('n');
    }

    // adjust day starts for time zones
    // honestly i am not 100% sure these are the right params
    $month_start = day_of(mktime(0, 0, 0, $month, 1, $year));
    $month_end = increment_month($month_start);

    $monthRange = new TimeRange($month_start, $month_end);
    return self::$ical->search_by_range($monthRange);
  }

  public static function get_term($time=NULL) {
    if ($time === NULL)
      $time = time();

    if (self::$terms === NULL) {
      // if we're in the first half of the year, use last year as base year
      $year = (date('n', $time) < 7) ? date('Y', $time) - 1 : date('Y', $time);

      // start with crude lower-bound guesses for ranges
      $fall_start = mktime(0, 0, 0, 8, 15, $year);
      $iap_start = mktime(0, 0, 0, 1, 1, $year+1);
      $spring_start = mktime(0, 0, 0, 2, 1, $year+1);
      $summer_start = mktime(0, 0, 0, 6, 1, $year+1);
      $summer_end = mktime(0, 0, 0, 8, 15, $year+1);

      self::$terms = Array(
	'fa' => new TimeRange($fall_start, $iap_start),
	'ia' => new TimeRange($iap_start, $spring_start),
	'sp' => new TimeRange($spring_start, $summer_start),
	'su' => new TimeRange($summer_start, $summer_end),
	);

      $events = self::$ical->search_by_title("first day of");
      
      foreach ($events as $event) {
	$event_start = increment_day($event->get_start(), -1);
	foreach (self::$terms as $term => $range) {
	  if ($range->contains_point($event_start)) {
	    self::$terms[$term]->set_start($event_start);
	    switch ($term) {
	    case 'ia':
	      self::$terms['fa']->set_end($event_start);
	      break;
	    case 'sp':
	      self::$terms['ia']->set_end($event_start);
	      break;
	    case 'su':
	      self::$terms['sp']->set_end($event_start);
	      break;
	    } // switch
	    break;
	  } // if
	} // foreach term
      } // foreach event

    } // done creating terms

    foreach (self::$terms as $term => $range) {
      if ($range->contains_point($time)) {
	return $term;
	break;
      }
    }

  }

  public static function init() {
    if (!self::$ical) {
      if (!file_exists(ACADEMIC_CALENDAR_CACHE_FILE)
	  || filemtime(ACADEMIC_CALENDAR_CACHE_FILE) < time() - ACADEMIC_CALENDAR_CACHE_LIFESPAN) {
	$fh = fopen(ACADEMIC_CALENDAR_CACHE_FILE, 'w');
	fwrite($fh, file_get_contents(ACADEMIC_CALENDAR_ICS));
	fclose($fh);
      }
      self::$ical = new ICalendar(ACADEMIC_CALENDAR_CACHE_FILE);
    }
  }

}

?>