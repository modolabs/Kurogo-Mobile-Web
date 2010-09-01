<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once LIBDIR . '/courses.php';
require_once "stellar_lib.inc";

function selfURL() {
  $query = http_build_query(array("filter" => $_REQUEST['filter'], "courseGroup" => $_REQUEST['courseGroup'], "courseName" => $_REQUEST['courseName']));
  return "searchMain.php?$query";
}

    $queryTerms = stripslashes($_REQUEST['filter']);
    $school = stripslashes(isset($_REQUEST['courseGroup']) ? $_REQUEST['courseGroup'] : '');
    $course = stripslashes(isset($_REQUEST['courseName']) ? $_REQUEST['courseName'] : '');
    //print $_REQUEST['courseGroup'];
    $course = str_replace("\\", "", $course);
    $school = str_replace("\\", "", $school);

    $data = CourseData::search_subjects($queryTerms, str_replace('-other', '', $school), str_replace(' ', '+', $course));
    $count = $data['count'];
    $classes = isset($data["classes"]) ? $data["classes"] : NULL;

    /* SchoolsAsResults will only be available for searches from the top-level view where search results are > 100*/
    $schoolsAsResults = isset($data['schools']) ? $data['schools'] : NULL;

    $school_array = array();
    $school_count_map = array();
    $school_name_count_map = array();

    if ($count <= 100) {
    foreach($classes as $class_current) {
        $short_school_name = $class_current['short_name'];

        if (!in_array($class_current['school'], $school_array)) {
            $school_array[] = $class_current['school'];
            $school_count_map[$class_current['school']] = 1;
        }
        else {
             $school_count_map[$class_current['school']] = $school_count_map[$class_current['school']] + 1;
             $school_name_count_map[$class_current['school']] = array('name'=> $class_current['school'], 
                                                                'count' => $school_count_map[$class_current['school']],
                                                                'name_short' => $short_school_name);
        }
    }
    }

    else {
        foreach($schoolsAsResults as $school) {
             $school_count_map[$school['name']] = $school['count'];
             $school_name_count_map[$school['name']] = array('name'=> $school['name'], 'count' => $school['count'], 'name_short' => $school['name_short']);
        }
    }


// if exactly one class is found redirect to that
// classes detail page
/*if(count($classes) == 1) {
  header("Location: " . detailURL('', $classes[0]['masterId'], $courseGroup, $courseGroupShort, $courseId, $courseIdShort));
  die();
}*/

//$content = new ResultsContent("items", "courses", $page, NULL, FALSE);

require "$page->branch/searchMain.html";

$page->output();

?>
