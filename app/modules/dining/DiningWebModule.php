<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Locations');

class DiningWebModule extends WebModule {
  protected $id = 'dining';
  
  protected $DEFAULT_LOCATION_MODEL_CLASS = 'LocationsDataModel';
  
  protected $SHOW_HOURS_STATUS = true;
  protected $SHOW_EVENT_DETAILS = true;
  
  protected $timezone;
  protected $feeds;
  
  protected function loadFeed($feedID) {
    $feedData = $this->feeds[$feedID];
    $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : $this->DEFAULT_LOCATION_MODEL_CLASS;
    return LocationsDataModel::factory($modelClass, $feedData);
  }
  
  protected function linkForSchedule(KurogoObject $event, $data=null) {
    $subtitle = DateFormatter::formatDateRange($event->getRange(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
  
    $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
    $beginningOfDay = mktime(0, 0, 0, date('n', $current), date('j', $current));
    $endOfDay = mktime(23, 59, 59, date('n', $current), date('j', $current));
  
    $eventStart = $event->getStart();
    $eventEnd = $event->getEnd();

    if ($eventStart < $beginningOfDay) {
      // if starts more than one day before, put the date
      if ($eventStart < strtotime('-1 day', $beginningOfDay)) {
        $startDate = DateFormatter::formatDate($eventStart, DateFormatter::SHORT_STYLE, DateFormatter::NO_STYLE);
      } else {
        $startDate = $this->getLocalizedString('THE_PREVIOUS_DAY'); //'the previous day'
      }
      $subtitle = $this->getLocalizedString('IF_STARTS_EARLIER_DATE', $startDate) . $subtitle;
    }
  
    if ($eventEnd > $endOfDay) {
      // if ends more than one day after, put the date
      if ($eventEnd > strtotime('+1 day', $endOfDay)) {
        $endDate = DateFormatter::formatDate($eventEnd, DateFormatter::SHORT_STYLE, DateFormatter::NO_STYLE);
      } else {
        $endDate = $this->getLocalizedString('THE_FOLLOWING_DAY'); //'the following day'
      }
      $subtitle .= $this->getLocalizedString('IF_ENDS_LATER_DATE', $endDate);
    }
  
    $options = array(
        'id'   => $event->getID(),
        'time' => $event->getStart()
    );
  
    if (isset($data['section'])) {
        $options['section'] = $data['section'];
    }

    if (isset($data['groupID'])) {
        $options['groupID'] = $data['groupID'];
    }
  
    $class = '';
    if($this->SHOW_EVENT_DETAILS) {
        $url = $this->buildBreadcrumbURL('schedule', $options, true);
    }else {
        $url = false;
    }
    if ($event->getRange()->contains(new TimeRange(time()))) {
        $class = 'open';
    } else {
        $class = 'closed';
    }
              
    return array(
        'title'     => $event->getTitle(),
        'subtitle'  => $subtitle,
        'url'       => $url,
        'listclass' => ($this->SHOW_HOURS_STATUS) ? $class : null,
    );
  }
  
  protected function valueForType($type, $value) {
    $valueForType = $value;

    switch ($type) {
        case 'datetime':
            $valueForType = DateFormatter::formatDateRange($value, DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE);
            if ($value instanceOf TimeRange) {
                $timeString = DateFormatter::formatDateRange($value, DateFormatter::NO_STYLE, DateFormatter::MEDIUM_STYLE);
                $valueForType .= "<br />\n" . $timeString;
            }
            break;

        case 'url':
            $valueForType = str_replace("http://http://", "http://", $value);
            if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
                $valueForType = 'http://'.$valueForType;
            }
            break;
    
        case 'phone':
            $valueForType = PhoneFormatter::formatPhone($value);
            break;
  
        case 'email':
            $valueForType = str_replace('@', '@&shy;', $value);
            break;
    
        case 'category':
            $valueForType = $this->formatTitle($value);
            break;
    }

    return $valueForType;
  }
  
  protected function urlForType($type, $value) {
    $urlForType = null;

    switch ($type) {
        case 'url':
            $urlForType = str_replace("http://http://", "http://", $value);
            if (strlen($urlForType) && !preg_match('/^http\:\/\//', $urlForType)) {
                $urlForType = 'http://'.$urlForType;
            }
            break;
    
        case 'phone':
            $urlForType = PhoneFormatter::getPhoneURL($value);
            break;
    
        case 'email':
            $urlForType = "mailto:$value";
            break;
    
        case 'category':
            $urlForType = $this->categoryURL($value, false);
            break;
    }

    return $urlForType;
  }
  
  protected function initialize() {
    $this->timezone = Kurogo::siteTimezone();
    $this->feeds = $this->getModuleSections('feeds');
    $this->SHOW_HOURS_STATUS = $this->getOptionalModuleVar('SHOW_HOURS_STATUS', true);
    $this->SHOW_EVENT_DETAILS = $this->getOptionalModuleVar('SHOW_EVENT_DETAILS', true);
  }
  
  protected function initializeIndex() {
    $breadcrumbs = $this->page != 'pane';
    
    // get location data models
    $locationDataModels = array();
    $groupedLocations = array();
    foreach ($this->feeds as $feedID => $feed) {
      $locationDataModels[$feedID] = $this->loadFeed($feedID);
      $groupedLocations[$feedID]['title'] = isset($feed['title']) ? $feed['title'] : null;
    }
    
    foreach ($locationDataModels as $feedID => $model) {
      if ($this->getOptionalModuleVar('SHOW_OPEN_AT_TOP', 0)) {
          $model->setSortByOpen();
      }
      $locations = $model->getLocations();
      $locationLinks = array();
      foreach ($locations as $location) {
        if ($location->hasAttribute('events')) {
          $subtitle = '';
          $currentEvents = $location->getCurrentEvents(time());
          $nextEvent = $location->getNextEvent(true);
        
          if (count($currentEvents)>0) {
              $events = array();
              $lastTime = null;
              foreach ($currentEvents as $event) {
                  if ($event->getEnd() > $lastTime) {
                      $lastTime = $event->getEnd();
                  }
                  $eventSummary = $event->getTitle();
                  if (strlen($eventSummary)) {
                    $events[] = $eventSummary . ': ' . EventsDataModel::timeText($event, true);
                  } else {
                    $events[] = EventsDataModel::timeText($event, true);
                  }
              }
              $subtitle .= implode("<br />", $events);
          } else {
              if ($nextEvent) {
                  $eventSummary = $nextEvent->getTitle();
                  if (strlen($eventSummary)) {
                    $subtitle .= $this->getLocalizedString('NEXT_EVENT') . $eventSummary . ': ' . EventsDataModel::timeText($nextEvent);
                  } else {
                    $subtitle .= $this->getLocalizedString('NEXT_EVENT') . EventsDataModel::timeText($nextEvent);
                  }
              }
          }
        
        } else {
          // Dining module requires events attribute so skip this location
          // if it does not contain any events. Events don't necessarily need
          // to be currently occurring, but each location needs an events data model.
          continue;
        }
        $summary = $location->getAttribute('summary');
        if (strlen($summary)) {
          $subtitle = $summary.'<br />'.$subtitle;
        }
        $locationLinks[] = array(
          'title' => $location->getTitle(),
          'subtitle' => $subtitle,
          'url' => $this->buildBreadcrumbURL('detail', array('groupID' => $feedID, 'id' => $location->getID()), $breadcrumbs),
          'listclass' => ($this->SHOW_HOURS_STATUS) ? $location->getListClass() : null,
        );
      }
      $groupedLocations[$feedID]['items'] = $locationLinks;
    }
    $this->assign('groupedLocations', $groupedLocations);
  }
  
  protected function initializeDetail() {
    $feedID = $this->getArg('groupID');
    $locationID = $this->getArg('id');
    
    // specified date for events
    $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
    $next    = strtotime("+1 day", $current);
    $prev    = strtotime("-1 day", $current);
    
    $model = $this->loadFeed($feedID);
    $location = $model->getLocation($locationID);

    if ($location->hasAttribute('maploc') 
      && $link = Kurogo::moduleLinkForValue('map', $location->getAttribute('maploc'), $this)) {
      $link['title'] = $this->getLocalizedString('VIEW_ON_MAP');
      $this->assign('location', array($link));
    }
    
    if ($location->hasAttribute('events')) {
      $events = $location->getTodaysEvents($current);
      $eventLinks = array();
      foreach ($events as $event) {
        $eventLink = $this->linkForSchedule($event, array('section' => $locationID, 'groupID' => $feedID));
        $eventLinks[] = $eventLink;
      }
    }
    
    $nextURL = $this->buildBreadcrumbURL('detail', array('id' => $locationID, 'groupID' => $feedID, 'time' => $next), false);
    $prevURL = $this->buildBreadcrumbURL('detail', array('id' => $locationID, 'groupID' => $feedID, 'time' => $prev), false);
    
    $dayRange = new DayRange(time());
   
    $this->assign('title', $location->getTitle());
    $this->assign('description', $location->getDescription());
    $this->assign('current', $current);
    $this->assign('events', $eventLinks);
    $this->assign('next',    $next);
    $this->assign('prev',    $prev);
    $this->assign('nextURL', $nextURL);
    $this->assign('prevURL', $prevURL);
    $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
    $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DAY_FORMAT'));
    $this->assign('isToday', $dayRange->contains(new TimeRange($current)));
  }
  
  protected function initializeSchedule() {
    $locationID = $this->getArg('section');
    $eventID = $this->getArg('id');
    $feedID = $this->getArg('groupID');
    
    $model = $this->loadFeed($feedID);
    $location = $model->getLocation($locationID);
    
    $locationEvents = $location->getAttribute('events');
  
    $time = $this->getArg('time', time(), FILTER_VALIDATE_INT);
  
    if ($event = $locationEvents->getItem($eventID, $time)) {
        $this->assign('event', $event);
    } else {
        throw new KurogoUserException($this->getLocalizedString('EVENT_NOT_FOUND'));
    }
  
    $eventFields = $this->getModuleSections('schedule-detail');
    $fields = array();
    foreach ($eventFields as $key => $info) {
        $field = array();

        $value = $event->get_attribute($key);
        if (empty($value)) { continue; }

        if (isset($info['label'])) {
            $field['label'] = $info['label'];
        }

        if (isset($info['class'])) {
            $field['class'] = $info['class'];
        }
      
        if (Kurogo::arrayVal($info, 'nl2br')) {
            $value = nl2br($value);
        }
      
        if (isset($info['type'])) {
            $field['title'] = $this->valueForType($info['type'], $value);
            $field['url']   = $this->urlForType($info['type'], $value);
        } elseif (isset($info['module'])) {
            $field = array_merge($field, Kurogo::moduleLinkForValue($info['module'], $value, $this, $event));
        } else {
            $field['title'] = $value;
        }

        $fields[] = $field;
    }  
    $this->assign('fields', $fields);
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index' :
      case 'pane' :
        $this->initializeIndex();
        break;
      case 'detail' :
        $this->initializeDetail();
        break;
      case 'schedule' :
        $this->initializeSchedule();
        break;
    }
  }
  
  public function nativeWebTemplateAssets() {
    return array(
      '/common/images/location-status-closed.png',
      '/common/images/location-status-open.png',
      '/common/images/location-status-restricted.png',
      '/common/images/location-status-unknown.png',
      '/common/images/location-status-closed@2x.png',
      '/common/images/location-status-open@2x.png',
      '/common/images/location-status-restricted@2x.png',
      '/common/images/location-status-unknown@2x.png',
    );
  }
}