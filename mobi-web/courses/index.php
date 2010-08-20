<?php

require_once LIBDIR . '/testCourses.php';
require_once "stellar_lib.inc";


/*if (!isset($_REQUEST['refresh']) && $page->branch != "Webkit") {
	header("Location: index.php?refresh=true");
        die(0);
}*/

$schools = (CourseData::get_schoolsAndCourses());

require "$page->branch/index.html";

//$page->prevent_caching($pagetype);

$page->output();

?>
