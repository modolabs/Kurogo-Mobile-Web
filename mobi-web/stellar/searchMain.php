<?php

require_once LIBDIR . '/testCourses.php';
require_once "stellar_lib.inc";

function selfURL() {
  $query = http_build_query(array("filter" => $_REQUEST['filter'], "courseGroup" => $_REQUEST['courseGroup'], "courseName" => $_REQUEST['courseName']));
  return "searchMain.php?$query";
}


    $queryTerms = urldecode($_REQUEST['filter']);
    $school = urldecode($_REQUEST['courseGroup']);
    $course = urldecode($_REQUEST['courseName']);
    //print $_REQUEST['courseGroup'];
    $course = str_replace("\\", "", $course);
    $school = str_replace("\\", "", $school);

    $data = CourseData::search_subjects($queryTerms, str_replace('-other', '', $school), str_replace(' ', '+', $course));

    $classes = $data["classes"];

    $school_array = array();
    $school_count_map = array();
    $school_name_count_map = array();
    foreach($classes as $class_current) {

        if (!in_array($class_current['school'], $school_array)) {
            $school_array[] = $class_current['school'];
            $school_count_map[$class_current['school']] = 1;
        }
        else {
             $school_count_map[$class_current['school']] = $school_count_map[$class_current['school']] + 1;
             $school_name_count_map[$class_current['school']] = array('name'=> $class_current['school'], 'count' => $school_count_map[$class_current['school']]);
        }
    }



// if exactly one class is found redirect to that
// classes detail page
/*if(count($classes) == 1) {
  header("Location: " . detailURL($classes[0], selfURL()));
  die();
}*/

//$content = new ResultsContent("items", "stellar", $page, NULL, FALSE);

require "$page->branch/searchMain.html";

$page->output();

?>
