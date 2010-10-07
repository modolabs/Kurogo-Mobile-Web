<?php

require_once LIBDIR . "/TranslocReader.php";

$infoItems = array(
    array('id' => 'harvard', 'name' => 'About Harvard Shuttles', 'html' => aboutHarvardShuttles()),
    array('id' => 'masco', 'name' => 'About MASCO Shuttles', 'html' => aboutMasco()),
    array('id' => 'calendar', 'name' => 'Shuttles Calendar', 'html' => shuttlesCalendar()),
);

?>