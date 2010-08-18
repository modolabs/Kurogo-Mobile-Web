<?php
require_once LIBDIR . '/testCourses.php';

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
    $subjectId = urldecode($_REQUEST['id']);
    $data = CourseData::get_subject_details($subjectId);
    if($data) {

    } else {
      $data = array('error' => 'SubjectNotFound', 'message' => 'Courses could not find this subject');
    }
    break;

case 'search':
    $query = urldecode($_REQUEST['query']);
    $school = urldecode($_REQUEST['courseGroup']);
    $course = urldecode($_REQUEST['courseName']);
    $course = urlencode(str_replace('-other', '', $course));
    $school = urlencode(str_replace('-other', '', $school));
    $query = urlencode($query);

    $data = CourseData::search_subjects($query, str_replace('-other', '', $school), str_replace(' ', '+', $course));
    break;
}

echo json_encode($data);

?>