<?php

require_once "lib_constants.inc";
require_once "DiskCache.inc";
require_once 'html2text.php';

define('CATEGORY_QUERY_BASE', 'fq_dept_area_category=dept_area_category:"');
define('TERM_QUERY','&fq_coordinated_semester_yr=coordinated_semester_yr:"Sep+to+Dec+2010+(Fall+Term)"&');
define('TERM', 'Fall 2010');
define('SCHOOL_QUERY_BASE', '&fq_school_nm=school_nm:"');

function compare_courseNumber($a, $b)
{
  return strnatcmp($a['name'], $b['name']);
}

function compare_schoolName($a, $b)
{
    return strcmp($a->school_name_short, $b->school_name_short);
}


class CourseData {

  private static $courses = array();

  private static $schools = array();
  private static $schoolsToCoursesMap = array();

  private static $indexToCoursesMap = array();
  private static $coursesToSubjectsMap = array();

  private static $not_courses = array("SP");

  // general course info is cached in the $courses array (since some long living processes) use this data
  // we need to make sure the data does not get too old
  private static $course_last_cache_times = array();
  private static $course_last_cache_terms = array();

  private static $base_url = STELLAR_BASE_URL;

  private static $rss_url = STELLAR_RSS_URL;

  //private static $subscriptions = Array();
  public static $subscriptions = Array();

  private static $courseDiskCache = NULL;
  private static $feedDiskCache = NULL;

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
      return TERM;
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

  public static function get_subject_details($subjectId) {

    $urlString = STELLAR_BASE_URL .'q=id:'.$subjectId;

    error_log("COURSE DEBUG: " . $urlString);

    $filenm = STELLAR_COURSE_DIR. '/Course-' .$subjectId . '.xml';

    if (file_exists($filenm) && ((time() - filemtime($filenm)) < STELLAR_COURSE_CACHE_TIMEOUT)) {
      $urlString = $filenm; //file_get_contents($filenm);
    }
    else {
      $handle = fopen($filenm, "w");
      fwrite($handle, file_get_contents($urlString));
      $urlString = $filenm;
    }
    $xml = file_get_contents($urlString);

    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);

    $subject_array = array();
    $single_course = $xml_obj->courses->course;
    $subject_fields = array();
    $id = explode(':',$single_course['id']);
    $nm = explode(':', $single_course->course_number);
    $subject_fields['name'] = $nm[0];
    $subject_fields['masterId'] = $id[0];
    $titl = explode(':', $single_course->title);
    $len = count($titl);
    $subject_fields['title'] = '';
    for ($ind = 0; $ind < $len; $ind++) {
      if ($ind == $len-1)
        $subject_fields['title'] = $subject_fields['title'] .$titl[$ind];
      else
        $subject_fields['title'] = $subject_fields['title'] .$titl[$ind] .':';
    }

    //$subject_fields['title'] = $titl[0];
    $desc = explode(':', $single_course->description);
    $len = count($desc);
    $subject_fields['description'] = '';
    for ($ind = 0; $ind < $len; $ind++) {
      if ($ind == $len-1)
        $subject_fields['description'] = $subject_fields['description'] .$desc[$ind];
      else
        $subject_fields['description'] = $subject_fields['description'] .$desc[$ind] .':';
    }
    $subject_fields['description'] = HTML2TEXT($subject_fields['description']);
    // $subject_fields['description'] = $desc[0];
    $pre_req = explode(':', $single_course->prereq);
    $subject_fields['preReq'] = $pre_req[0];
    $credits = explode(':', $single_course->credits);
    $subject_fields['credits'] = $credits[0];
    $cross_reg = explode(':', $single_course->crossreg);
    $subject_fields['cross_reg'] = $cross_reg[0];
    $exam_group = explode(':', $single_course->exam_group);
    $subject_fields['exam_group'] = $exam_group[0];
    $dept = explode(':', $single_course->department);
    $subject_fields['department'] = $dept[0];
    $school = explode(':', $single_course->school_name);
    $subject_fields['school'] = $school[0];
    //$trm = explode(':',$single_course->term_description);
    //$subject_fields['term'] = $trm[0];
    $subject_fields['term'] = TERM;
    $ur = explode(':',$single_course->url);
    if (count($ur) > 1)
      $subject_fields['stellarUrl'] = $ur[0].':'.$ur[1];

    $classtime['title'] = 'Lecture';
    $loc = explode(':',$single_course->location);
    $classtime['location'] = $loc[0];

    $m_time = explode(':', $single_course->meeting_time);
    $len = count($m_time);
    $classtime['time'] = '';
    for ($ind = 0; $ind < $len; $ind++) {
      if ($ind == $len-1)
        $classtime['time'] = $classtime['time'] .$m_time[$ind];
      else
        $classtime['time'] = $classtime['time'] .$m_time[$ind] .':';
    }

    $classtime_array[] = $classtime;

    $subject_fields['times'] = $classtime_array;

    // Reimplementation using crazier parsing
    $subject_fields['meeting_times'] = new MeetingTimes($single_course->meeting_time,
                                                        $single_course->location);

    $ta_array = array();
    //$prof = explode(':', $single_course->faculty_description);
    //$staff['instructors'] = array($prof[0]);
    $staff['instructors'] = explode(",",$single_course->faculty_description);
    $staff['tas'] = $ta_array;
    $subject_fields['staff'] = $staff;

    $announ['unixtime'] = time();
    $announ['title'] = 'Announcement1';
    $announ['text'] = 'Details of Announcement1';
    $announ_array[] = $announ;
    $subject_fields['announcements'] = $announ_array;

    $subject_array = $subject_fields;
    $subjectDetails = $subject_array;
    //$courseToSubject[$course] = $subject_array;// store it in a global array containing courses to subjects
    return $subjectDetails;
  }


  public static function get_subjectsForCourse($course, $courseGroup) {
      $gueryAdditionForCourseGroup = 'fq_school_nm=school_nm:"' .str_replace(' ', '+', $courseGroup) .'"&';
      $queryAddition = 'fq_dept_area_category=dept_area_category:"' . str_replace(' ', '+', str_replace('&', '%26',$course)) .'"&';
      $term = TERM_QUERY;

      if ( $course == $courseGroup)
           $queryAddition = 'fq_dept_area_category=dept_area_category:[*+TO+""]';

      $urlString = STELLAR_BASE_URL .$term .$gueryAdditionForCourseGroup .$queryAddition;

      $filenm = STELLAR_COURSE_DIR .'/' .$course .'-' . $courseGroup .'.xml';
      if (file_exists($filenm) && ((time() - filemtime($filenm)) < STELLAR_COURSE_CACHE_TIMEOUT)) {
      }
      else {
          $handle = fopen($filenm, "w");
          fwrite($handle, file_get_contents($urlString));
          //$urlString = $filenm;
      }

      $xml = file_get_contents($filenm);

      if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);
    $count = $xml_obj->courses['numFound']; // Number of Courses Found

    $iterations = ($count/25);

    
    $subject_array = array();
    for ($index=0; $index < $iterations; $index=$index+1) {
        //printf(" Current = %d\n",$index*25);
        $number = $index * 25;
        $queryString = $queryAddition .'&start=' .$number;


      $urlString = STELLAR_BASE_URL .$term .$gueryAdditionForCourseGroup .$queryString;

      $filenm1 = STELLAR_COURSE_DIR .'/' .$course .'-' . $courseGroup.'-' .$index.'.xml';
      if (file_exists($filenm1) && ((time() - filemtime($filenm1)) < STELLAR_COURSE_CACHE_TIMEOUT)) {

      }
      else {
          $handle = fopen($filenm1, "w");
          fwrite($handle, file_get_contents($urlString));
      }

      $xml = file_get_contents($filenm1);

      if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }
   
     $xml_obj = simplexml_load_string($xml);
    // $nbr = 1;

     
     foreach($xml_obj->courses->course as $single_course) {
         $subject_fields = array();
         $id = explode(':',$single_course['id']);
         $nm = explode(':', $single_course->course_number);

         if (ctype_alpha(str_replace(' ', '', $nm[0])) || (substr($nm[0], 0, 1) == '0')) {
             $nm[0] = '0' .$nm[0];
         }
         
         $subject_fields['name'] = $nm[0];
         $subject_fields['masterId'] = $id[0];
         $titl = explode(':', $single_course->title);
                  $len = count($titl);
         $subject_fields['title'] = '';
          for ($ind = 0; $ind < $len; $ind++) {
             if ($ind == $len-1)
                 $subject_fields['title'] = $subject_fields['title'] .$titl[$ind];
             else
                $subject_fields['title'] = $subject_fields['title'] .$titl[$ind] .':';
         }
         //$subject_fields['title'] = $titl[0];
         $subject_fields['term'] = TERM;

        //$prof = explode(':', $single_course->faculty_description);
        $ta_array = array();
        $staff['instructors'] = $staff['instructors'] = explode(",",$single_course->faculty_description);
          $staff['tas'] = $ta_array;
          $subject_fields['staff'] = $staff;

         $subject_array[] = $subject_fields;
     }
  }

  usort($subject_array, 'compare_courseNumber');

  $subjectArrayToReturn = array();
  foreach($subject_array as $subject) {
      if (substr($subject["name"], 0, 1) == '0') {
          $subject["name"] = substr($subject["name"], 1);
      }
      $subjectArrayToReturn[] = $subject;
  }
  $courseToSubject = $subjectArrayToReturn;
  return $courseToSubject;
 }


  // returns the Schools (Course-Group) to Departmetns (Courses) map
  public static function get_schoolsAndCourses() {

     // $filenm = STELLAR_COURSE_DIR. '/SchoolsAndCourses' .'.xml';
      $filenm = STELLAR_COURSE_DIR. '/SchoolsAndCourses' .'.txt';

      if (file_exists($filenm) && ((time() - filemtime($filenm)) < STELLAR_COURSE_CACHE_TIMEOUT)) {
          //$urlString = $filenm; //file_get_contents($filenm);
      }
      else {
          self::condenseXMLFileForCoursesAndWrite(STELLAR_BASE_URL .TERM_QUERY, $filenm);
      }
      $schoolsAndCourses = json_decode(file_get_contents($filenm));
      usort($schoolsAndCourses, "compare_schoolName");
      
      return $schoolsAndCourses;
  }


  public static function condenseXMLFileForCoursesAndWrite($xmlURLPath, $fileToWrite) {

      $handle = fopen($fileToWrite, "w");

      $xml = file_get_contents($xmlURLPath);


   // $xml = file_get_contents(STELLAR_BASE_URL .TERM_QUERY);

      if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);

    foreach($xml_obj->facets->facet as $fc) {

        if ($fc['name'] == 'school_nm')
            foreach($fc->field as $field) {
                $self->schools[] = $field['name'];

                $school_string = SCHOOL_QUERY_BASE .str_replace(' ', '+', $field['name']) .'"&';
                $urlString = STELLAR_BASE_URL .TERM_QUERY . $school_string;
                $courses_map_xml = file_get_contents($urlString);

                    if($courses_map_xml == "") {
                    // if failed to grab xml feed, then run the generic error handler
                        throw new DataServerException('COULD NOT GET XML');
                    }

                     $courses_xml_obj = simplexml_load_string($courses_map_xml);

                     foreach($courses_xml_obj->facets->facet as $fcm) {
                        if ($fcm['name'] == 'dept_area_category') {

                            $map = array();
                            $course_array = array();
                             foreach($fcm->field as $fieldMap) {
                                 $crs = explode(':', $fieldMap['name']);

                                 if ($crs != '') {
                                    $crsMap['name'] = $crs[0];
                                    $crsMap['short'] = '1';
                                    $course_array[] = $crsMap;
                                 }
                             }
                       }
                      }

                      if (count($course_array) >= 1) {
                             $str = explode(':', $field['name']);
                             $map['school_name'] = $str[0];
                             $strShort = explode(':', $field['short_name']);
                             $map['school_name_short'] = $strShort[0];
                             $map['courses'] = $course_array;

                             $self->schoolsToCoursesMap[] = $map;
                      }
                }

    }

    $stringToWrite = '';
    foreach($self->schoolsToCoursesMap as $schoolsMapping) {
        if ($schoolsMapping['school_name'] != '') {
        $stringToWrite = $stringToWrite . $schoolsMapping['school_name'] .',,,' .$schoolsMapping['school_name_short'] . ':::';
            foreach($schoolsMapping['courses'] as $course) {
                $stringToWrite = $stringToWrite . $course['name'] . ',,,';
            }

            $stringToWrite = substr($stringToWrite, 0, -1) . '...';
        }
    }

    fwrite($handle, $stringToWrite);
    fclose($handle);

    self::getXMLSchoolsMapJSONEncoded($fileToWrite);

    return;
  }


  public static function getXMLSchoolsMapJSONEncoded($filenm) {

      $dummySchoolsToCoursesMap = array();
       $str = file_get_contents($filenm);

      $schoolLayerArray = explode('...', $str);

      foreach($schoolLayerArray as $schoolLayer) {
          $mappingArray = explode(':::',$schoolLayer);

          $nameLayerArray = explode(',,,', $mappingArray[0]);
          $groupLayerArray = explode(',,,', $mappingArray[1]);

          $courseArray = Array();
          foreach($groupLayerArray as $dept) {
              $crsMap['name'] = str_replace(',,', '', $dept);
              $crsMap['short'] = '1';
              $courseArray[] = $crsMap;
          }

          if (count($courseArray) >= 1) {

              if (strlen($nameLayerArray[0]) > 0) {
                             $map['school_name'] = $nameLayerArray[0];
                             $map['school_name_short'] = $nameLayerArray[1];
                             $map['courses'] = $courseArray;

                             $dummySchoolsToCoursesMap[] = $map;
                      }
          }
      }

      $handle = fopen($filenm, "w");
      fwrite($handle, json_encode($dummySchoolsToCoursesMap));
      fclose($handle);

      return;

        //return schoolsToCoursesMap;
  }

  public static function search_subjects($terms, $school, $courseTitle) {
      
      $words = explode(' ', $terms);

      $terms = '"';
      for ($ind=0; $ind< count($words); $ind++) {
          if ($ind == count($words)-1)
            $terms = $terms .$words[$ind]. '"';
          else
            $terms = $terms .$words[$ind] .'+';
      }

      $schoolWords = explode(' ', $school);

      $schoolNm = '';
      for ($ind=0; $ind< count($schoolWords); $ind++) {
          if ($ind == count($schoolWords)-1)
            $schoolNm = $schoolNm .$schoolWords[$ind];
          else
            $schoolNm = $schoolNm .$schoolWords[$ind] .'+';
      }


      $term = TERM_QUERY;
      $search_terms = $terms;
      $sorting_params = 'sort=score+desc,course_title+asc';
      $schoolName = SCHOOL_QUERY_BASE . $schoolNm . '"';

      if ($school == '') {
        $schoolName = '';
      }

      $courseName = '&' .CATEGORY_QUERY_BASE .$courseTitle .'"';

      if ($courseTitle == '') {
          $courseName = '';
      }

      else if ($courseTitle == $school) {
          $courseName = '&' .'fq_dept_area_category=dept_area_category:[*+TO+""]';
      }
      $urlString = STELLAR_BASE_URL .$courseName .$schoolName .$term . 'q="' .$terms .'"&' . $sorting_params;

      $xml = file_get_contents($urlString);

     // echo $urlString;
      //echo $xml;
      
      if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);
    $count = $xml_obj->courses['numFound']; // Number of Courses Found


    /* ONLY IF search results from the MAIN courses page are greater than 100 */
    if (($count > 100)  && ($school == '')){

        foreach($xml_obj->facets->facet as $fc) {

        if ($fc['name'] == 'school_nm')
            foreach($fc->field as $field) {
            $nm = explode(':', $field['name']);
            $nm_count = explode(':', $field['count']);
            $strShort = explode(':', $field['short_name']);
                $schools[] = array('name'=> $nm[0], 'count' => $nm_count[0], 'name_short'=> $strShort[0]);
            }
        }
        $count_array = explode(':', $count);
        $too_many_results['count'] =$count_array[0];
        $too_many_results['schools'] = $schools;
        return $too_many_results;
    }

    $iterations = ($count/25);

    $actual_count = $count;
    if ($iterations > 4) {
        $iterations = 4;
        $count = 100;
    }


   // printf("Total: %d\n",$count);
   // printf("Iterations: %d\n",$iterations);

    $subject_array = array();
    for ($index=0; $index < $iterations; $index=$index+1) {
        //printf(" Current = %d\n",$index*25);
        $number = $index * 25;
        $queryAddition = '&start=' .$number;


      $urlString = STELLAR_BASE_URL .$courseName .$schoolName .$term .'q="' .$terms .'"&'  . $sorting_params .$queryAddition;
      $xml = file_get_contents($urlString);


      if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

     $xml_obj = simplexml_load_string($xml);

     foreach($xml_obj->courses->course as $single_course) {
         $subject_fields = array();
         $id = explode(':',$single_course['id']);
         $nm = explode(':', $single_course->course_number);
         $subject_fields['name'] = $nm[0];
         $school = explode(':', $single_course->school_name);
         $subject_fields['school'] = $school[0];
         $subject_fields['masterId'] = $id[0];
         $titl = explode(':', $single_course->title);
                  $len = count($titl);
          $subject_fields['title'] = '';
          for ($ind = 0; $ind < $len; $ind++) {
             if ($ind == $len-1)
                 $subject_fields['title'] = $subject_fields['title'] .$titl[$ind];
             else
                $subject_fields['title'] = $subject_fields['title'] .$titl[$ind] .':';
         }
         //$subject_fields['title'] = $titl[0];
         $subject_fields['term'] = TERM;
         
           $ta_array =array();
          $staff['instructors'] = $staff['instructors'] = explode(",",$single_course->faculty_description);
          $staff['tas'] = $ta_array;
          $subject_fields['staff'] = $staff;
          $temp = self::get_schoolsAndCourses();
          foreach($temp as $schoolsMapping) {
              //print_r($schoolsMapping);

              if ( $schoolsMapping->school_name == $school[0]) {
                  $subject_fields['short_name'] = $schoolsMapping->school_name_short;
              }
        }

         $subject_array[] = $subject_fields;
     }
  }

  //usort($subject_array, 'compare_courseNumber');
  //$courseToSubject = array('count' => $count, 'classes' => $subject_array);
        $count_array = explode(':', $count);
        $courseToSubject ['count'] = $count_array[0];
        $actual_count_array = explode(':', $actual_count);
        $courseToSubject['actual_count'] = $actual_count_array[0];
        $courseToSubject ['classes'] = $subject_array;
  return $courseToSubject;


  }

}
?>
