<?php

require_once LIBDIR . '/testCourses.php';
require_once "stellar_lib.inc";

$selected_school_name = $_REQUEST['which'];

$schools = CourseData::get_schoolsAndCourses();

foreach($schools as $school) {
    if ($school->school_name == $selected_school_name)
        $courses = $school->courses;
}

$title = $selected_school_name;

require "$page->branch/courses.html";

$page->output();
    
?>
