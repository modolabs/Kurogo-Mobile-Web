<?php

function coursesURL($which) {
  return "courses.php?which=$which";
}

function courseURL($id, $which) {
  return "course.php?id=$id&back=$which";
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

function detailURL($class, $self) {
  return "detail.php?id={$class['masterId']}&back=" . urlencode($self);
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
  if(!isset($_COOKIE["mystellar"])) {
    return array();
  } else {
  	$arr = explode(",", $_COOKIE["mystellar"]);
	natsort($arr);
    return $arr;
  }
}

function setMyStellar($classes) {
  setcookie("mystellar", implode(",", $classes), time() + EXPIRE_TIME);
}

?>
