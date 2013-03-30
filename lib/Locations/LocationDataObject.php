<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */
 
includePackage('Maps');
class LocationDataObject extends KurogoDataObject {

  protected static $nextID = 0;

  protected $lat;
  protected $long;
  protected $address;
  
  public function __construct($arr = array()) {
    if (array_key_exists('id', $arr)) {
      $this->setID($arr['id']);
    }
    unset($arr['id']);
    
    if (array_key_exists('name', $arr) && strlen($arr['name'])) {
      $this->setTitle($arr['name']);
    }
    unset($arr['name']);
    
    if (array_key_exists('description', $arr) && strlen($arr['description'])) {
      $this->setDescription($arr['description']);
    }
    unset($arr['description']);
    
    if (array_key_exists('geoloc', $arr) && (count($arr['geoloc']) === 2)) {
      $this->setCoordinates($arr['geoloc']['lat'], $arr['geoloc']['long']);
    }
    unset($arr['geoloc']);
    
    if (array_key_exists('address', $arr) && (count($arr['address']))) {
      $this->setAddress($arr['address']);
    }
    unset($arr['address']);
    
    // put the remaining arguments array into attributes in case other data objects
    // can use them
    $this->setAttributes($arr); 
  }
  
  public function setCoordinates($lat, $long) {
    if ($this->areValidCoordinates($lat, $long)) {
      $this->lat = $lat;
      $this->long = $long;
    }
  }
  
  public function hasValidCoordinates() {
    return $this->areValidCoordinates($this->lat, $this->long);
  }
  
  protected function areValidCoordinates($lat, $long) {
    if (is_numeric($lat) && is_numeric($long)
        && ($lat >= -90) && ($lat <= 90)
        && ($long >= -180) && ($long <= 180)) {
      return true;
    } else {
      return false;
    }
  }
  
  public function getCoordinates() {
    return array($this->lat, $this->long);
  }
  
  public function setAddress($addressArr) {
    $this->address = $addressArr;
  }
  
  public function getAddress() {
    return $this->address;
  }
  
  public function getAddressHTML() {
    $html = '';
    $address = $this->address;
    if (is_array($address)) {
      $html .= (isset($address['street'])) ? $address['street'].'<br />' : '';
      $html .= (isset($address['city'])) ? $address['city'] : '';
      if (isset($address['street']) && isset($address['city'])) {
        $html .= ',';
      }
      $html .= (isset($address['state'])) ? ' '.$address['state'] : '';
      $html .= (isset($address['zip'])) ? ' '.$address['zip'] : '';
    } else {
      $html = $address;
    }
    return trim($html);
  }
  
  public function getDistance($myGeolocation) {
    $fromLat = $myGeolocation[0];
    $fromLon = $myGeolocation[1];
    
    list($toLat, $toLon) = $this->getCoordinates();
    
    $radiansPerDegree = M_PI / 180.0;
    $y1 = $fromLat * $radiansPerDegree;
    $x1 = $fromLon * $radiansPerDegree;
    $y2 = $toLat * $radiansPerDegree;
    $x2 = $toLon * $radiansPerDegree;

    $dx = $x2 - $x1;
    $cosDx = cos($dx);
    $cosY1 = cos($y1);
    $sinY1 = sin($y1);
    $cosY2 = cos($y2);
    $sinY2 = sin($y2);

    $leg1 = $cosY2*sin($dx);
    $leg2 = $cosY1*$sinY2 - $sinY1*$cosY2*$cosDx;
    $denom = $sinY1*$sinY2 + $cosY1*$cosY2*$cosDx;
    $angle = atan2(sqrt($leg1*$leg1+$leg2*$leg2), $denom);

    return $angle * EARTH_RADIUS_IN_METERS;
  }
  
  public function getJavascriptDistancePlaceholderHTML() {
    if ($this->hasValidCoordinates()) {
      $coords = $this->getCoordinates();
      return "<div class='distance-placeholder' lat='$coords[0]' lon='$coords[1]'></div>";
    } else {
      return '';
    }
  }

  public function filterItem($filters) {
    return true;
  }
  
  public function hasAmenity($amenity) {
    if ($amenities = $this->getAttribute('amenities')) {
      if (array_key_exists($amenity, $amenities)) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
  
  public function getAmenities() {
    return $this->getAttribute('amenities', array());
  }
  
  public function getHoursHTML() {
    $html = '';
    $hours = $this->getHours();
    $today = date('w', time());
    if (!isset($hours)){
      return null;
    } else {
      $hours = $hours->getAllHours();
    }
    foreach ($hours as $dayOfTheWeek => $timeRanges) {
      // If there is only one day's worth of hours and that day is today output
      // as 'Today's Hours: ' rather than the name of the day
      if (count($hours) === 1 && $dayOfTheWeek == $today) {
        $firstHours = 'Today '.date('g:ia', $timeRanges[0]->get_start());
      } else {
        $firstHours = date('l g:ia', strtotime('+'.$dayOfTheWeek-$today.' Day', $timeRanges[0]->get_start()));
      }
      $html .= '<span class="hours">'.$firstHours.'-'.date('g:ia', $timeRanges[0]->get_end()).'</span>';
    }
    return $html;
  }
  
  public function getHours() {
    if ($hours = $this->getAttribute('hours')) {
      return $hours;
    } else {
      return null;
    }
  }
  
  /*
   * Returns all the events that are currently occuring
   */
  public function getCurrentEvents($currentTime = null) {
    if ($this->hasAttribute('events')) {
      $events = $this->getAttribute('events');
      return $this->convertEventsToEventDataObjects($events->getCurrentEvents($currentTime));
    } else {
      return null;
    }
  }
  
  /*
   * Returns all the events that occur today
   */
  public function getTodaysEvents($currentTime = null) {
    if ($this->hasAttribute('events')) {
      $events = $this->getAttribute('events');
      return $this->convertEventsToEventDataObjects($events->getTodaysEvents($currentTime));
    } else {
      return null;
    }
  }
  
  /*
   * Returns the next occuring event
   */
  public function getNextEvent($todayOnly = false) {
    if ($this->hasAttribute('events')) {
      $events = $this->getAttribute('events');
      if ($event = $events->getNextEvent($todayOnly)) {
          return $this->convertEventToEventDataObject($event);
      }
    } 
    
    return null;
  }
  
  public function isOpen($timerange = null) {
    if ($hours = $this->getHours()) {
      return $hours->isOpen($timerange = null);
    } elseif ($this->hasAttribute('events')) {
      $events = $this->getCurrentEvents();
      return (count($events)) ? true : false;
    } else {
      return null;
    }
  }
  
  public function getListClass($timerange = null) {
      if ($this->isOpen() === true) {
        return 'open';
      } elseif ($this->isOpen() === false) {
        return 'closed';
      } else {
        return 'unknown';
      }
  }
  
  // Transitional code: currently the EventsDataModel returns ICalEventObjects.
  // The ICalEventObjects need to be converted to EventDataObjects
  // to work with the DataObjectDetailsController. In the future Calendar will
  // be refactored to create KurogoDataObjects
  protected function convertEventsToEventDataObjects($eventObjects) {
    if (is_array($eventObjects)) {
      $events = array();
      foreach ($eventObjects as $eventObject) {
        if ($eventObject instanceof EventDataObject) {
          $events[] = $eventObject;
        } else {
          $events[] = $this->convertEventToEventDataObject($eventObject);
        }
      }
      return $events;
    } else {
    }
  }
  
  protected function convertEventToEventDataObject(CalendarEvent $eventObject) {
    $event = new EventDataObject();
    $event->setID($eventObject->get_uid());
    $event->setTitle($eventObject->get_summary());
    $event->setRange($eventObject->get_range());
    $event->setStart($eventObject->get_start());
    $event->setEnd($eventObject->get_end());
    $event->setDescription($eventObject->get_description());
    return $event;
  } 

}