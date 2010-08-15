<?php

require_once LIBDIR . '/testCourses.php';
require_once "stellar_lib.inc";

function selfURL() {
  return "course.php?id=" . $_REQUEST['id'] . '&back=' . $_REQUEST['back'] . '&courseGroup=' . $_REQUEST['courseGroup'];
}

$back = $_REQUEST['back'];
$Back = ucwords($back);

$courseId = urldecode($_REQUEST['id']);
$courseGroup = urldecode($_REQUEST['courseGroup']);

$classes = CourseData::get_subjectsForCourse(str_replace('-other', '', $courseId), $courseGroup);

require "$page->branch/course.html";
$page->output();
    
?>
