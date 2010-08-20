<?php

require_once LIBDIR . '/testCourses.php';
require_once "stellar_lib.inc";

function selfURL() {
  $query = http_build_query(array("filter" => $_REQUEST['filter'], "courseGroup" => $_REQUEST['courseGroup'], "courseName" => $_REQUEST['courseName']));
  return "search.php?$query";
}


    $queryTerms = urldecode($_REQUEST['filter']);
    $school = urldecode($_REQUEST['courseGroup']);
    $course = urldecode($_REQUEST['courseName']);
    //print $_REQUEST['courseGroup'];
    $course = str_replace("\\", "", $course);
    $school = str_replace("\\", "", $school);

    //print "Here!";
    //print ($school);
    
    $data = CourseData::search_subjects($queryTerms, str_replace('-other', '', $school), str_replace(' ', '+', $course));

    $classes = $data["classes"];
// if exactly one class is found redirect to that
// classes detail page
/*if(count($classes) == 1) {
  header("Location: " . detailURL($classes[0], selfURL()));
  die();
}*/

$content = new ResultsContent("items", "courses", $page, NULL, FALSE);

require "$page->branch/search.html";

$page->output();
    
?>
