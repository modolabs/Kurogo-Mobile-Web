<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/feeds/courses.php');

switch ($_REQUEST['command']) {
  case 'courses':
    $data = CourseData::get_schoolsAndCourses();
    break;
  
  case 'subjectList':
    $course = stripslashes($_REQUEST['id']);
    $school = str_replace('-other', '', stripslashes($_REQUEST['coursegroup']));
    $data = CourseData::get_subjectsForCourse($course, $school);
  
    if(isset($_REQUEST['checksum'])) {
      $checksum = md5(json_encode($data));
      if(isset($_REQUEST['full'])) {
        $data = array('checksum' => $checksum, 'classes' => $data);
      }
      else {
        $data = array('checksum' => $checksum);
      }
    }
    break;
    
  case 'term':
    $data = array('term' => CourseData::get_term());
    break;
  
  case 'subjectInfo':
    $subjectId = stripslashes($_REQUEST['id']);
    $data = CourseData::get_subject_details($subjectId);
    if(!$data) {
      $data = array('error' => 'SubjectNotFound', 'message' => 'Courses could not find this subject');
    }
    // If parsed_meeting_times is not NULL, then the iPhone can be smart about
    // handling multiple locations and times.  Otherwise, we fall back on the
    // old behavior of just assuming it's one location and one time being sent.
    $data['parsed_meeting_times'] = $data['meeting_times']->toArray();
    break;
  
  case 'search':
    $query = stripslashes($_REQUEST['query']);
    $school = str_replace('-other', '', stripslashes($_REQUEST['courseGroup']));
    $course = str_replace('-other', '', stripslashes($_REQUEST['courseName']));
  
    $data = CourseData::search_subjects($query, $school, $course);
    break;
}

echo json_encode($data);
