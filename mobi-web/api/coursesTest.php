<?php

require_once LIBDIR . '/testCourses.php';


/*$data = CourseData::get_schoolsAndCourses();

//echo(json_encode($data));
//printf("\nCount = %d\n", count($data));
//$courseSubj = array();

foreach($data as $schools) {
    $name = $schools['school_name'];
    printf("School Name = %s\n",$name);
    foreach($schools['courses'] as $dept) {
       // printf("     %s\n", $dept);
        $subj = CourseData::get_subjectsForCourse($dept);

       // $courseSubj[$subj] = $subj;
        printf(count($subj));
    }
}*/

//$data = CourseData::get_subjectsForCourse('Physics');

//echo json_encode($data);
//printf(count($data));



 switch ($_REQUEST['command']) {
  case 'courses':
    $data = CourseData::get_schoolsAndCourses();
    break;

case 'subjectList':
    $courseId = urldecode($_REQUEST['id']);
    $courseGroup = urldecode($_REQUEST['coursegroup']);
    $data = CourseData::get_subjectsForCourse(str_replace('-other', '', $courseId), $courseGroup);

    if(isset($_REQUEST['checksum'])) {
      $checksum = md5(json_encode($data));
      if(isset($_REQUEST['full'])) {
        $data = array('checksum' => $checksum, 'classes' => $data);
      } else {
        $data = array('checksum' => $checksum);
      }
    }
    break;
 
 case 'term':
        $data = array('term' => CourseData::get_term());
     break;

 case 'subjectInfo':
    $subjectId = urldecode($_REQUEST['id']);
    $data = CourseData::get_subject_details($subjectId);
    if($data) {
      //$data['announcements'] = StellarData::get_announcements($subjectId);

      // some classes dont have stellar announcements
      //if($data['announcements'] === False) {
	//unset($data['announcements']);
     // }

     // $data['term'] = StellarData::get_term();
    } else {
      $data = array('error' => 'SubjectNotFound', 'me ssage' => 'Stellar could not find this subject');
    }
    break;

    case 'search':
        //$query1 = $_REQEUST['query'];
        //echo $query1;
    $query = urldecode($_REQUEST['query']);
    $school = urldecode($_REQUEST['courseGroup']);
    $course = urldecode($_REQUEST['courseName']);
       //echo $course;
    $data = CourseData::search_subjects($query, str_replace('-other', '', $school), str_replace(' ', '+', $course));
   /* $term = CourseData::get_term();
    foreach($data as $index => $value) {
      $data[$index]['term'] = $term;
    }*/
    break;

 }

 echo json_encode($data);
?>