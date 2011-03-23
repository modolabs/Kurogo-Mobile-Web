<?php

require_once(LIB_DIR . '/Calendar/DateTimeUtils.php');
require_once(LIB_DIR . '/Calendar/ICalendar.php');

class Calendar 
{
    public static function getUserCalendarListController() {
        return array(
            ''=>'-',
            'GoogleAppsCalendarListController'=>'Google Apps'
        );
    }

    public static function getResourceListController() {
        return array(
            ''=>'-',
            'GoogleAppsCalendarListController'=>'Google Apps (Business or Education)'
        );
    }
    
    public static function getTimeZones() {
        return array(
            'America/New_York'=>'US Eastern Time'
        );
    }
}