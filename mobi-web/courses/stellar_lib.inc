<?php

function coursesURL($which, $whichShort) {
  return "courses.php?".http_build_query(array(
    "which" => $which,
    "whichShort" => $whichShort,
  ));
}

function courseURL($back, $id, $idShort, $courseGroup, $courseGroupShort) {
  return "course.php?".http_build_query(array(
    "back" => $back,
    "id" => $id,
    "idShort" => $idShort,
    "courseGroup" => $courseGroup,
    "courseGroupShort" => $courseGroupShort,
  ));
}

function searchURL($filter, $courseGroup, $courseGroupShort, $courseName, $courseNameShort) {
  return "search.php?".http_build_query(array(
    "filter" => $filter,
    "courseName" => $courseName,
    "courseNameShort" => $courseNameShort,
    "courseGroup" => $courseGroup,
    "courseGroupShort" => $courseGroupShort,
  ));
}

function detailURL($back, $id, $courseGroup, $courseGroupShort, $courseSubGroup, $courseSubGroupShort, $filter='') {
  return "detail.php?".http_build_query(array(
    "back" => $back,
    "id" => $id,
    "courseGroup" => $courseGroup,
    "courseGroupShort" => $courseGroupShort,
    "courseSubGroup" => $courseSubGroup,
    "courseSubGroupShort" => $courseSubGroupShort,
    "filter" => $filter, 
  ));
}

function longID($class) {
  if (strlen($class['masterId']) > strlen($class['name']))
    return $class['masterId'];
  return str_replace(' / ', '/', $class['name']);
}

function longerID($class) {
  if (strlen($class['masterId']) > strlen($class['name']))
    return $class['masterId'];
  return $class['name'];
}

function className($class) {
  return $class['title'];
}

function name($course) {
  return htmlentities($course["name"]);
}

function idName($id, $course) {
  $prefix = $course['is_course'] ? "Course " : "";
  return $prefix . $id;
}

function has_stellar_site($class) {
  return array_key_exists('stellarUrl', $class);
}

/* My Stellar functions */

define("EXPIRE_TIME", 160 * 24 * 60 * 60);
// is it a coincidence that this is the same duration as the
// customize EXPIRE_TIME or do we purposely want them to be the same?

function getMyStellar() {
  // read the cookie, and create three groups                                                                                                                                                           
  // first group all the classes in the cookie                                                                                                                                                          
  // second group is the classes for the current semester                                                                                                                                               
  // third group is the classes from previou semesters 
  if(!isset($_COOKIE["mystellar"])) {
    $allTags = array();
  } else {
    $termAndYear = explode(" ",CourseData::get_term());
    $term = $termAndYear[0];
    $allTags = explode(",", $_COOKIE["mystellar"]);
    natsort($allTags);
  }

  $currentTags = array();
  $currentIds = array();
  $oldIds = array();
  foreach($allTags as $classTag) {
    $parts = explode(" ", $classTag);
    if($parts[1] == $term) {
      $currentTags[] = $classTag;
      $currentIds[] = $parts[0];
    } else {
      $oldIds[] = $parts[0];
    }
  }

  return (object)array(
    "allTags" => $allTags,
    "currentTags" => $currentTags,
    "currentIds" => $currentIds,
    "oldIds" => $oldIds,
  );
}

function removeOldMyStellar() {
  setMyStellar(getMyStellar()->currentTags);
}

function setMyStellar($classes) {
  setcookie("mystellar", implode(",", $classes), time() + EXPIRE_TIME);
}

?>
