<?php

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

function selfURL() {
  return "search.php?".http_build_query(array(
    "filter"           => stripslashes($_REQUEST['filter']), 
    "courseGroup"      => stripslashes($_REQUEST['courseGroup']), 
    "courseGroupShort" => stripslashes($_REQUEST['courseGroupShort']), 
    "courseName"       => stripslashes($_REQUEST['courseName']), 
    "courseNameShort"  => stripslashes($_REQUEST['courseNameShort']),
  ));
}


$queryTerms = stripslashes($_REQUEST['filter']);
$school = stripslashes($_REQUEST['courseGroup']);
$course = stripslashes(isset($_REQUEST['courseName']) ? $_REQUEST['courseName'] : '');
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
  header("Location: " . detailURL('', $classes[0]['masterId'], $courseGroup, $courseGroupShort, $courseId, $courseIdShort)));
  die();
}*/

$courseGroup = isset($_REQUEST['courseGroup']) ? stripslashes($_REQUEST['courseGroup']) : '';
$courseGroupShort = isset($_REQUEST['courseGroupShort']) ? stripslashes($_REQUEST['courseGroupShort']) : $courseGroup;

$content = new ResultsContent("items", "courses", $page, array(), FALSE);

require "$page->branch/search.html";

$page->output();
    
?>
