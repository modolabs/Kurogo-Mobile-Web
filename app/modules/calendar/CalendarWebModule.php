<?php
/**
  * @package Module
  * @subpackage Calendar
  */


Kurogo::includePackage('Calendar');

define('DAY_SECONDS', 24*60*60);

/**
  * @package Module
  * @subpackage Calendar
  */
class CalendarWebModule extends WebModule {
  protected $id = 'calendar';
  protected $feeds = array();
  protected $timezone;

  protected function getTitleForSearchOptions($intervalType, $offset, $forward=true) {
    if ($offset < 0) {
      $relation = $this->getLocalizedString("SEARCH_RANGE_PREVIOUS");
      $offset = -$offset;
    } else {
      $relation = $this->getLocalizedString("SEARCH_RANGE_NEXT");
    }

    switch ($intervalType) {
      case 'day':
          if ($offset == 1) {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_DAY", $relation);
          } else {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_MULTIDAY", $relation, strval($offset));
          }
          break;
      case 'week':
          if ($offset == 1) {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_WEEK", $relation);
          } else {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_MULTIWEEK", $relation, strval($offset));
          }
          break;
      case 'month':
          if ($offset == 1) {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_MONTH", $relation);
          } else {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_MULTIMONTH", $relation, strval($offset));
          }
          break;
      case 'year':
          if ($offset == 1) {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_YEAR", $relation);
          } else {
            $result = $this->getLocalizedString("SEARCH_RANGE_TITLE_MULTIYEAR", $relation, strval($offset));
          }
          break;
    }
    return $result;
  }

  protected function searchOptions() {
    $searchOptions = array();
    $searchRanges = $this->getModuleSections('searchranges');
    foreach ($searchRanges as $rangeConfig) {
      $searchOptions[] = array(
        'phrase' => $this->getTitleForSearchOptions($rangeConfig['type'], $rangeConfig['offset']));
    }
    return $searchOptions;
  }

  protected function getDatesForSearchOptions($intervalType, $offset) {
    $now = time();
    $day = intval(date('j', $now));
    $month = intval(date('n', $now));
    $year = intval(date('Y', $now));

    $startDT = new DateTime();
    $endDT = new DateTime();

    $dayInterval = $monthInterval = $yearInterval = 0;
    switch ($intervalType) {
      case 'day':   $dayInterval = $offset; break;
      case 'week':  $dayInterval = $offset * 7; break;
      case 'month': $monthInterval = $offset; break;
      case 'year':  $yearInterval = $offset; break;
    }

    if ($offset >= 0) { // searching future events
      $startDT->setDate($year, $month, $day);
      $endDT->setDate($year + $yearInterval, $month + $monthInterval, $day + $dayInterval);
    } else {
      $startDT->setDate($year + $yearInterval, $month + $monthInterval, $day + $dayInterval);
      $endDT->setDate($year, $month, $day);
    }

    return array($startDT, $endDT);
  }  
    
  protected function timeText($event, $timeOnly=false) {
    if ($timeOnly) {
      if ($event->get_end() - $event->get_start() == -1) {
        return DateFormatter::formatDate($event->get_start(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
      } else {
        return DateFormatter::formatDateRange($event->get_range(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
      }
    } else {
        return DateFormatter::formatDateRange($event->get_range(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
    }
  }

  // TODO: this appears to be a harvard relic
  // this kind of formatting should be done downstream, not here
  protected function formatTitle($name) {
    $new_words = array();
    foreach(explode('/', $name) as $word) {
      $new_words[] = ucwords($word);
    } 
    return implode('/', $new_words);
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

  // URL DEFINITIONS
  protected function dayURL($time, $type, $calendar, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('day', array(
      'time'     => $time,
      'type'     => $type,
      'calendar' => $calendar
    ), $addBreadcrumb);
  }

  protected function yearURL($year, $month, $day, $type, $calendar, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('year', array(
      'year'     => $year,
      'month'    => $month,
      'day'      => $day,
      'type'     => $type,
      'calendar' => $calendar
    ), $addBreadcrumb);
  }
  
  protected function categoryDayURL($time, $categoryID, $name, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'time'  => $time,
      'catid' => $categoryID,
      'name'  => $name, 
    ), $addBreadcrumb);
  }
  
  protected function categoriesURL($addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('categories', array(), $addBreadcrumb);
  }
  
  protected function categoryURL($category, $addBreadcrumb=true) {
    $options = array();
    if ($addBreadcrumb) {
      $options['addBreadcrumb'] = true;
    }
    return $this->linkForCategory($category, $options);
  }
  
    public function searchItems($searchTerms, $limit=null, $options=null) {  

        $type     = isset($options['type']) ? $options['type'] : 'static';
        $calendar = isset($options['calendar']) ? $options['calendar'] : $this->getDefaultFeed($type);
        $feed     = $this->getFeed($calendar, $type);
        
        if (isset($options['timeframe'])) {
          $searchRanges = $this->getModuleSections('searchranges');
          $selectedRange = $searchRanges[$options['timeframe']];
          list($start, $end) = $this->getDatesForSearchOptions($selectedRange['type'], $selectedRange['offset']);

          $options['start'] = $start;
          $options['end'] = $end;
        }
        
        if (isset($options['start'])) {
            $feed->setStartDate($options['start']);
        }
        
        if (isset($options['end'])) {
            $feed->setEndDate($options['end']);
        }
    
        if ($searchTerms) {
            $feed->addFilter('search', $searchTerms);
        }

        return $feed->items();
    }

    public function linkForCategory($category, $data=null) {
      $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;

      if (is_array($category)) {
        $title = $category['name'];
        $catid = $category['catid'];
      } elseif ($category instanceof CalendarCategory) {
        $title = $category->getName();
        $catid = $category->getId();
      } else {
        // downstream compatibility
        // these methods are implemented by harvard's Harvard_Event_Category class
        $title = $category->get_name();
        $catid = $category->get_cat_id();
      }
      $options = array('name' => $title, 'catid' => $catid);
      $url = $this->buildBreadcrumbURL('category', $options, $addBreadcrumb);

      $title = $this->formatTitle($title);
      return array(
        'title' => $title,
        'url' => $url,
        );
    }

    public function linkforItem(KurogoObject $event, $data=null) {
      $subtitle = $this->timeText($event);
      if ($briefLocation = $event->get_location()) {
        $subtitle .= " | $briefLocation";
      }
      
      $options = array(
        'id'   => $event->get_uid(),
        'time' => $event->get_start()
      );
      
      foreach (array('type','calendar','searchTerms','timeframe','catid','filter') as $field) {
          if (isset($data[$field])) {
              $options[$field] = $data[$field];
          }
      }

      $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
      $noBreadcrumbs = isset($data['noBreadcrumbs']) ? $data['noBreadcrumbs'] : false;

      if ($noBreadcrumbs) {
        $url = $this->buildURL('detail', $options);
      } else {
        $url = $this->buildBreadcrumbURL('detail', $options, $addBreadcrumb);
      }

      return array(
        'url'       => $url,
        'title'     => $event->get_summary(),
        'subtitle'  => $subtitle
      );
    }

    protected function getFeedsByType() {  
        $feeds = array();
        foreach (array('user','resource','static') as $type) {
            $typeFeeds = $this->getFeeds($type);
            foreach ($typeFeeds as $feed=>$feedData) {
                $feeds[$type][$type . '|' . $feed] = $feedData['TITLE'];
            }
        }
        return $feeds;
    }
    
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
        $listController = isset($sectionData['CONTROLLER_CLASS']) ? $sectionData['CONTROLLER_CLASS'] : '';
        if (strlen($listController)) {
            $controller = CalendarListController::factory($listController, $sectionData);
            switch ($type)
            {
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
        throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $type));
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
  
  protected function getFeedTitle($index, $type) {
    $feeds = $this->getFeeds($type);
    if (isset($feeds[$index])) {
      return $feeds[$index]['TITLE'];
    } else {
      throw new KurogoConfigurationException($this->getLocalizedString("ERROR_NO_CALENDAR_TITLE", $index));
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
      throw new KurogoConfigurationException($this->getLocalizedString("ERROR_NO_CALENDAR_FEED", $index));
    }
  }
 
    protected function initialize() {
        $this->timezone = Kurogo::siteTimezone();
    }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'pane':
        $start = new DateTime(date('Y-m-d H:i:s', time()), $this->timezone);
        $start->setTime(0,0,0);
        $end = clone $start;
        $end->setTime(23,59,59);

        $type     = $this->getArg('type', 'static');
        $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
        
        $options = array(
            'type'=>$type,
            'calendar'=>$calendar,
            'start'=>$start,
            'end'=>$end
        );
        
        $iCalEvents = $this->searchItems('', null, $options);
        $options['noBreadcrumbs'] = true;
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $events[] = $this->linkforItem($iCalEvent, $options, false);
        }
        
        $this->assign('events', $events);
        break;
      
      case 'resources':
        if ($resourceFeeds = $this->getFeeds('resource')) {
          $resources = array();
          foreach ($resourceFeeds as $calendar=>$resource) {

            $feed = $this->getFeed($calendar, 'resource');
            $availability = $this->getLocalizedString('RESOURCE_AVAILABLE');
            if ($event = $feed->getNextEvent()) {
                $now = time();
                if ($event->overlaps(new TimeRange($now, $now))) {
                    $availability = $this->getLocalizedString('RESOURCE_IN_USE');
                } elseif ($event->overlaps(new TimeRange($now + 900, $now + 1800))) {
                    $availability = $this->getLocalizedString('RESOURCE_IN_USE_TIME', $this->timeText($event, true));
                }
            }
                
            $resources[$calendar] = array(
              'title' => $resource['TITLE'],
              'subtitle'=>$availability,
              'url'   => $this->buildBreadcrumbURL('day', array(
                'type'     => 'resource', 
                'calendar' => $calendar
              ))
            );
          }
          $this->assign('resources', $resources);
        } else {
            $this->redirectTo('index');
        }
        break;
        
      case 'user':
        if ($userFeeds = $this->getFeeds('user')) {
          $userCalendars = array();
          foreach ($userFeeds as $id=>$calendar) {
            $userCalendars[$id] = array(
              'title' => $calendar['TITLE'],
              'url'   => $this->buildBreadcrumbURL('day', array(
                'type'     => 'user', 
                'calendar' => $id
              )),
            );
          }
          $this->assign('userCalendars', $userCalendars);
        } else {
            $this->redirectTo('index');
        }
      
        break;

      case 'index':
        if ($userCalendar = $this->getDefaultFeed('user')) {
            $this->assign('selectedFeed', 'user|' . $userCalendar);
            $feed = $this->getFeed($userCalendar, 'user');
            $feeds = $this->getFeeds('user');
            $upcomingEvents = array();
            if ($event = $feed->getNextEvent(true)) {
                $upcomingEvents[] = $this->linkForItem($event, array(
                    'type'    =>'user',
                    'calendar'=>$userCalendar
                ));
            } else {
                $upcomingEvents[] = array(
                    'title'=>$this->getLocalizedString('NO_EVENTS_REMAINING')
                );
            }
            
            $upcomingEvents[] = array(
                'title'=>$this->getLocalizedString('MY_CALENDAR'),
                'url'=>$this->dayURL(time(), 'user', $userCalendar)
            );
            if (count($feeds)>1) {
                $upcomingEvents[] = array(
                    'title'=>$this->getLocalizedString('OTHER_CALENDARS'),
                    'url'=>$this->buildBreadcrumbURL('user', array())
                );
            }
            $this->assign('upcomingEvents', $upcomingEvents);
        }
        
        if ($resourceFeeds = $this->getFeeds('resource')) {
            $resources = array(
                array(
                    'title'=>$this->getLocalizedString('RESOURCES'),
                    'url'  =>$this->buildBreadcrumbURL('resources', array())
                )
            );
            $this->assign('resources', $resources);
        }

        $this->loadPageConfigFile('index','calendarPages');
        $this->assign('today',         mktime(0,0,0));
        $this->assign('dateFormat', $this->getLocalizedString("LONG_DATE_FORMAT"));
        $this->assign('placeholder', $this->getLocalizedString('SEARCH_TEXT'));
        $this->assign('searchOptions', $this->searchOptions());
        $this->assign('feeds',  $this->getFeedsByType());
        break;
      
      case 'categories':
        $categories = array();
        $type       = $this->getArg('type', 'static');
        $calendar   = $this->getArg('calendar', $this->getDefaultFeed($type));
        $feed       = $this->getFeed($calendar, $type);
        $categoryObjects = $feed->getEventCategories();

        foreach ($categoryObjects as $categoryObject) {
          $categories[] = $this->linkForCategory($categoryObject);
        }
        
        $this->assign('categories', $categories);
        break;
      
      case 'category':
        $type    = $this->getArg('type', 'static');
        $calendar= $this->getArg('calendar', $this->getDefaultFeed($type));
        $catid   = $this->getArg('catid', '');
        $name    = $this->getArg('name', '');
        $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
        $next    = strtotime("+1 day", $current);
        $prev    = strtotime("-1 day", $current);

        $this->setBreadcrumbTitle($name);
        $this->setBreadcrumbLongTitle($name);

        // wouldn't this already be formatted from the url building stage?
        $catname = $this->formatTitle($name);
        $this->assign('category', $catname);
        $this->setLogData($catid, $catname);
        
        $dayRange = new DayRange(time());
        
        $this->assign('current', $current);
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextURL', $this->categoryDayURL($next, $catid, $name, false));
        $this->assign('prevURL', $this->categoryDayURL($prev, $catid, $name, false));
        $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
        $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DATE_FORMAT'));
        $this->assign('isToday', $dayRange->contains(new TimeRange($current)));

        $events = array();
        
        if (strlen($catid) > 0) {
            $feed = $this->getFeed($calendar, $type); // this allows us to have multiple feeds in the future
            $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
    
            $feed->setStartDate($start);
            $feed->setEndDate($end);
            $feed->addFilter('category', $catid);
            $iCalEvents = $feed->items();
          
            foreach($iCalEvents as $iCalEvent) {
          
                $events[] = $this->linkForItem($iCalEvent, array(
                    'catid'    =>$catid,
                    'calendar' =>$calendar,
                    'type'     =>$type)
                );
            }          
        }
        
        $this->assign('events', $events);        
        break;
        
      case 'list':
        $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
        $type     = $this->getArg('type', 'static');
        $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
        $limit    = $this->getArg('limit', 20);
        $feed     = $this->getFeed($calendar, $type);
        $title    = $this->getFeedTitle($calendar, $type);
        $this->setLogData($type . ':' . $calendar, $title);
        $this->setPageTitle($title);
        $this->setBreadcrumbTitle('List');
        $this->setBreadcrumbLongTitle($title);
        
        $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
        $start->setTime(0,0,0);

        $feed->setStartDate($start);
        $iCalEvents = $feed->items(0, $limit);
                        
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
        
            $events[] = $this->linkForItem($iCalEvent, array(
                'calendar' =>$calendar,
                'type'     =>$type)
            );
        }

        $this->assign('feedTitle', $this->getFeedTitle($calendar, $type));
        $this->assign('calendar', $calendar);
        $this->assign('current', $current);
        $this->assign('events',  $events);        
        $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
        $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DATE_FORMAT'));
        break;
        
      case 'day':  
        $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
        $type    = $this->getArg('type', 'static');
        $calendar= $this->getArg('calendar', $this->getDefaultFeed($type));
        $next    = strtotime("+1 day", $current);
        $prev    = strtotime("-1 day", $current);
        
        $feed = $this->getFeed($calendar, $type);
        $title    = $this->getFeedTitle($calendar, $type);
        $this->setLogData($type . ':' . $calendar, $title);
        
        $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
        $start->setTime(0,0,0);
        $end = clone $start;
        $end->setTime(23,59,59);

        $feed->setStartDate($start);
        $feed->setEndDate($end);
        $iCalEvents = $feed->items();
                
        $events = array();
        foreach($iCalEvents as $iCalEvent) {

            $events[] = $this->linkForItem($iCalEvent, array(
                'calendar' =>$calendar,
                'type'     =>$type)
            );
        }

        $dayRange = new DayRange(time());

        $this->assign('feedTitle', $title);
        $this->assign('type',    $type);
        $this->assign('calendar',$calendar);
        $this->assign('current', $current);
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextURL', $this->dayURL($next, $type, $calendar, false));
        $this->assign('prevURL', $this->dayURL($prev, $type, $calendar, false));
        $this->assign('titleDateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
        $this->assign('linkDateFormat', $this->getLocalizedString('SHORT_DATE_FORMAT'));
        $this->assign('isToday', $dayRange->contains(new TimeRange($current)));
        $this->assign('events',  $events);        
        break;
        
      case 'detail':  
        $calendarFields = $this->getModuleSections('page-detail');
        $type = $this->getArg('type', 'static');
        $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
        
        $feed = $this->getFeed($calendar, $type);
        
        if ($filter = $this->getArg('filter')) {
            $feed->addFilter('search', $filter);
        }

        if ($catid = $this->getArg('catid')) {
            $feed->addFilter('category', $catid);
        }
        
        $time = $this->getArg('time', time(), FILTER_VALIDATE_INT);

        if ($event = $feed->getItem($this->getArg('id'), $time)) {
          $this->assign('event', $event);
        } else {
          throw new KurogoUserException($this->getLocalizedString('ERROR_NOT_FOUND'));
        }

        $this->setLogData($event->get_uid(), $event->get_summary());
            
        // build the list of attributes
        $allKeys = array_keys($calendarFields);

        $fields = array();
        foreach ($calendarFields as $key => $info) {
          $field = array();
          
          $value = $event->get_attribute($key);
          if (empty($value)) { continue; }

          if (isset($info['label'])) {
            $field['label'] = $info['label'];
          }
          
          if (isset($info['class'])) {
            $field['class'] = $info['class'];
          }
          
          if (is_array($value)) {		
            $fieldValues = array();
            foreach ($value as $item) {
              $fieldValue = '';
              $fieldValueUrl = null;
              
              if (isset($info['type'])) {
                $fieldValue  = $this->valueForType($info['type'], $item);
                $fieldValueUrl = $this->urlForType($info['type'], $item);
              } else {
                $fieldValue = $item;
              }
              
              if (isset($fieldValueUrl)) {
                $fieldValue = '<a href="'.$fieldValueUrl.'">'.$fieldValue.'</a>';
              }
              
              $fieldValues[] = $fieldValue;
            }
            $field['title'] = implode(', ', $fieldValues);
          
          } else {
            if (isset($info['type'])) {
              $field['title'] = $this->valueForType($info['type'], $value);
              $field['url']   = $this->urlForType($info['type'], $value);
            } elseif (isset($info['module'])) {
                $field = array_merge($field, Kurogo::moduleLinkForValue($info['module'], $value, $this, $event));
            } else {
              $field['title'] = nl2br($value);
            }
          }
          
          if (isset($info['urlfunc'])) {
            $urlFunction = create_function('$value,$event', $info['urlfunc']);
            $field['url'] = $urlFunction($value, $event);
          }
          
          $fields[] = $field;
        }        

        $this->assign('fields', $fields);
        //error_log(print_r($fields, true));
        break;
        
      case 'search':
        if ($filter = $this->getArg('filter')) {
          $searchTerms    = trim($filter);
          $timeframe      = $this->getArg('timeframe', 0);
          $type           = $this->getArg('type', 'static');
          $searchCalendar = $this->getArg('calendar', $this->getDefaultFeed($type));
          
          if (preg_match("/^(.*?)\|(.*?)$/", $searchCalendar, $bits)) {
            $type     = $bits[1];
            $calendar = $bits[2];
          } else {
            $calendar = $searchCalendar;
          }
          
          $options = array(
            'type'    =>$type,
            'calendar'=>$calendar,
            'timeframe'=>$timeframe
          );
          
          $this->setLogData($searchTerms);
          $iCalEvents = $this->searchItems($searchTerms, null, $options);
          $events = array();
          foreach($iCalEvents as $iCalEvent) {

            $events[] = $this->linkForItem($iCalEvent, array(
                'filter'   =>$searchTerms, 
                'timeframe'=>$timeframe,
                'calendar' =>$calendar,
                'type'     =>$type)
            );

          }
                    
          $this->assign('events'        , $events);        
          $this->assign('searchTerms'   , $searchTerms);        
          $this->assign('selectedOption', $timeframe);
          $this->assign('searchOptions' , $this->searchOptions());
          $this->assign('feeds'         , $this->getFeedsByType());
          $this->assign('searchCalendar', $searchCalendar);

        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'year':
        $type     = $this->getArg('type', 'static');
        $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

        // Figure out when the calendar year starts; default to January 1
        // Allow setting either per-calendar with config or override with url parameter
        $defaultStartMonth = $this->getOptionalModuleVar(strtoupper($calendar).'_CALENDAR_START_MONTH', 1);
        $defaultStartDay   = $this->getOptionalModuleVar(strtoupper($calendar).'_CALENDAR_START_DAY', 1);

        $month = intval($this->getArg('month', $defaultStartMonth));
        $day   = intval($this->getArg('day', $defaultStartDay));

        // Figure out which year we are currently in based on year start month and day:
        $currentYear = intval(date('Y'));
        $yearStartForCurrentYear = new DateTime(sprintf("%d%02d%02d", $currentYear, $month, $day), $this->timezone);
        if (time() < intval($yearStartForCurrentYear->format('U'))) {
          $currentYear--;  // today's date is before the start date for the current year
        }

        // Which year to view; default to current year based on year start month and day:
        $year = intval($this->getArg('year', $currentYear));

        $start = new DateTime(sprintf("%d%02d%02d", $year, $month, $day), $this->timezone);
        $end   = new DateTime(sprintf("%d%02d%02d", $year+1, $month, $day), $this->timezone);

        $feed = $this->getFeed($calendar, $type);
        $feed->setStartDate($start);
        $feed->setEndDate($end);
        $feed->addFilter('year', $year);
        $iCalEvents = $feed->items();
        $title = $this->getFeedTitle($calendar, $type);
        $this->setLogData($type . ':' . $calendar, $title);

        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $events[] = array(
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => date('l F j', $iCalEvent->get_start()), 
          );
        }

        $current =  $year   .'&nbsp;-&nbsp;'.($year+1);
        $next    = ($year+1).'&nbsp;-&nbsp;'.($year+2);
        $prev    = ($year-1).'&nbsp;-&nbsp;'. $year;

        // How many years into the future and past to page:
        $maxNextYears = $this->getOptionalModuleVar(strtoupper($calendar).'_CALENDAR_MAX_NEXT_YEARS', 1);
        $maxPrevYears = $this->getOptionalModuleVar(strtoupper($calendar).'_CALENDAR_MAX_PREV_YEARS', 1);

        if ($year < $currentYear + $maxNextYears) {
          $this->assign('next',    $next);
          $this->assign('nextURL', $this->yearURL($year+1, $month, $day, $type, $calendar, false));
        }
        if ($year > $currentYear - $maxPrevYears) {
          $this->assign('prev',    $prev);
          $this->assign('prevURL', $this->yearURL($year-1, $month, $day, $type, $calendar, false));
        }

        $this->assign('current', $current);
        $this->assign('events',  $events);        
        $this->assign('feedTitle', $title);
        break;
    }
  }
}
