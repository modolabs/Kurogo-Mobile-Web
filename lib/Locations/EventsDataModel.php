<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Calendar');
class EventsDataModel extends CalendarDataModel {
  
  public function isOpen($timerange = null) {
    $events = $this->getCurrentEvents();
    if (count($events)) {
      return true;
    } else {
      return false;
    }
  }
  
  public function getTodaysEvents($currentTime = null) {
    if (is_null($currentTime)) {
      $currentTime = time();
    }
    $start = new DateTime(date('Y-m-d H:i:s', $currentTime), Kurogo::siteTimezone());
    $start->setTime(0,0,0);
    $end = clone $start;
    $end->setTime(23,59,59);

    $this->setStartDate($start);
    $this->setEndDate($end);
    
    return $this->items();
  }

  public function getCurrentEvents($currentTime = null) {
    if (is_null($currentTime)) {
      $currentTime = time();
    }
    $start = new DateTime(date('Y-m-d H:i:s', $currentTime), Kurogo::siteTimezone());
    $end = clone $start;
 
    $this->setStartDate($start);
    $this->setEndDate($end);

    $calendar = $this->getCalendar();
    $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataModel::START_TIME_LIMIT;
    $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataModel::END_TIME_LIMIT;
    $range = new TimeRange($startTimestamp, $endTimestamp);
    
    $events = $calendar->getEventsInRange($range);
    return $events;
  }

  public function getCurrentEvent() {
    $events = $this->getCurrentEvents();
    return is_array($events) ? current($events) : null;
  }
  
  public static function timeText($event, $timeOnly=false) {
    if ($timeOnly) {
      if ($event->getEnd() - $event->getStart() == -1) {
        return DateFormatter::formatDate($event->getStart(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
      } else {
        return DateFormatter::formatDateRange($event->getRange(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
      }
    } else {
      return DateFormatter::formatDateRange($event->getRange(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
    }
  }

  /**
   * setTime 
   * used for passing time to retriever
   * 
   * @access public
   * @return void
   */
  public function setTime($time) {
      $this->setOption('time', $time);
  }
}