<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once LIBDIR . "db.php";

class StellarData {
  private static $courses = array(
    '1'    => 'Civil and Environmental Engineering',
    '2'    => 'Mechanical Engineering',
    '3'    => 'Materials Science and Engineering',
    '4'    => 'Architecture',
    '5'    => 'Chemistry',
    '6'    => 'Electrical Engineering and Computer Science',
    '7'    => 'Biology',
    '8'    => 'Physics',
    '9'    => 'Brain and Cognitive Sciences',
    '10'   => 'Chemical Engineering',
    '11'   => 'Urban Studies and Planning',
    '12'   => 'Earth, Atmospheric, and Planetary Sciences',
    '13'   => 'Ocean Engineering',
    '14'   => 'Economics',
    '15'   => 'Management',
    '16'   => 'Aeronautics and Astronautics',
    '17'   => 'Political Science',
    '18'   => 'Mathematics',
    '20'   => 'Biological Engineering',
    '21'   => 'Humanities',
    '21A'  => 'Anthropology',
    '21F'  => 'Foreign Languages and Literatures',
    '21H'  => 'History',
    '21L'  => 'Literature',
    '21M'  => 'Music and Theater Arts',
    '21W'  => 'Writing and Humanistic Studies',
    '22'   => 'Nuclear Science and Engineering',
    '24'   => 'Linguistics and Philosophy',
    'CMS'  => 'Comparative Media Studies',
    'CSB'  => 'Computational and Systems Biology',
    'ESD'  => 'Engineering Systems Division',
    'HST'  => 'Health Sciences and Technology',
    'MAS'  => 'Media Arts and Sciences',
    'SP'   => 'Special Programs',
    'STS'  => 'Science, Technology, and Society',
  );

  private static $not_courses = array("SP");

  private static $base_url = "http://stellar.mit.edu/courseguide/course/";

  private static $rss_url = "http://stellar.mit.edu/SRSS/rss/course/";

  private static function clean_text($text) {
    $text = str_replace(chr(194), '', $text);
    $text = str_replace(chr(160), ' ', $text);
    return preg_replace('/\s+/', ' ', $text);
  }

  private static function html_decode_array($fields, $array) {
    foreach($fields as $field) {
      $array[$field] = html_entity_decode($array[$field]);
    }
    return $array;
  }
   
  public static function get_courses() {
    $out = array();
    foreach(self::$courses as $id => $name) {
      $out[$id] = array(
        "name"      => $name,
        "is_course" => !in_array($id, self::$not_courses)
      );
    }
    return $out;
  }

  public static function get_course($id) {
    return array(
      "name"      => self::$courses[$id],
      "is_course" => !in_array($id, self::$not_courses)
    );
  }

  public static function get_others() {
    $all_courses = self::get_courses();
    $out = array();
    foreach($all_courses as $id => $course) {
      if(!preg_match("/^\d/", $id)) {
        $out[$id] = $course;
      }
    }
    return $out;
  }

  private static function getTag($xml_obj, $tag) {
    $list = $xml_obj->getElementsByTagName($tag);
    if($list->length == 0) {
      throw new Exception("no elements of type $tag found");
    }
    if($list->length > 1) {
      throw new Exception("elements of type $tag not unique, {$list->length} found");
    }
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
    $seasons = array("sp" => "Spring", "fa" => "Fall");    
    return $seasons[ $data["season"] ] . " 20" . $data["year"];
  }

  public static function get_classes($course) {
    if(!in_array($course, array_keys(self::$courses))) {
      throw new Exception("$course not a valid course ID-number");
    }

    $term = self::get_term();
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
    $classes_xml = self::getTag($body, 'subjects');
    $classes = $classes_xml->getElementsByTagName('subject');

    $stellar_classes = array();

    foreach($classes as $class_xml) {
      // the code currently assumes a class has one stellarSite
      // not sure how to handle the more general case
      $stellarSites = $class_xml->getElementsByTagName('stellarSites');
      if($stellarSites->length == 0) {
        // no stellar site for class so just continue onto the next class
        continue;
      } 
       
      $stellarSite = $stellarSites->item(0)->getElementsByTagName('stellarSite')->item(0);
      $stellarURL = self::getTag($stellarSite, 'stellarUrl')->nodeValue;
      preg_match('/\/(\w*\.\w*)$/', $stellarURL, $match);
      

      // extract times and locations
      $times_xml = self::getTag($stellarSite, 'times');
      $items = $times_xml->getElementsByTagName('item');
      $times = array();
      foreach($items as $item) {
        $times[] = array(
	  "title" => self::getTagVal($item, 'title'),
	  "time" => self::getTagVal($item, 'time'),
	  "location" => self::getTagVal($item, 'location'),
	);
      }

      // extract the class staff
      $staff_xml = self::getTag($stellarSite, 'staff');
      $staff = array(
        'instructors' => self::getStaff($staff_xml, 'instructors'),
        'tas' => self::getStaff($staff_xml, 'tas'),
      );

      $class_struct = array(
        "masterId" => self::getTagVal($class_xml, 'masterSubjectId'),
        "rssId" => $match[1],
        "id" => self::getTagVal($class_xml, 'subjectId'),
        "name" => self::getTagVal($class_xml, 'name'),
        "title" => $class_xml->getElementsByTagName('title')->item(0)->nodeValue,
        "description" => self::getTagVal($class_xml, 'description'),
        "times" => $times,
        "staff" => $staff,
      );
      $stellar_classes[] = self::html_decode_array(array("title"), $class_struct);
    }
    return $stellar_classes;
  }

  private static function getStaff($staff_xml, $type) {
    $child = $staff_xml->getElementsByTagName($type);
    if($child->length == 1) {
      return self::getTagVals($child->item(0), 'fullName');
    } else {
      return array();
    }
  }

  public static function get_class_id($id) {
    $db = db::$connection;

    $parts = explode('.', trim($id));
    $course_id = $parts[0];
    $class_id = $parts[1];

    $sql = "SELECT main_course_id, main_class_id FROM ClassID WHERE (this_course_id=? AND this_class_id=?)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $course_id, $class_id);
    $stmt->bind_result($result_course_id, $result_class_id);
    $stmt->execute();
    if($stmt->fetch()) {
      $result = "{$result_course_id}.{$result_class_id}";
    }
    $stmt->free_result();

    if(!$result) {
      $sql2 = "SELECT name FROM Class WHERE course_id=? AND class_id=?";
      $stmt2 = $db->prepare($sql2);
      $stmt2->bind_param('ss', $course_id, $class_id);
      $stmt2->bind_result($name);
      $stmt2->execute();
      if($stmt2->fetch()) {
        $result = $id;
      } else {
        // class id not found so no such class exist
        $result = NULL;
      }
      $stmt->free_result(); 
    }

    return $result;
  }

  public static function get_class_info($id) {
    $parts = explode('.', $id);
    $course_id = $parts[0];

    $classes = self::get_classes($course_id);

    foreach($classes as $aClass) {
      if($aClass['masterId'] == $id) {
        $class = $aClass;
        break;
      }
    }
   
    //get the annoucements
    $rss_id = $class['rssId'];
    $rss_obj = new DOMDocument();
    $term = self::get_term();
    $rss = file_get_contents(self::$rss_url . "$course_id/$term/$rss_id/");  
    $rss_obj->loadXML($rss);    
    $rss_root = $rss_obj->documentElement;

    $class["announcements"] = array();
    foreach($rss_root->getElementsByTagName('item') as $item) {
      $title = self::getTag($item, 'title')->nodeValue;
      $colon_pos = strpos($title, ":");
      $class["announcements"][] = array(
   	  "date"     => date_parse(self::getTag($item, 'pubDate')->nodeValue),
          "unixtime" => strtotime(self::getTag($item, 'pubDate')->nodeValue),
          "title"    => substr($title, $colon_pos + 1),
          "text"     => self::clean_text(self::getTag($item, 'description')->nodeValue)
      );
    }
    return $class;
  }

  public static function search_classes($terms) {
    $db = db::$connection;
 
    //since courses numbers are uppercase
    //convert to uppercase
    $terms = strtoupper($terms);

    $words = split(' ', $terms);
    $ids = array();
    $new_words = array();
    
    foreach($words as $word) {
      preg_match('/^(\w*)(.*)/', $word, $match);
      if(in_array($match[1], array_keys(self::$courses))) {
        if(preg_match('/^\..*/', $match[2])) {
          $ids[] = array($match[1], substr($match[2], 1));
        } else {
          $ids[] = array($match[0]);
        }
      } elseif($word) {
        $new_words[] = $word;
      }
    }
    $new_words = implode(' ', $new_words);

    $sql = "SELECT DISTINCT Class.* FROM Class, ClassID WHERE (ClassID.main_course_id=Class.course_id AND ClassID.main_class_id=Class.class_id)";
    
    // do an OR search with ClassID's
    $or_sqls = array();
    foreach($ids as $id) {
      if(count($id) == 1) {
        $or_sqls[] = "(ClassID.this_course_id = '{$id[0]}' OR Class.name like '% {$id[0]}.%' OR Class.name like '{$id[0]}.%')";
      }	else {
        $or_sqls[] = "((ClassID.this_course_id = '{$id[0]}' AND ClassID.this_class_id like '{$id[1]}%') OR Class.name like '% {$id[0]}.{$id[1]} %' OR Class.name like '{$id[0]}.{$id[1]}%')";
      }
    }

    if(count($or_sqls) > 0) {
      $sql .= "AND (" . implode(" OR ", $or_sqls) . ")";
    }

    if($new_words) {
      $sql .= "AND (Class.title like '%" . $db->escape_string($new_words) . "%')";
    }

    $stmt1 = $db->prepare($sql);
    $stmt1->bind_result($course_id, $class_id, $title, $name);
    $stmt1->execute();
    $classes = array();
    while($stmt1->fetch()) {
      $classes[] = array(
        "masterId"    => $course_id . "." . $class_id,
        "title" => $title,
        "name"   => $name,
      );
    }
    $stmt1->free_result();

    $stmt2 = $db->prepare(
      "SELECT this_course_id, this_class_id FROM ClassID WHERE main_course_id=? AND main_class_id=?"
    );

    foreach($classes as $index => $class) {
      //find all the alternate IDs for $class
      $stmt2->bind_param('ss', $class['ids'][0], $class['ids'][1]);
      $stmt2->bind_result($course_id, $class_id);
      $stmt2->execute();

      //store the alternate ids     
      $classes[$index]['ids'] = array();
      while($stmt2->fetch()) {
        //add each alternate id
        $classes[$index]['ids'][] = $course_id . "." . $class_id;
      }
      $stmt2->free_result();
    }
    return $classes;        
  }


  public static function populate_db() {
    $db = db::$connection;
    $stmt1 = $db->prepare( "INSERT INTO Class (course_id, class_id, title, name) values (?, ?, ?, ?)" );

    $stmt2 = $db->prepare( "SELECT COUNT(*) FROM Class WHERE course_id=? AND class_id=?" );

    $stmt3 = $db->prepare( "INSERT INTO ClassID (main_course_id, main_class_id, this_course_id, this_class_id) values (?, ?, ?, ?)" );

    foreach(self::$courses as $course_id => $course_title) {
      foreach(self::get_classes($course_id) as $class) {
        $id_parts = explode('.', $class['masterId']);

        $stmt2->bind_param('ss', $id_parts[0], $id_parts[1]);
        $stmt2->bind_result($count);
        $stmt2->execute();
        $stmt2->fetch();
        $stmt2->free_result();

        // only add the the table if no pre-existing entry exists
        if($count == 0) {
          $stmt1->bind_param('ssss', $id_parts[0], $id_parts[1], $class['title'], $class['name']); 
          $stmt1->execute();
        }

        $this_id_parts = explode('.', $class['id']);
        $stmt3->bind_param('ssss', $id_parts[0], $id_parts[1], $this_id_parts[0], $this_id_parts[1]);
        $stmt3->execute();
      }
    }
  }
}  
?>
