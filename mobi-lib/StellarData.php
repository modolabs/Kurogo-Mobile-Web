<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once("AcademicCalendar.php");
require_once('DiskCache.php');

class StellarData {
  // is there really not a data source for this?
  private static $courses = array(
    '1'    => Array('subjects' => Array(), 'name' => 'Civil and Environmental Engineering'),
    '2'    => Array('subjects' => Array(), 'name' => 'Mechanical Engineering'),
    '3'    => Array('subjects' => Array(), 'name' => 'Materials Science and Engineering'),
    '4'    => Array('subjects' => Array(), 'name' => 'Architecture'),
    '5'    => Array('subjects' => Array(), 'name' => 'Chemistry'),
    '6'    => Array('subjects' => Array(), 'name' => 'Electrical Engineering and Computer Science'),
    '7'    => Array('subjects' => Array(), 'name' => 'Biology'),
    '8'    => Array('subjects' => Array(), 'name' => 'Physics'),
    '9'    => Array('subjects' => Array(), 'name' => 'Brain and Cognitive Sciences'),
    '10'   => Array('subjects' => Array(), 'name' => 'Chemical Engineering'),
    '11'   => Array('subjects' => Array(), 'name' => 'Urban Studies and Planning'),
    '12'   => Array('subjects' => Array(), 'name' => 'Earth, Atmospheric, and Planetary Sciences'),
    //'13'   => Array('subjects' => Array(), 'name' => 'Ocean Engineering'),
    '14'   => Array('subjects' => Array(), 'name' => 'Economics'),
    '15'   => Array('subjects' => Array(), 'name' => 'Management'),
    '16'   => Array('subjects' => Array(), 'name' => 'Aeronautics and Astronautics'),
    '17'   => Array('subjects' => Array(), 'name' => 'Political Science'),
    '18'   => Array('subjects' => Array(), 'name' => 'Mathematics'),
    '20'   => Array('subjects' => Array(), 'name' => 'Biological Engineering'),
    //'21'   => Array('subjects' => Array(), 'name' => 'Humanities'),
    '21A'  => Array('subjects' => Array(), 'name' => 'Anthropology'),
    '21F'  => Array('subjects' => Array(), 'name' => 'Foreign Languages and Literatures'),
    '21H'  => Array('subjects' => Array(), 'name' => 'History'),
    '21L'  => Array('subjects' => Array(), 'name' => 'Literature'),
    '21M'  => Array('subjects' => Array(), 'name' => 'Music and Theater Arts'),
    '21W'  => Array('subjects' => Array(), 'name' => 'Writing and Humanistic Studies'),
    '22'   => Array('subjects' => Array(), 'name' => 'Nuclear Science and Engineering'),
    '24'   => Array('subjects' => Array(), 'name' => 'Linguistics and Philosophy'),
    'CMS'  => Array('subjects' => Array(), 'name' => 'Comparative Media Studies'),
    'CSB'  => Array('subjects' => Array(), 'name' => 'Computational and Systems Biology'),
    'ESD'  => Array('subjects' => Array(), 'name' => 'Engineering Systems Division'),
    'HST'  => Array('subjects' => Array(), 'name' => 'Health Sciences and Technology'),
    'MAS'  => Array('subjects' => Array(), 'name' => 'Media Arts and Sciences'),
    'SP'   => Array('subjects' => Array(), 'name' => 'Special Programs'),
    'STS'  => Array('subjects' => Array(), 'name' => 'Science, Technology, and Society'),
  );

  private static $not_courses = array("SP");
  
  // general course info is cached in the $courses array (since some long living processes) use this data
  // we need to make sure the data does not get too old
  private static $course_last_cache_times = array();
  private static $course_last_cache_terms = array();

  private static $base_url = STELLAR_BASE_URL;

  private static $rss_url = STELLAR_RSS_URL;

  //private static $subscriptions = Array();
  public static $subscriptions = Array();

  private static function clean_text($text) {
    $text = str_replace(chr(194), '', $text);
    $text = str_replace(chr(160), ' ', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
  }

  private static function getTag($xml_obj, $tag) {
    $list = $xml_obj->getElementsByTagName($tag);
    if($list->length == 0) {
      throw new Exception("no elements of type $tag found");
    }
    /*
    if($list->length > 1) {
      throw new Exception("elements of type $tag not unique, {$list->length} found");
    }
    */
    return $list->item(0);
  }

  private static function getTagVal($xml_obj, $tag) {
    return self::getTag($xml_obj, $tag)->nodeValue;
  }

  private static function getTagVals($xml_obj, $tag) {
    $nodes = $xml_obj->getElementsByTagName($tag);
    $vals = array();
    foreach($nodes as $node) {
      $vals[] = $node->nodeValue;
    }
    return $vals;
  }

  private static function getStaff($staff_xml, $type) {
    $child = $staff_xml->getElementsByTagName($type);
    if($child->length == 1) {
      return self::getTagVals($child->item(0), 'fullName');
    } else {
      return array();
    }
  }

  public static function get_term_data() {
    $month = (int) date('m');
    AcademicCalendar::init();
    return array(
      "year" => date('y'),
      "season" => AcademicCalendar::get_term(),
      //"season" => ($month <= 7) ? 'sp' : 'fa'
    );
  }

  public static function get_term() {
    $data = self::get_term_data();
    return $data["season"] . $data["year"];
  }

  public static function get_term_text() {
    $data = self::get_term_data();
    $seasons = array(
      'sp' => 'Spring',
      'fa' => 'Fall',
      'ia' => 'IAP',
      'su' => 'Summer',
      );    
    return $seasons[ $data["season"] ] . " 20" . $data["year"];
  }

  public static function get_courses() {
    $out = Array();
    foreach (self::$courses as $id => $attribs) {
      $out[$id] = Array(
        'name' => $attribs['name'],
	'is_course' => !in_array($id, self::$not_courses)
	);
    }
    return $out;
  }

  public static function get_others() {
    $out = Array();
    foreach (self::get_courses() as $id => $course) {
      if (!preg_match('/^\d/', $id))
	$out[$id] = $course;
    }
    return $out;
  }

  private static $feedCache = NULL;
  private static $courseCache;
 
  private static function announcements_is_changed($subject) {
    $current_announcements = self::get_announcements($subject);

    if($current_announcements === False) {
      // this announcements data is not valid 
      // we mark this as a null timestamp
      $current_timestamp = NULL;
    } else if(count($current_announcements)==0) {
      // no annoucements we mark this as zero timestamp
      $current_timestamp = 0;
    } else {
      $current_timestamp = $current_announcements[0]['unixtime'];
    }

    $date_fname = STELLAR_FEED_DIR . "{$subject}_last_timestamp";
    if(file_exists($date_fname)) {
      $old_timestamp = intval(file_get_contents($date_fname));
    } else {
      $old_timestamp = NULL;
    }

    if($current_timestamp !== NULL) {
      // record the current timestamps for future checks
      file_put_contents($date_fname, "{$current_timestamp}");
    }

    if(($old_timestamp === NULL) || ($current_timestamp === NULL)) {
      return False;
    }
    
    return ($current_timestamp > $old_timestamp);
  }

  public static function check_subscriptions($term) {
    $updates = Array();
    foreach (self::subjects_with_subscriptions($term) as $subject) {
      if (self::announcements_is_changed($subject)) {
	$updates[] = $subject;
      }
    }
    return $updates;
  }
  
  public static function subjects_with_subscriptions($term) {
    // this function is meant to be robust to database connections getting lost
    // since it is run inside a daemon script
    $subject_ids = array();
    $results = db::$connection->query("SELECT subject_id FROM MyStellarSubscription "
      . "WHERE term='$term' GROUP BY subject_id");
    while($results && $row = $results->fetch_assoc()) {
      $subject_ids[] = $row['subject_id'];
    }
    if($results) {
      $results->close();
    }
    return $subject_ids;
  }

  public static function subscriptions_for_subject($subject, $term) {
    $subscriptions = array();
    $results = db::$connection->query(
      "SELECT device_id, device_type FROM MyStellarSubscription "
      . "WHERE term='$term' AND subject_id='$subject'");
    while($row = $results->fetch_assoc()) {
      $subscriptions[] = $row;
    }
    $results->close();
    return $subscriptions;
  }

  public static function push_subscribe($subject, $term, $device_id, $device_type) {
    $subjectId = self::get_subject_id($subject);
    $term = db::escape($term);
    $device_id = db::escape($device_id);
    $device_type = db::escape($device_type);

    // use a transaction to insure a person only get subscribed once per class
    db::$connection->query("START TRANSACTION");
    // this statement will cause row locking
    $initial = db::$connection->query(
      "SELECT * FROM MyStellarSubscription WHERE subject_id='$subjectId' AND term='$term'"
      .  " AND device_id=$device_id AND device_type='{$device_type}' FOR UPDATE");

    if(!$initial->fetch_assoc()) {
      // no subscription exists yet so create it
      db::$connection->query(
        "INSERT INTO MyStellarSubscription (subject_id, term, device_id, device_type)"
        . " VALUES ('{$subjectId}', '$term', {$device_id}, '{$device_type}')");
    }
    $initial->close();
    db::$connection->query("COMMIT");
  }

  public static function push_unsubscribe($subject, $term, $device_id, $device_type) {
    $subjectId = self::get_subject_id($subject);
    $term = db::escape($term);
    $device_id = db::escape($device_id);
    $device_type = db::escape($device_type);

    db::$connection->query("DELETE FROM MyStellarSubscription WHERE "
      . " subject_id='$subjectId' AND term='$term' " 
      . " AND device_id=$device_id AND device_type='{$device_type}'");
  }

  /*
  public static function init() {
    if (count(self::$subscriptions) == 0) {
      self::$subscriptions = self::read_subscriptions_cache();
    }
  }
  */

  public static function get_course($id) {
    return Array(
      'name' => self::$courses[$id]['name'],
      'is_course' => !in_array($id, self::$not_courses),
      );
  }

  public static function get_subjects_with_xref($course) {
    $subjects = self::get_subjects($course);
    $results = Array();
    foreach ($subjects as $subjectId => $subjectData) {
      if (!array_key_exists('title', $subjectData)) { // subjectId != masterId
	if (!array_key_exists(self::get_subject_id($subjectId), $subjects)) {
	  $masterSubjectData = self::get_subject_info($subjectId);
	  if($masterSubjectData !== False) {
	    $results[$subjectId] = $masterSubjectData;
	  }
	}
      } else {
	$results[$subjectId] = $subjectData;
      }
    }
    return $results;
  }

  public static function get_subjects($course) {
    if(!in_array($course, array_keys(self::$courses))) {
      throw new Exception("$course not a valid course ID-number");
    }

    if (self::$courseCache === NULL) {
      self::$courseCache = new DiskCache(
        STELLAR_COURSE_DIR, STELLAR_COURSE_CACHE_TIMEOUT, true);
    }

    // checking to see if the data cached in local memory is valid (it is invalid if it was never populated, or old, or the term has changed)
    $term = self::get_term();
    if ( (array_key_exists($course, self::$course_last_cache_times)) &&
         (time()-self::$course_last_cache_times[$course] < STELLAR_COURSE_CACHE_TIMEOUT) &&
         (self::$course_last_cache_terms[$course] == $term)) {
            return self::$courses[$course]['subjects'];
    }

    $cacheName = 'course' . $course . '-' . $term;
    if (self::$courseCache->isFresh($cacheName)) {
      self::$courses[$course] = self::$courseCache->read($cacheName);
      self::$course_last_cache_times[$course] = self::$courseCache->getAge($cacheName);
      self::$course_last_cache_terms[$course] = $term;
      return self::$courses[$course]['subjects'];
    }
    
    $xml_obj = new DOMDocument();

    $error_reporting = intval(ini_get('error_reporting'));
    error_reporting($error_reporting & ~E_WARNING);
      $xml = file_get_contents(self::$base_url . "$course/$term/index.xml");
    error_reporting($error_reporting);

    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException(self::$base_url . "$course/$term/index.xml is experiencing problems");
    }

    $xml_obj->loadXML($xml);

    $root = $xml_obj->documentElement;
    $body = self::getTag($xml_obj, 'courseTerm');
    $list = $body->getElementsByTagName('term');
    $subjects = $list->item(0);
    $subjects_xml = self::getTag($body, 'subjects');
    $subjects = $subjects_xml->getElementsByTagName('subject');

    foreach($subjects as $subject_xml) {
      $subjectData = Array();
      $subjectId = self::getTagVal($subject_xml, 'subjectId');
      $masterId = self::getTagVal($subject_xml, 'masterSubjectId');
      $subjectData['masterId'] = $masterId;
      if ($subjectId != $masterId) {
	self::$courses[$course]['subjects'][$subjectId] = $subjectData;
	continue;
      }
      $subjectData['name'] = self::getTagVal($subject_xml, 'name');
      $subjectData['title'] = self::getTagVal($subject_xml, 'title');
      $subjectData['description'] = trim(self::getTagVal($subject_xml, 'description'));

      $sitesNode = $subject_xml->getElementsByTagName('stellarSites');
      if ($sitesNode->length > 0) {

	// we will replicate all subjectData for each stellarSite
	// since it is rare that a subject has > 1 stellarSites
	$siteData = $subjectData;

	$stellarSites = $sitesNode->item(0)->getElementsByTagName('stellarSite');
	$numSites = $stellarSites->length;

	foreach ($stellarSites as $stellarSite) {
	  $stellarUrl = self::getTagVal($stellarSite, 'stellarUrl');
	  $siteData['stellarUrl'] = $stellarUrl;

	  $stellarId = end(explode('/', $stellarUrl));

	  // i guess how our masterId's should work is this: if the
	  // subject has zero or one stellarSite, use the masterId
	  // provided.  if the subject has multiple stellarSites, use
	  // the end of the stellarUrl since that is *hopefully*
	  // unique *at least in these cases*.  basically the goal is
	  // to index the union of every masterId and unique
	  // stellarSite so that our server can generate meaningful
	  // content for every link we show.
	  if ($numSites > 1)
	    $siteData['masterId'] = $stellarId;

	  $siteData['title'] = self::getTagVal($stellarSite, 'stellarTitle');
      
	  // extract times and locations
	  $times_xml = self::getTag($stellarSite, 'times');
	  $items = $times_xml->getElementsByTagName('item');
	  $siteData['times'] = Array();
	  foreach($items as $item) {
	    $siteData['times'][] = array(
              "title" => self::getTagVal($item, 'title'),
	      "time" => self::getTagVal($item, 'time'),
	      "location" => self::getTagVal($item, 'location'),
	      );
	  }

	  // extract the subject staff
	  $staff_xml = self::getTag($stellarSite, 'staff');
	  $siteData['staff'] = array(
            'instructors' => self::getStaff($staff_xml, 'instructors'),
	    'tas' => self::getStaff($staff_xml, 'tas'),
	    );

	  // sometimes the site uses a differnent course #
	  // from the masterSubjectId...... why oh why stellar???
	  $siteCourse = reset(explode('.', $stellarId));
	  $masterCourse = reset(explode('.', $masterId));
	  if ($siteCourse == $masterCourse && $numSites > 1)
	    self::$courses[$course]['subjects'][$stellarId] = $siteData;
	  else
	    self::$courses[$course]['subjects'][$masterId] = $siteData;
	}
      } else {
	self::$courses[$course]['subjects'][$subjectId] = $subjectData;
      }
    }

    self::$courseCache->write(self::$courses[$course], $cacheName);

    // record time/term when course cached into memory
    self::$course_last_cache_times[$course] = time();
    self::$course_last_cache_terms[$course] = $term;    

    return self::$courses[$course]['subjects'];
  }

  public static function get_subject_id($id) {
    $id = trim($id);
    $parts = explode('.', $id);
    $course = $parts[0];
    $subjects = self::get_subjects($course);
    return $subjects[$id]['masterId'];
  }

  public static function get_subject_info($id) {
    $subjectId = self::get_subject_id($id);
    if (!$subjectId)
      return FALSE;
    $parts = explode('.', $subjectId);
    $course = $parts[0];
    $subjects = self::get_subjects($course);

    // ugly test for all possible cases with/without "J" suffix
    // sometimes stellar drops the "J" in classes like
    // 16.920J in the masterSubjectId
    // as fas as i can tell it's pretty random
    // if someone knows the pattern please tell me
    if (array_key_exists($subjectId, $subjects))
      return $subjects[$subjectId];
    elseif (array_key_exists($subjectId . 'J', $subjects))
      return $subjects[$subjectId . 'J'];
    elseif (array_key_exists(rtrim($subjectId, 'J'), $subjects))
      return $subjects[rtrim($subjectId, 'J')];
    else
      return FALSE;
  }

  private static function get_announcements_xml($subjectId) {
    if (self::$feedCache === NULL) {
      self::$feedCache = new DiskCache(
        STELLAR_FEED_DIR, STELLAR_FEED_CACHE_TIMEOUT, true);
      self::$feedCache->preserveFormat();
    }

    if (self::$feedCache->isFresh($subjectId))
      return self::$feedCache->read($subjectId);
    
    $subjectData = self::get_subject_info($subjectId);
    if (array_key_exists('stellarUrl', $subjectData)) {
      $rss_id = $subjectData['stellarUrl'];
      $rss = file_get_contents(self::$rss_url . $rss_id);
      self::$feedCache->write($rss, $subjectId);
      return $rss;
    } else { // no feed because no stellarUrl
      return FALSE;
    }
  }

  public static function get_announcements($subjectId) {
    $rss = self::get_announcements_xml($subjectId);

    if (!$rss)
      return FALSE;

    $rss_obj = new DOMDocument();
    if(!$rss_obj->loadXML($rss)) {
      // make sure the XML parses correctly
      return FALSE;
    }
    $rss_root = $rss_obj->documentElement;

    $announcements = Array();
    foreach($rss_root->getElementsByTagName('item') as $item) {
      $title = self::getTag($item, 'title')->nodeValue;
      $colon_pos = strpos($title, ":");
      if (substr($title, 0, $colon_pos) == 'announcement') {
	$announcements[] = array(
          "date"     => date_parse(self::getTag($item, 'pubDate')->nodeValue),
          "unixtime" => strtotime(self::getTag($item, 'pubDate')->nodeValue),
          "title"    => trim(substr($title, $colon_pos + 1)),
          "text"     => self::clean_text(self::getTag($item, 'description')->nodeValue)
	  );
      }
    }
    return $announcements;
  }

  public static function search_subjects($terms) {
    $terms = trim($terms);
    $subjects_found = Array();

    $terms_uc = strtoupper($terms);
    if (array_key_exists($terms_uc, self::$courses)) {
      self::get_subjects($terms_uc);
      foreach (self::$courses[$terms_uc]['subjects'] as $subjectId => $subjectData) {
	$subjects_found[] = self::get_subject_info($subjectId);
      }

    } elseif (preg_match('/(\w{1,3})\.(\w{1,5})/', $terms_uc, $matches)) {
      $course = $matches[1];
      if (array_key_exists($course, self::$courses)) {
        $courseData = self::get_subjects($course);
        foreach ($courseData as $subjectId => $subjectData) {
	  // search for subjects that start with search term
	  if (strpos($subjectId, $terms_uc) === 0) {
	    $subjects_found[] = $subjectData;
	  }
        }
      }
    } else { // match all terms
      $words = split(' ', $terms);
      foreach (array_keys(self::$courses) as $course) { // need to populate
	$courseData = self::get_subjects($course);
	foreach ($courseData as $subjectId => $subjectData) {
	  $found = TRUE;
	  $title = strtolower($subjectData['title']);
	  foreach ($words as $word) {
            if($word) { //filter out $word=''
	      if (strpos($title, strtolower($word)) === FALSE)
	        $found = FALSE;
            }
	  }
	  if ($found)
	    $subjects_found[] = $subjectData;
	}
      }
    }

    return $subjects_found;      
  }

}  
?>
