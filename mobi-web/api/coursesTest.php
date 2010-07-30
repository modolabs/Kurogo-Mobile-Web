<?php

require '/Users/muhammadamjad/Documents/work/Harvard/Harvard-Mobile/mobi-lib/testCourses.php';


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
    $data = CourseData::get_subjectsForCourse($courseId);

    if(isset($_REQUEST['checksum'])) {
      $checksum = md5(json_encode($data));
      if(isset($_REQUEST['full'])) {
        $data = array('checksum' => $checksum, 'classes' => $data);
      } else {
        $data = array('checksum' => $checksum);
      }
    }
    break;
 }

 echo json_encode($data);
?>