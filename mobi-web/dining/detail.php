<?php

require_once LIBDIR . "/harvard_dining.php";
require_once LIBDIR . "/diningHrs.php";

$dining_hall = $_REQUEST['location'];

$dining_halls_hours = DINING_HOURS::getDiningHours();

foreach ($dining_halls_hours as $dining_hall_hours) {
    if($dining_hall_hours->name == $dining_hall) {
        break;
    }
}

$hours = array(
    "breakfast" => $dining_hall_hours->breakfast_hours,
    "lunch" => $dining_hall_hours->lunch_hours,
    "dinner" => $dining_hall_hours->dinner_hours,
    "brain break" => $dining_hall_hours->bb_hours,
    "brunch" => $dining_hall_hours->brunch_hours,
);

foreach($hours as $meal => $hour) {
    if($hour == "NA") {
        $hours[$meal] = "Closed";
    }
}

if($hours["brain break"] != "Closed") {
    $bb_hours_parts = explode(" ", $hours["brain break"]);
    $bb_start_time = $bb_hours_parts[1];
    $hours["brain break"] = "Sunday-Thursday starting at {$bb_start_time}";
}

if($hours["brunch"] != "Closed") {
    $hours["brunch"] = "Sunday {$hours['brunch']}";
}


$restrictions = array(
    "lunch" => $dining_hall_hours->lunch_restrictions[0]->message,
    "dinner" => $dining_hall_hours->dinner_restrictions[0]->message,
    "brunch" => $dining_hall_hours->brunch_restrictions[0]->message,
);

foreach($restrictions as $meal => $restriction) {
    if($restriction == "NA") {
        $restrictions[$meal] = "None";
    }
}

// super special cases
if($dining_hall == "Hillel") {
    $hours['lunch'] = 'Saturday only';
    $hours['dinner'] .= ' (Sunday-Thursday)';
}

if($dining_hall == "Fly-By") {
    $hours['lunch'] .= " (Monday-Friday)";
}

require "$page->branch/detail.html";

$page->output();


?>