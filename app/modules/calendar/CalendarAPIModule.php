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
                $typeController = $type=='user' ? 'UserCalendarListController' :'ResourceListController';
                $sectionData = $this->getOptionalModuleSection('calendar_list');
                $listController = isset($sectionData[$typeController]) ? $sectionData[$typeController] : '';
                if (strlen($listController)) {
                    $sectionData = array_merge($sectionData, array('SESSION'=>$this->getSession()));
                    $controller = CalendarListController::factory($listController, $sectionData);
                    switch ($type) {
                        case 'resource':
                            $feeds = $controller->getResources();
                            break;
                        case 'user':
                            $feeds = $controller->getUserCalendars();
                            break;
                    }
                }
                break;
            default:
                throw new Exception("Invalid feed type $type");
        }

        if ($feeds) {
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
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = 'CalendarDataController';
            }
            $controller = CalendarDataController::factory($feedData['CONTROLLER_CLASS'],$feedData);
            return $controller;
        } else {
            throw new Exception("Error getting calendar feed for index $index");
        }
    }

    private function apiArrayFromEvent(ICalEvent $event) {
        foreach ($this->fieldConfig as $aField => $fieldInfo) {
            $fieldName = isset($fieldInfo['label']) ? $fieldInfo['label'] : $aField;
            $attribute = $event->get_attribute($aField);

            if ($attribute) {
                if (isset($fieldInfo['section'])) {
                    $section = $fieldInfo['section'];
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$fieldName] = $attribute;

                } else {
                    $result[$fieldName] = $attribute;
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

        switch ($this->command) {
            case 'groups':
                // special cases for two configs:
                // type = group
                // calendar = __USER__

                $groupConfig = $this->getAPIConfigData('groups');
                $groups = array();
                foreach ($groupConfig as $groupID => $groupData) {
                    $type = $groupData['type'];
                    $groupResult = array(
                        'title' => $groupData['title'],
                        'id'    => $groupData['id'],
                        );

                    if ($type == 'group') {
                        // TODO unimplemented API

                        if (isset($groupData['categories'])) {
                            $categories = $groupData['categories'];

                        } elseif (isset($groupData['all']) && $groupData['all']) {
                            $categories = array();
                        }

                        $groupResult['categories'] = $categories;

                    } else {
                        $calendars = array();

                        if (isset($groupData['calendars'])) {
                            $calendarIDs = $groupData['calendars'];
                            foreach ($calendarIDs as $calID) {
                                if ($calID == '__USER__') {
                                    $calID = $this->getDefaultFeed('user');
                                }
                                $feedData = $this->getFeedData($calID, $groupData['type']);
                                $calendars[] = array(
                                    'id'    => $calID,
                                    'type'  => $groupData['type'],
                                    'title' => $feedData['TITLE'],
                                    );
                            }

                        } elseif (isset($groupData['all']) && $groupData['all']) {
                            $feeds = $this->getFeeds($type);
                            foreach ($feeds as $id => $feedData) {
                                $calendars[] = array(
                                    'id'    => $id,
                                    'type'  => $groupData['type'],
                                    'title' => $feedData['TITLE'],
                                    );
                            }
                        }

                        $groupResult['calendars'] = $calendars;
                    }

                    $groups[] = $groupResult;
                }

                $response = array(
                    'total'        => count($groups),
                    'returned'     => count($groups),
                    'displayField' => 'title',
                    'results'      => $groups,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);
                
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
                    $events[] = $this->apiArrayFromEvent($iCalEvent);
                    $count++;
                }

                $response = array(
                    'total'        => $count,
                    'returned'     => $count,
                    'displayField' => 'title',
                    'results'      => $events,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

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
                    $eventArray = $this->apiArrayFromEvent($event);
                    $this->setResponse($eventArray);
                    $this->setResponseVersion(1);

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

                    $response = array(
                        'total' => $count,
                        'returned' => $count,
                        'displayField' => 'title',
                        'results' => $events,
                        );

                    $this->setResponse($response);
                    $this->setResponseVersion(1);

                } else {
                    $error = new KurogoError(
                            5,
                            'Invalid Request',
                            'Invalid search parameter');
                    $this->throwError($error);
                }
                break;

            case 'calendars':

                $feeds = array();
                foreach (array('static', 'user', 'resource') as $type) {
                    $typeFeeds = $this->getFeeds($type);
                    foreach ($typeFeeds as $feedID=>$feedData) {
                        $feeds[] = array(
                            'id'    => $feedID,
                            'type'  => $type,
                            'title' => $feedData['TITLE'],
                            );
                    }
                }

                $count = count($feeds);
                $response = array(
                    'total'        => $count,
                    'returned'     => $count,
                    'displayField' => 'title',
                    'results'      => $feeds,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

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


