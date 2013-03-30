<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('Calendar');

class CalendarAPIModule extends APIModule
{
    const ERROR_NO_SUCH_EVENT = 50;

    protected $id = 'calendar';
    protected $vmin = 1;
    protected $vmax = 3;

    protected $timezone;
    protected $fieldConfig;
    protected $feeds = array();
    protected static $defaultModel = 'CalendarDataModel';

    protected function getCalendarsForGroup($groupConfig) {
        $calendars = array();
        $type = $groupConfig['type'];
        if (isset($groupConfig['calendars'])) {
            foreach ($groupConfig['calendars'] as $calendarId) {
                $feedsForType = $this->getFeeds($type);
                if (isset($feedsForType[$calendarId])) {
                    $calendarData = $feedsForType[$calendarId];
                    $calendars[] = array(
                        'id' => strval($calendarId),
                        'title' => $calendarData['TITLE'],
                        'type' => $type,
                        );
                }
            }

        } elseif (isset($groupConfig['all'])) {
            foreach ($this->getFeeds($type) as $feedId => $feedData) {
                $calendars[] = array(
                    'id' => strval($feedId),
                    'title' => $feedData['TITLE'],
                    'type' => $type,
                    );
            }
        }
        return $calendars;
    }

    protected function getEventCategories() {
    
        $categories = array();
    
        if ($this->getOptionalModuleVar('SHOW_CATEGORIES', false, 'categories')) {
            $type     = $this->getArg('type', 'static');
            $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
            $limit    = $this->getArg('limit', $this->getOptionalModuleVar('SHOW_POPULAR_CATEGORIES',null,'categories'));
    
            $feed = $this->getFeed($calendar, $type);
            
            $categories = $feed->getEventCategories($limit);
        }
        
        return $categories;
    }

    // modified from CalendarWebModule
    protected function getFeedsByType() {
        $groups = $this->getModuleSections('api-groups');
        $feeds = array();
        foreach ($groups as $groupConfig) {
            $feedGroup = array(
                'id' => strval($groupConfig['id']),
                'title' => $groupConfig['title'],
                'calendars' => $this->getCalendarsForGroup($groupConfig),
                );

            if (count($feedGroup['calendars'])) {
                $feeds[] = $feedGroup;
            }
        }
        return $feeds;
    }

    // from CalendarWebModule

    protected function getFeeds($type) {
        if (isset($this->feeds[$type])) {
            return $this->feeds[$type];
        }

        $feeds = array();
        switch ($type) {
            case 'static':
                $feeds = $this->loadFeedData();
                break;

          case 'user':
          case 'resource':
            $section = $type=='user' ?  'user_calendars' :'resources';
            $sectionData = $this->getOptionalModuleSection($section);
            $controller = false;

            $modelClass = isset($sectionData['MODEL_CLASS']) ? $sectionData['MODEL_CLASS'] : 'CalendarListModel';
            $controller = CalendarDataModel::factory($modelClass, $sectionData);
    
            switch ($type)
            {
                case 'resource':
                    $feeds = $controller->getResources();
                    break;
                case 'user':
                    $feeds = $controller->getUserCalendars();
                    break;
            }
            break;
        }

        if ($feeds) {
            foreach ($feeds as $id => &$feed) {
                $feed['type'] = $type;
            }

            $this->feeds[$type] = $feeds;
        }

        return $feeds;
    }

    public function getDefaultFeed($type) {
        $feeds = $this->getFeeds($type);
        if ($indexes = array_keys($feeds)) {
            return current($indexes);
        }
    }

    private function getFeedData($index, $type) {
        $feeds = $this->getFeeds($type);
        if (isset($feeds[$index])) {
            return $feeds[$index];
        }
    }
    
    public function getFeed($index, $type) {
        $feeds = $this->getFeeds($type);
        if (isset($feeds[$index])) {
            $feedData = $feeds[$index];
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
            $controller = CalendarDataModel::factory($modelClass, $feedData);
            return $controller;
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString("ERROR_NO_CALENDAR_FEED", $index));
        }
    }

    protected function apiArrayFromEvent(CalendarEvent $event, $version) {
        $standardAttributes = array(
          'datetime', 'start', 'end', 'uid', 'summary', 'description', 'location', 'geo');
        
        $result = array(
            'id'            => $event->get_uid(),
            'title'         => $event->get_summary(),
            'description'   => nl2br($event->get_description()),
            'start'         => $event->get_start(),
            'end'           => $event->get_end(),
            'allday'        => ($event->isAllDay()),
            'location'      => $event->get_location(),
            'locationLabel' => '', // subclass to add dynamic title to location
        );

        // iCal GEO property -- subclass if event lat/lon comes from somewhere else
        $coords = $event->get_location_coordinates();
        if ($coords) {
          $result['latitude'] = $coords['lat'];
          $result['longitude'] = $coords['lon'];
        }
        
        foreach ($this->fieldConfig as $aField => $fieldInfo) {
            if (in_array($aField, $standardAttributes)) { continue; } // Handled these above
            
            $id      = self::argVal($fieldInfo, 'id', $aField);
            $title   = self::argVal($fieldInfo, 'label', $id);
            $section = self::argVal($fieldInfo, 'section', '');
            $type    = self::argVal($fieldInfo, 'type', '');
            $value   = $event->get_attribute($aField);
            
            if ($value) {
                if (self::argVal($fieldInfo, 'type', '') == 'category' && is_array($value)) {
                    $value = $this->apiArrayFromCategories($value);
                }
                
                if ($version < 2) {
                    $result[$title] = $value;
                    
                } else {
                    if (!isset($result['fields'])) {
                        $result['fields'] = array();
                    }
                    $result['fields'][] = array(
                        'id'      => $id,
                        'section' => $section,
                        'type'    => $type,
                        'title'   => $title,
                        'value'   => $value,
                    );
                }
            }
        }
        
        return $result;
    }
    
    protected function apiArrayFromCategories($categories) {
        $result = array();
        foreach ($categories as $category) {
            if (is_array($category)) {
                $name = $category['name'];
                $catid = $category['catid'];
            } elseif ($category instanceof CalendarCategory) {
                $name = $category->getName();
                $catid = $category->getId();
            }
            $result[] = array(
                'name' => $name,
                'id'   => $catid,
            );
        }
        return $result;
    }

    private function getStartArg($currentTime) {
        $startTime = $this->getArg('start', null);
        if ($startTime) {
            $start = new DateTime(date('Y-m-d H:i:s', $startTime), $this->timezone);
        } else {
            $start = new DateTime(date('Y-m-d H:i:s', $currentTime), $this->timezone);
            $start->setTime(0, 0, 0);
        }
        return $start;
    }

    private function getEndArg($startTime) {
        $endTime = $this->getArg('end', null);
        if ($endTime) {
            $end = new DateTime(date('Y-m-d H:i:s', $endTime), $this->timezone);
        } else {
            $end = new DateTime(date('Y-m-d H:i:s', $startTime), $this->timezone);
            $end->setTime(23, 59, 59);
        }
        return $end;
    }

    public function  initializeForCommand() {

        $this->timezone = Kurogo::siteTimezone();
        $this->fieldConfig = $this->getModuleSections('api-detail');

        $responseVersion = $this->requestedVersion < 2 ? 1 : 2;

        switch ($this->command) {
            case 'index':
            case 'groups':

                $response = $this->getFeedsByType();

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                
                break;

            case 'category':
                if (!$this->getArg('catid', false)) {
                    $error = new KurogoError(
                            5,
                            'Invalid Request',
                            'Invalid catid parameter');
                    $this->throwError($error);
                }
                // very similar to events, fallthrough to share code
            case 'events':
                $catid = $this->getArg('catid', '');
                $type     = $this->getArg('type', 'static');
                // the calendar argument needs to be urlencoded
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

                // default to the full day that includes current time
                $current = $this->getArg('time', time());
                $feed    = $this->getFeed($calendar, $type);

				// in v3 the start parameter is used to paginate (along with limit)
				if ($this->requestedVersion >= 3) {
					$start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
					$start->setTime(0,0,0);
					$startEvent = $this->getArg('start', 0);
				} else {
					$start = $this->getStartArg($current);
					$startEvent = 0;
				}

				$feed->setStartDate($start);
                $feed->setStart($startEvent);

                if ($limit = $this->getArg('limit')) {
                    $feed->setLimit($limit);
                } else {
                    $end = $this->getEndArg($start->format('U'));
                    $feed->setEndDate($end);
                }
                
                if ($catid) {
                    $iCalEvents = $feed->getEventsByCategory($catid);
                } else {
                    $iCalEvents = $feed->items();
                } 

                $events = array();
                $count  = 0;

                foreach ($iCalEvents as $iCalEvent) {
                    $events[] = $this->apiArrayFromEvent($iCalEvent, $responseVersion);
                    $count++;
                }

                $response = array(
                    'total'        => $count,
                    'returned'     => $count,
                    'displayField' => 'title',
                    'results'      => $events,
                    );

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);

                break;

            case 'detail':
                $eventID = $this->getArg('id', null);
                if (!$eventID) {
                    $error = new KurogoError(
                            5,
                            'Invalid Request',
                            'Invalid id parameter supplied');
                    $this->throwError($error);
                }

                // default to the full day that includes current time
                $current  = $this->getArg('time', time());
                $start    = $this->getStartArg($current);
                $end      = $this->getEndArg($start->format('U'));
                $type     = $this->getArg('type', 'static');
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

                $feed = $this->getFeed($calendar, $type);
                $feed->setStartDate($start);

                if (!$limit = $this->getArg('limit')) {
                    $feed->setEndDate($end);
                }
                
                if ($filter = $this->getArg('q')) {
                    $feed->addFilter('search', $filter);
                }

                if ($catid = $this->getArg('catid')) {
                    $feed->addFilter('category', $catid);
                }

                if ($event = $feed->getEvent($this->getArg('id'))) {
                    $eventArray = $this->apiArrayFromEvent($event, $responseVersion);
                    $this->setResponse($eventArray);
                    $this->setResponseVersion($responseVersion);

                } else {
                    $error = new KurogoError(
                            self::ERROR_NO_SUCH_EVENT,
                            'Invalid Request',
                            "The event $eventID cannot be found");
                    $this->throwError($error);
                }
                break;

            case 'search':
                $filter = $this->getArg('q', null);
                if ($filter) {
                    $searchTerms = trim($filter);

                    $current  = $this->getArg('time', time());
                    $start    = $this->getStartArg($current);
                    $end      = $this->getEndArg($start->format('U'));
                    $type     = $this->getArg('type', 'static');
                    $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
			
                    $feed     = $this->getFeed($calendar, $type);

                    $feed->setStartDate($start);
                    $feed->setEndDate($end);
			    	$this->setLogData($searchTerms);
                    
                    $iCalEvents = $feed->search($searchTerms);
					
                    $events = array();
                    $count = 0;
                    foreach ($iCalEvents as $iCalEvent) {
                        $events[] = $this->apiArrayFromEvent($iCalEvent, $responseVersion);
                        $count++;
                    }

                    $titleField = 'summary';
                    if (isset($this->fieldConfig['summary'], $this->fieldConfig['summary']['label'])) {
                        $titleField = $this->fieldConfig['summary']['label'];
                    }

                    $response = array(
                        'total' => $count,
                        'returned' => $count,
                        'displayField' => $titleField,
                        'results' => $events,
                        );

                    $this->setResponse($response);
                    $this->setResponseVersion($responseVersion);

                } else {
                    $error = new KurogoError(
                            5,
                            'Invalid Request',
                            'Invalid search parameter');
                    $this->throwError($error);
                }
                break;

            case 'calendars':
                $group = $this->getArg('group');
                $response = array();

                $groups = $this->getModuleSections('api-groups');
                foreach ($groups as $groupData) {
                    if ($groupData['id'] == $group) {
                        $response = $this->getCalendarsForGroup($groupData);
                        break;
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;

            case 'categories':
                $categories = $this->getEventCategories();

                
                $response = $this->apiArrayFromCategories($categories);
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;

            case 'resources':
                //break;

            case 'user':
                //break;

            default:
                $this->invalidCommand();
                break;
        }
    }
}
