<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";

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
    '13'   => Array('subjects' => Array(), 'name' => 'Ocean Engineering'),
    '14'   => Array('subjects' => Array(), 'name' => 'Economics'),
    '15'   => Array('subjects' => Array(), 'name' => 'Management'),
    '16'   => Array('subjects' => Array(), 'name' => 'Aeronautics and Astronautics'),
    '17'   => Array('subjects' => Array(), 'name' => 'Political Science'),
    '18'   => Array('subjects' => Array(), 'name' => 'Mathematics'),
    '20'   => Array('subjects' => Array(), 'name' => 'Biological Engineering'),
    '21'   => Array('subjects' => Array(), 'name' => 'Humanities'),
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

  private static $base_url = "http://stellar.mit.edu/courseguide/course/";

  private static $rss_url = "http://stellar.mit.edu/SRSS/rss";

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
    return array(
      "year" => date('y'),
      "season" => ($month <= 7) ? 'sp' : 'fa'
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

  private static function write_course_cache($course, $term) {
    $fh = fopen(STELLAR_COURSE_DIR . 'course' . $course . '-' . $term, 'w');
    fwrite($fh, json_encode(self::$courses[$course]));
    fclose($fh);
  }

  private static function read_course_cache($course, $term) {
    $fname = STELLAR_COURSE_DIR . 'course' . $course . '-' . $term;
    if (!file_exists($fname))
      throw new Exception("file $fname does not exist");
    self::$courses[$course] = json_decode(file_get_contents($fname), TRUE);
    return self::$courses[$course];
  }

  private static function course_cache_is_fresh($course, $term) {
    $fname = STELLAR_COURSE_DIR . 'course' . $course . '-' . $term;
    if (!file_exists($fname))
      return FALSE;
    return (time() - filemtime($fname) < STELLAR_COURSE_CACHE_TIMEOUT);
  }

  private static function write_feed_cache($subject, $xml) {
    $xml = preg_replace('/>\s+</', '><', trim($xml));
    $fh = fopen(STELLAR_FEED_DIR . $subject, 'w');
    fwrite($fh, $xml);
    fclose($fh);
  }

  private static function read_feed_cache($subject) {
    $fname = STELLAR_FEED_DIR . $subject;
    return file_get_contents($fname);
  }

  private static function feed_cache_is_fresh($subject) {
    $fname = STELLAR_FEED_DIR . $subject;
    if (!file_exists($fname))
      return FALSE;
    return (time() - filemtime($fname) < STELLAR_FEED_CACHE_TIMEOUT);
  }

  private static function feed_is_changed($subject) {
    $cached_xml = self::read_feed_cache($subject);
    $xml = self::get_announcements_xml($subject);
    $xml = preg_replace('/>\s+</', '><', trim($xml));
    return ($xml != $cached_xml);
  }

  public static function check_subscriptions() {
    $updates = Array();
    self::$subscriptions = self::read_subscriptions_cache();
    foreach (self::$subscriptions as $subject => $uids) {
      if (self::feed_is_changed($subject)) {
	$updates[$subject] = $uids;
      }
    }
    return $updates;
  }

  public static function push_subscribe($subject, $uid) {
    $subjectId = self::get_subject_id($subject);
    if (!array_key_exists($subjectId, self::$subscriptions))
      self::$subscriptions[$subjectId] = Array();
    self::$subscriptions[$subjectId][] = $uid;
    self::write_subscriptions_cache();
    // make sure cache file exists so no false alarms
    self::get_announcements_xml($subjectId);
  }

  public static function push_unsubscribe($subject, $uid) {
    $subjectId = self::get_subject_id($subject);
    if (!array_key_exists($subjectId, self::$subscriptions))
      throw new Exception("nobody is subscribed to $subjectId");
    elseif (!in_array($uid, self::$subscriptions[$subjectId]))
      throw new Exception("this user is not subscribed to $subjectId");
    else {
      $id_pos = array_search($uid, self::$subscriptions[$subjectId]);
      array_splice(self::$subscriptions[$subjectId], $id_pos, 1);
      if (count(self::$subscriptions[$subjectId]) == 0)
	unset(self::$subscriptions[$subjectId]);
      self::write_subscriptions_cache();
    }
  }

  private static function write_subscriptions_cache() {
    $fh = fopen(STELLAR_SUBSCRIPTIONS_FILE, 'w');
    fwrite($fh, json_encode(self::$subscriptions));
    fclose($fh);
  }

  private static function read_subscriptions_cache() {
    if (!file_exists(STELLAR_SUBSCRIPTIONS_FILE))
      return Array();
    return json_decode(file_get_contents(STELLAR_SUBSCRIPTIONS_FILE), TRUE);
  }

  public static function init() {
    // remove this section later
    self::$courses['99'] = json_decode(file_get_contents('/home/nobody/lib/trunk/duspStellar.json'), TRUE);
    // end of removable section

    if (count(self::$subscriptions) == 0) {
      self::$subscriptions = self::read_subscriptions_cache();
    }
  }

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
	  $results[$subjectId] = $masterSubjectData;	  
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

    if (count(self::$courses[$course]['subjects']) > 0) {
      return self::$courses[$course]['subjects'];
    }

    $term = self::get_term();
    if (self::course_cache_is_fresh($course, $term)) {
      $courseData = self::read_course_cache($course, $term);
      return $courseData['subjects'];
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

    self::write_course_cache($course, $term);
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
    if (self::feed_cache_is_fresh($subjectId))
      return self::read_feed_cache($subjectId);
    $subjectData = self::get_subject_info($subjectId);
    if (array_key_exists('stellarUrl', $subjectData)) {
      $rss_id = $subjectData['stellarUrl'];
      $rss = file_get_contents(self::$rss_url . $rss_id);
      if (array_key_exists($subjectId, self::$subscriptions))
	self::write_feed_cache($subjectId, $rss);
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
    $rss_obj->loadXML($rss);
    $rss_root = $rss_obj->documentElement;

    $announcements = Array();
    foreach($rss_root->getElementsByTagName('item') as $item) {
      $title = self::getTag($item, 'title')->nodeValue;
      $colon_pos = strpos($title, ":");
      $announcements[] = array(
          "date"     => date_parse(self::getTag($item, 'pubDate')->nodeValue),
          "unixtime" => strtotime(self::getTag($item, 'pubDate')->nodeValue),
          "title"    => substr($title, $colon_pos + 1),
          "text"     => self::clean_text(self::getTag($item, 'description')->nodeValue)
      );
    }
    return $announcements;
  }

  public static function search_subjects($terms) {
    $subjects_found = Array();

    $terms_uc = strtoupper($terms);
    if (array_key_exists($terms_uc, self::$courses)) {
      foreach (self::$courses[$terms_uc]['subjects'] as $subjectId => $subjectData) {
	$subjects_found[] = self::get_subject_info($subjectId);
      }

    } elseif (preg_match('/(\w{1,3})\.(\w{1,5})/', $terms, $matches)) {
      $course = $matches[1];
      $courseData = self::get_subjects($course);
      foreach ($courseData as $subjectId => $subjectData) {
	// search for subjects that start with search term
	if (strpos($subjectId, $terms_uc) === 0 
	    && $subjectId == $subjectData['masterId']) {
	  $subjects_found[] = $subjectData;
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
	    if (strpos($title, strtolower($word)) === FALSE)
	      $found = FALSE;
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
