<?php

define('HARVARD_EVENTS_ICS_BASE_URL', 'http://www.trumba.com/calendars/gazette.ics');

require_once "harvard_ical_lib.php";
require_once "DiskCache.inc";

TrumbaCal::init();

class TrumbaCal {

  private static $ical = NULL;
  private static $diskCache = NULL;

  public function init() {
    if (self::$ical === NULL) {
      self::$diskCache = new DiskCache(CACHE_DIR . '/gazette.ics', 86400);
      self::$diskCache->preserveFormat();
      if (!self::$diskCache->isFresh()) {
error_log("retrieving file from " . HARVARD_EVENTS_ICS_BASE_URL, 0);
        $contents = file_get_contents(HARVARD_EVENTS_ICS_BASE_URL);
        self::$diskCache->write($contents);
      }

      self::$ical = new HarvardICalendar(self::$diskCache->getFullPath());
    }
  }

  public static function getDayEvents($time=NULL) {
    $events = self::$ical->get_day_events();
    return $events;
  }
}

