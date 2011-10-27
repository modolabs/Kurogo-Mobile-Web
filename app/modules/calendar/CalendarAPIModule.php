<?php

Kurogo::includePackage('Calendar');

class CalendarAPIModule extends APIModule
{
    const ERROR_NO_SUCH_EVENT = 50;

    protected $id = 'calendar';
    protected $vmin = 1;
    protected $vmax = 1;

    protected $timezone;
    protected $fieldConfig;
    protected $feeds = array();

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

    // modified from CalendarWebModule
    protected function getFeedsByType() {
        $groups = $this->getAPIConfigData('groups');
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
                $sectionData = $this->getOptionalModuleSection('user_calendars');
                $listController = isset($sectionData['CONTROLLER_CLASS']) ? $sectionData['CONTROLLER_CLASS'] : '';
                if (strlen($listController)) {
                    $sectionData = array_merge($sectionData, array('SESSION'=>$this->getSession()));
                    $controller = CalendarListController::factory($listController, $sectionData);
                    $feeds = $controller->getUserCalendars();
                }
                break;

            case 'resource':
                $sectionData = $this->getOptionalModuleSection('resources');
                $listController = isset($sectionData['CONTROLLER_CLASS']) ? $sectionData['CONTROLLER_CLASS'] : '';
                if (strlen($listController)) {
                    $sectionData = array_merge($sectionData, array('SESSION'=>$this->getSession()));
                    $controller = CalendarListController::factory($listController, $sectionData);
                    $feeds = $controller->getResources();
                }
                break;
                
            case 'category':
                $sectionData = $this->getOptionalModuleSection('categories');
                $controllerClass = isset($sectionData['CONTROLLER_CLASS']) ? $sectionData['CONTROLLER_CLASS'] : '';
                if (strlen($controllerClass)) {
                    $controller = DataController::factory($controllerClass, $sectionData);
                    foreach ($controller->items() as $category) {
                        $feeds[$category->getId()] = array(
                            'TITLE' => $category->getName(),
                            'CATEGORY' => $category->getId(),
                            'BASE_URL' => $sectionData['EVENT_BASE_URL'],
                            'CONTROLLER_CLASS' => $sectionData['EVENT_CONTROLLER_CLASS'],
                            );
                    }
                }
                break;

            default:
                throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $type));
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
        $controller = null;
        $feeds = $this->getFeeds($type);
        if (isset($feeds[$index])) {
            $feedData = $feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = 'CalendarDataController';
            }
            $controller = CalendarDataController::factory($feedData['CONTROLLER_CLASS'],$feedData);
        } else {
            throw new KurogoDataException($this->getLocalizedString('ERROR_NO_CALENDAR_FEED', $index));
        }
        return $controller;
    }

    protected function apiArrayFromEvent(ICalEvent $event, $version) {
        $skipFields = array('datetime', 'start', 'end', 'uid', 'summary');
        
        $datetime = $event->get_attribute('datetime');
        $result = array(
            'id'     => $event->get_uid(),
            'title'  => $event->get_summary(),
            'allday' => ($datetime instanceOf DayRange),
            'start'  => $datetime->get_start(),
            'end'    => $datetime->get_end(),
        );
        
        foreach ($this->fieldConfig as $aField => $fieldInfo) {
            if (in_array($aField, $skipFields)) { continue; } // Handled these above
            
            $id = self::argVal($fieldInfo, 'id', $aField);
            $value = $event->get_attribute($aField);
            $title = self::argVal($fieldInfo, 'label', $id);
            $section = self::argVal($fieldInfo, 'section', '');
            
            if ($value) {
                if ($version < 2) {
                    if ($aField == 'description') {
                        $title = $aField;  // native v1 api looks for this key, ignore label
                    }
                    $result[$title] = $value;
                    
                } else {
                    if (!isset($result['fields'])) {
                        $result['fields'] = array();
                    }
                    $result['fields'][] = array(
                        'id'      => $id,
                        'section' => $section,
                        'title'   => $title,
                        'value'   => $value,
                    );
                }
            }
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
        $this->fieldConfig = $this->getAPIConfigData('detail');

        $responseVersion = $this->requestedVersion < 2 ? 1 : 2;

        switch ($this->command) {
            case 'index':
            case 'groups':

                $response = $this->getFeedsByType();

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                
                break;

            case 'events':
                $type     = $this->getArg('type', 'static');
                // the calendar argument needs to be urlencoded
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

                // default to the full day that includes current time
                $current = $this->getArg('time', time());
                $start   = $this->getStartArg($current);
                $end     = $this->getEndArg($start->getTimestamp());
                $feed    = $this->getFeed($calendar, $type);

                $feed->setStartDate($start);
                $feed->setEndDate($end);
                $iCalEvents = $feed->items();

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
                $end      = $this->getEndArg($start->getTimestamp());
                $type     = $this->getArg('type', 'static');
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

                $feed = $this->getFeed($calendar, $type);
                $feed->setStartDate($start);
                $feed->setEndDate($end);

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
                    $end      = $this->getEndArg($start->getTimestamp());
                    $type     = $this->getArg('type', 'static');
                    $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
			
                    $feed     = $this->getFeed($calendar, $type);

                    $feed->setStartDate($start);
                    $feed->setEndDate($end);
                    $feed->addFilter('search', $searchTerms);
                    $iCalEvents = $feed->items();
					
                    $events = array();
                    $count = 0;
                    foreach ($iCalEvents as $iCalEvent) {
                        $events[] = $this->apiArrayFromEvent($iCalEvent);
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

                $groups = $this->getAPIConfigData('groups');
                foreach ($groups as $groupData) {
                    if ($groupData['id'] == $group) {
                        $response = $this->getCalendarsForGroup($groupData);
                        break;
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;

            case 'resources':
                //break;

            case 'user':
                //break;

            case 'categories':
                //break;

            case 'category':
                //break;

            default:
                $this->invalidCommand();
                break;
        }
    }

}
