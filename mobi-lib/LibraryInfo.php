<?

/* this file uses the constants
 * CACHE_DIR
 * ICS_CACHE_LIFESPAN
 */
require_once "lib_constants.inc";
require_once "mit_ical_lib.php";
require_once "rss_services.php";
require_once "DiskCache.inc";

class LibraryRSS extends RSS {
  protected $rss_url = LIBRARY_OFFICE_RSS;
  protected $custom_tags = array('url', 'room', 'phone', 'calendar_url');
}

class LibraryInfo {

  public static $libraries = NULL;
  private static $cache = NULL;

  // returns google calendar url
  public static function ical_url($library) {
    $attribs = self::get_library_info($library);
    return $attribs['gcal'];
  }

  public static function get_calendar($library) {
    $ical_file = self::ical_filename($library);
    if (!file_exists($ical_file)) {
      self::cache_ical($library);
    }

    $cal = new ICalendar($ical_file);
    return $cal;
  }

  public static function get_libraries() {
    if (self::$libraries === NULL) {
      $libraries = Array();
      $rss = new LibraryRSS;
      $feed = $rss->get_feed();
      foreach ($feed as $item) {
        $libraries[ $item['title'] ] = array(
          'url' => $item['url'],
          'tel' => $item['phone'],
          'location' => $item['room'],
          'gcal' => $item['calendar_url'],
          );
      }
      self::$libraries = $libraries;
    }
    return array_keys(self::$libraries);
  }

  public static function get_library_info($library) {
    if (self::$libraries === NULL) {
      self::get_libraries();
    }
    return self::$libraries[$library];
  }

  public static function cache_ical($library) {
    if (self::$cache === NULL) {
      self::$cache = new DiskCache(CACHE_DIR . "/LIBRARIES", ICS_CACHE_LIFESPAN, TRUE);
      self::$cache->setSuffix('.ics');
    }

    if (!self::$cache->isFresh($library)) {
      $google_cal_url = self::ical_url($library);

      $error_reporting = intval(ini_get('error_reporting'));
      error_reporting($error_reporting & ~E_WARNING);
      if ($contents = file_get_contents($google_cal_url)) {
        self::$cache->write($contents);
      }
      error_reporting($error_reporting);
    }
  }

  public static function cache_icals() {
    foreach (self::get_libraries() as $library) {
      if ($library == 'Aeronautics and Astronautics Library'
	  || $library == 'Lindgren Library') continue;
      else self::cache_ical($library);
    }
  }
}

?>
