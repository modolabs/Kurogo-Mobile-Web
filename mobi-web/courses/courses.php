<?php

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

$selected_school_name = stripslashes($_REQUEST['which']);
$selected_school_short_name = stripslashes(isset($_REQUEST['whichShort']) ? 
  $_REQUEST['whichShort'] : $_REQUEST['which']);

$schools = CourseData::get_schoolsAndCourses();

foreach($schools as $school) {
    if ($school->school_name == $selected_school_name)
        $courses = $school->courses;
}

//$title = $selected_school_short_name;

require "$page->branch/courses.html";

$page->output();
    
?>

