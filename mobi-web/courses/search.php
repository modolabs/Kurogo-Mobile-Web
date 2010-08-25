<?php

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

function selfURL() {
  return "search.php?".http_build_query(array(
    "filter"           => stripslashes($_REQUEST['filter']), 
    "courseGroup"      => isset($_REQUEST['courseGroup'])      ? stripslashes($_REQUEST['courseGroup']) : '', 
    "courseGroupShort" => isset($_REQUEST['courseGroupShort']) ? stripslashes($_REQUEST['courseGroupShort']) : '', 
    "courseName"       => isset($_REQUEST['courseName'])       ? stripslashes($_REQUEST['courseName']) : '', 
    "courseNameShort"  => isset($_REQUEST['courseNameShort'])  ? stripslashes($_REQUEST['courseNameShort']) : '',
  ));
}

$back             = isset($_REQUEST['back'])             ? stripslashes($_REQUEST['back']) : '';
$courseGroup      = isset($_REQUEST['courseGroup'])      ? stripslashes($_REQUEST['courseGroup']) : '';
$courseGroupShort = isset($_REQUEST['courseGroupShort']) ? stripslashes($_REQUEST['courseGroupShort']) : '';
$courseName       = isset($_REQUEST['courseName'])       ? stripslashes($_REQUEST['courseName']) : '';
$courseNameShort  = isset($_REQUEST['courseNameShort'])  ? stripslashes($_REQUEST['courseNameShort']) : '';

$title = strlen($courseNameShort) ? $courseNameShort : $courseGroupShort;

$queryTerms = stripslashes($_REQUEST['filter']);
$school = $courseGroup;
$course = $courseName;
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

$content = new ResultsContent("items", "courses", $page, array(
      'back' => $back,
      'courseGroup' => $courseGroup,
      'courseGroupShort' => $courseGroupShort,
      'courseName' => $courseName,
      'courseNameShort' => $courseNameShort,  
    ), FALSE);

require "$page->branch/search.html";

$page->output();
    
?>
