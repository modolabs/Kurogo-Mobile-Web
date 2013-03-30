<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

IncludePackage('DateTime'); 
class DailyHoursDataObject extends KurogoDataObject {
  
  // Days of the week, 0 is Sunday
  protected $dailyHours = array(
    0 => array(),
    1 => array(),
    2 => array(),
    3 => array(),
    4 => array(),
    5 => array(),
    6 => array(),
  );
  
  public function __construct($arr = array()) {
    $this->setDailyHours($arr);
  }
  
  public function getAllHours() {
    $returnArr = array();
    foreach ($this->dailyHours as $day => $hours) {
      if (!empty($hours)) {
        $returnArr[$day] = $hours;
      }
    }
    return $returnArr;
  }
  
  public function getHoursForDay($dayOfTheWeek = null) {
    if ($dayOfTheWeek === null) {
      $dayOfTheWeek = date('w', time());
    }
    return $this->dailyHours[$dayOfTheWeek];
  }
  
  public function setHoursForDay($startEndArr, $dayOfTheWeek = null) {
    if ($dayOfTheWeek === null) {
      $dayOfTheWeek = date('w', time());
    }
    foreach ($startEndArr as $startEndPair) {
      $this->dailyHours[$dayOfTheWeek][] = new TimeRange($startEndPair['start'], $startEndPair['end']);
    }
  }
  
  public function setDailyHours($dailyHours) {
    if (isset($dailyHours['day'])) {
      if (is_array($dailyHours)) {
        foreach ($dailyHours['day'] as $value) {
          $start = strtotime($value['start']);
          $end = strtotime($value['end']);
          $this->dailyHours[$value['dow']][] = new TimeRange($start, $end);
        }
      } 
    }
  }
  
  public function getEvents($timeRange = null) {
    if ($timeRange === null) {
      $timeRange = new TimeRange(time());
    }
    $dayOfTheWeek = date('w', $timeRange->get_start());
    $events = array();
    foreach ($this->dailyHours[$dayOfTheWeek] as $event) {
      if ($event->overlaps($timeRange)) {
        $events[] = $event;
      }
    }
    return $events;
  }
  
  public function isOpen($timeRange = null) {
    $events = $this->getEvents($timeRange);
    if ($events === null) {
      return null;
    }
    return !empty($events);
  }
  
  public function isClosed($timeRange = null) {
    $events = $this->getEvents($timeRange);
    if ($events === null) {
      return null;
    }
    return empty($events);
  }
  
  public function getScheduleLinks($timeRange, $data=null) {
    $events = $this->getEvents($timeRange);
    $scheduleLinks = array();
    foreach ($events as $event) {
      $subtitle = DateFormatter::formatDateRange($event, DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);

      $options = array(
        'id'   => $this->getID(),
        'time' => $event->get_start(),
      );
    
      if (isset($data['section'])) {
          $options['section'] = $data['section'];
      }

      if (isset($data['groupID'])) {
          $options['groupID'] = $data['groupID'];
      }
    
      $class = '';
      if($data['showDetail']) {
          $url = $this->buildBreadcrumbURL('schedule', $options, true);
      }else {
          $url = false;
      }

      if ($event->contains(new TimeRange(time()))) {
          $class = 'open';
      } else {
          $class = 'closed';
      }
    
      $scheduleLinks[] = array(
          'title'     => $class,
          'subtitle'  => $subtitle,
          'url'       => $url,
          'listclass' => $class
      );
    }
                
    return $scheduleLinks;
  }
  
  public function filterItem($filters) {
    return true;
  }
  
}