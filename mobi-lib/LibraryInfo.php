<?

/* this file uses the constants
 * CACHE_DIR
 * ICS_CACHE_LIFESPAN
 */


require_once "mobi_lib_constants.php";
require_once LIB_ROOT . "DrupalDB.php";

class LibraryInfo {

  public static $libraries = NULL;

  // returns google calendar url
  public static function ical_url($library) {
    $attribs = self::get_library_info($library);
    return $attribs['gcal'];
  }

  // returns cached location
  public static function ical_filename($library) {
    return str_replace(' ', '_', CACHE_DIR . "LIBRARIES_$library.ics");
  }

  public static function get_libraries() {
    if (self::$libraries === NULL) {
      $libraries = Array();
      $drupalDB = DrupalDB::$connection;
      $stmt = $drupalDB->stmt_init();
      $stmt->prepare("SELECT title FROM node WHERE type = 'library_office' ORDER BY title");
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($library);
      while ($stmt->fetch()) {
	// hide closed libraries; eventually need a way to delete
	if ($library != 'Aeronautics and Astronautics Library'
	    && $library != 'Lindgren Library') {
	  $libraries[] = $library;
	}
      }
      $stmt->close();
      self::$libraries = $libraries;
    }
    return self::$libraries;
  }

  public static function get_library_info($library) {
    $drupalDB = DrupalDB::$connection;

    $nid = NULL;

    $stmt = $drupalDB->stmt_init();
    $stmt->prepare("SELECT nid FROM node WHERE type = 'library_office' and title LIKE ?");
    $stmt->bind_param('s', $library);
    $stmt->execute();
    $stmt->bind_result($nid);
    $stmt->fetch();
    $stmt->free_result();

    if ($nid) {
      $stmt->prepare("SELECT field_office_url_value, field_room_value, field_phone_value, field_calendar_url_value FROM content_type_library_office WHERE nid = ?");
      $result = $stmt->result_metadata();
      $stmt->bind_param('i', $nid);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($url, $location, $tel, $gcal);
      $stmt->fetch();
      $stmt->close();

      return Array(
        'url' => $url,
	'location' => $location,
	'tel' => $tel,
	'gcal' => $gcal
	);
    }
  }

  public static function cache_ical($library) {
    $ical_file_location = self::ical_filename($library);
    $time = time();
    if (!file_exists($ical_file_location) 
	|| ($time - filemtime($ical_file_location)) > ICS_CACHE_LIFESPAN 
	|| !filesize($ical_file_location)) {
      
      $google_cal_url = self::ical_url($library);
      $fhandle = fopen($ical_file_location, 'w');

      $error_reporting = intval(ini_get('error_reporting'));
      error_reporting($error_reporting & ~E_WARNING);
      if ($contents = file_get_contents($google_cal_url)) {
	fwrite($fhandle, $contents);
	fclose($fhandle);
      }
      error_reporting($error_reporting);
    }
  }

  public static function cache_icals() {
    foreach (self::get_libraries() as $library) {
      if ($library == 'Aeronautics and Astronautics Library'
	  || $library != 'Lindgren Library') continue;
      else cache_ical($library);
    }
  }
}

?>
