<?php

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

function selfURL() {
  return "course.php?".http_build_query(array(
    'back' => stripslashes($_REQUEST['back']),
    'id' => stripslashes($_REQUEST['id']),
    'idShort' => stripslashes($_REQUEST['idShort']),
    'courseGroup' => stripslashes($_REQUEST['courseGroup']),
    'courseGroupShort' => stripslashes($_REQUEST['courseGroupShort']),    
  ));
}

$back = isset($_REQUEST['back']) ? $_REQUEST['back'] : '';
$courseId = stripslashes($_REQUEST['id']);
$courseIdShort = stripslashes($_REQUEST['idShort']);
$courseGroup = stripslashes($_REQUEST['courseGroup']);
$courseGroupShort = stripslashes($_REQUEST['courseGroupShort']);

$classes = CourseData::get_subjectsForCourse(str_replace('-other', '', $courseId), $courseGroup);

require "$page->branch/course.html";
$page->output();
    
?>