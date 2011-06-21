<?php
/**
  * @package Module
  * @subpackage Calendar
  */


includePackage('Calendar');

define('DAY_SECONDS', 24*60*60);

/**
  * @package Module
  * @subpackage Calendar
  */
class CalendarWebModule extends WebModule {
  protected $id = 'calendar';
  protected $feeds = array();
  protected $timezone;
  protected $defaultSearchOption = 0;

  public function timezone() {
    return $this->timezone;
  }

  private $searchOptions = array(
    array("phrase" => "in the next 7 days",   "offset" => 7),
    array("phrase" => "in the next 15 days",  "offset" => 15),
    array("phrase" => "in the next 30 days",  "offset" => 30),
    array("phrase" => "in the past 15 days",  "offset" => -15),
    array("phrase" => "in the past 30 days",  "offset" => -30),
    //array("phrase" => "this school term",     "offset" => "term"),
    //array("phrase" => "this school year",     "offset" => "year")
  );
  
  private function getDatesForSearchOption($option) {
    $start = $end = time();
    
    switch ($option['offset']) {
      case 'term':
        // TODO
        break;
        
      case 'year':
        // TODO
        break;
        
      default: // day counts TODO: This is not daylight saving time safe
        if ($option['offset'] >= 0) {
          $end = $start + ($option['offset']*DAY_SECONDS);
        } else {
          $start = $end + ($option['offset']*DAY_SECONDS);
        }
        break;
    }

    return array (
      new DateTime(date('Y-m-d H:i:s', $start), $this->timezone), 
      new DateTime(date('Y-m-d H:i:s', $end  ), $this->timezone),
    );
  }
    
  private function timeText($event, $timeOnly=false) {
    if ($timeOnly) {
      if ($event->get_end() - $event->get_start() == -1) {
        return $event->get_start()->format('g:i a');
      } else {
        return date('g:ia', $event->get_start()).' - '.date('g:ia', $event->get_end());
      }
    } else {
      return strval($event->get_range());
    }
  }

  private function ucname($name) {
    $new_words = array();
    foreach(explode(' ', $name) as $word) {
      $new_word = array();
      foreach(explode('/', $word) as $sub_word) {
        $new_word[] = ucwords($sub_word);
      }
      $new_word = implode('/', $new_word);
      $new_words[] = $new_word;
    } 
    return implode(' ', $new_words);
  }
  
  private function valueForType($type, $value) {
    $valueForType = $value;
  
    switch ($type) {
      case 'datetime':
        if ($value instanceOf DayRange) {
          $valueForType = strval($value);
        } else {
          $valueForType = date("D M j", $value->get_start());
          if ($value->get_end() && $value->get_end()!=$value->get_start()) {
            if (date('Ymd', $value->get_start()) != date('Ymd', $value->get_end())) {
              $valueForType .= date(' g:i', $value->get_start());
              if (date('a', $value->get_start()) != date('a', $value->get_end())) {
                $valueForType .= date(' a', $value->get_start());
              }
        
              $valueForType .= date(" - D M j g:i a", $value->get_end());
            } else {
              $valueForType .= "<br/>" . date('g:i', $value->get_start()) . date("-g:i a", $value->get_end());
            }
          } else {
            $valueForType .= "<br/>" . date('g:i a', $value->get_start());
          }
        }
        
        break;

      case 'url':
        $valueForType = str_replace("http://http://", "http://", $value);
        if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
          $valueForType = 'http://'.$valueForType;
        }
        break;
        
      case 'phone':
        // add the local area code if missing
        if (preg_match('/^\d{3}-\d{4}/', $value)) {
          $valueForType = Kurogo::getSiteVar('LOCAL_AREA_CODE').$value;
        }
        $valueForType = str_replace('-', '-&shy;', str_replace('.', '-', $value));
        break;
      
      case 'email':
        $valueForType = str_replace('@', '@&shy;', $value);
        break;
        
      case 'category':
        $valueForType = $this->ucname($value);
        break;
    }
    
    return $valueForType;
  }
  
  private function urlForType($type, $value) {
    $urlForType = null;
  
    switch ($type) {
      case 'url':
        $urlForType = str_replace("http://http://", "http://", $value);
        if (strlen($urlForType) && !preg_match('/^http\:\/\//', $urlForType)) {
          $urlForType = 'http://'.$urlForType;
        }
        break;
        
      case 'phone':
        // add the local area code if missing
        if (preg_match('/^\d{3}-\d{4}/', $value)) {
          $urlForType = Kurogo::getSiteVar('LOCAL_AREA_CODE').$value;
        }
    
        // remove all non-word characters from the number
        $urlForType = 'tel:1'.preg_replace('/\W/', '', $value);
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
  private function dayURL($time, $type, $calendar, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('day', array(
      'time'     => $time,
      'type'     => $type,
      'calendar' => $calendar
    ), $addBreadcrumb);
  }

  private function yearURL($year, $month, $day, $type, $calendar, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('year', array(
      'year'     => $year,
      'month'    => $month,
      'day'      => $day,
      'type'     => $type,
      'calendar' => $calendar
    ), $addBreadcrumb);
  }
  
  private function categoryDayURL($time, $categoryID, $name, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'time'  => $time,
      'catid' => $categoryID,
      'name'  => $name, 
    ), $addBreadcrumb);
  }
  
  private function categoriesURL($addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('categories', array(), $addBreadcrumb);
  }
  
  private function categoryURL($category, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'catid'   => is_array($category) ? $category['catid'] : $category->get_cat_id(),
      'name' => is_array($category) ? $category['name']  : $this->ucname($category->get_name()),
    ), $addBreadcrumb);
  }
  
  private function detailURL($event, $options=array(), $addBreadcrumb=true, $noBreadcrumbs=false) {
    $args = array_merge($options, array(
      'id'   => $event->get_uid(),
      'time' => $event->get_start()
    ));
  
    if ($noBreadcrumbs) {
      return $this->buildURL('detail', $args);
    } else {
      return $this->buildBreadcrumbURL('detail', $args, $addBreadcrumb);
    }
  }
  
  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $searchOption = $this->searchOptions[$this->defaultSearchOption]; // default timeframe
    
    /* @TODO which calendars should be searched? */
    $type = 'static';
    $calendar = $this->getDefaultFeed($type);
    
    $feed = $this->getFeed($calendar, $type); // this allows us to have multiple feeds in the future
    
    list($start, $end) = $this->getDatesForSearchOption($searchOption);          
    $feed->setStartDate($start);
    $feed->setEndDate($end);
    $feed->addFilter('search', $searchTerms);
    $iCalEvents = array_values($feed->items());

    $limit = min($maxCount, count($iCalEvents));
    for ($i = 0; $i < $limit; $i++) {
      $subtitle = $this->timeText($iCalEvents[$i]);
      if ($briefLocation = $iCalEvents[$i]->get_location()) {
        $subtitle .= " | $briefLocation";
      }
  
      $results[] = array(
        'url'      => $this->detailURL($iCalEvents[$i], array(
            'calendar'=>$calendar,
            'type'=>$type
        ), false, false),
        'title'    => $iCalEvents[$i]->get_summary(),
        'subtitle' => $subtitle,
      );
    }
    
    return count($iCalEvents);
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
        $typeController = $type=='user' ? 'UserCalendarListController' :'ResourceListController';
        $sectionData = $this->getOptionalModuleSection('calendar_list');
        $listController = isset($sectionData[$typeController]) ? $sectionData[$typeController] : '';
        if (strlen($listController)) {
            $sectionData = array_merge($sectionData, array('SESSION'=>$this->getSession()));
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
  
  protected function getFeedTitle($index, $type) {
    $feeds = $this->getFeeds($type);
    if (isset($feeds[$index])) {
      return $feeds[$index]['TITLE'];
    } else {
      throw new Exception("Error getting calendar title for index $index");
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
 
  protected function initialize() {
    $this->timezone = new DateTimeZone(Kurogo::getSiteVar('LOCAL_TIMEZONE'));
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

        $feed = $this->getFeed($calendar, $type);
        $feed->setStartDate($start);
        $feed->setEndDate($end);
        $iCalEvents = $feed->items();
                
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $events[] = array(
            'url'      => $this->detailURL($iCalEvent, array(), false, true),
            'title'    => $iCalEvent->get_summary().':',
            'subtitle' => $this->timeText($iCalEvent, true),
          );
        }
        
        $this->assign('events', $events);
        break;
      
      case 'resources':
        if ($resourceFeeds = $this->getFeeds('resource')) {
          $resources = array();
          foreach ($resourceFeeds as $calendar=>$resource) {

            $feed = $this->getFeed($calendar, 'resource');
            $availability = 'Available';
            if ($event = $feed->getNextEvent()) {
                $now = time();
                if ($event->overlaps(new TimeRange($now, $now))) {
                    $availability = 'In use';
                } elseif ($event->overlaps(new TimeRange($now + 900, $now + 1800))) {
                    $availability = 'In use at ' . $this->timeText($event, true);
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
                $upcomingEvents[] = array(
                    'title'=>$event->get_summary(),
                    'subtitle'=>$this->timeText($event),
                    'url'=>$this->detailURL($event, array(
                        'type'=>'user',
                        'calendar'=>$userCalendar
                     ))
                );
            } else {
                $upcomingEvents[] = array(
                    'title'=>'No remaining events for today'
                );
            }
            
            $upcomingEvents[] = array(
                'title'=>'My calendar',
                'url'=>$this->dayURL(time(), 'user', $userCalendar)
            );
            if (count($feeds)>1) {
                $upcomingEvents[] = array(
                    'title'=>'Other calendars',
                    'url'=>$this->buildBreadcrumbURL('user', array())
                );
            }
            $this->assign('upcomingEvents', $upcomingEvents);
        }
        
        if ($resourceFeeds = $this->getFeeds('resource')) {
            $resources = array(
                array(
                    'title'=>'Resources',
                    'url'  =>$this->buildBreadcrumbURL('resources', array())
                )
            );
            $this->assign('resources', $resources);
        }

        $this->loadPageConfigFile('index','calendarPages');
        $this->assign('today',         mktime(0,0,0));
        $this->assign('searchOptions', $this->searchOptions);
        $this->assign('feeds',  $this->getFeedsByType());
        break;
      
      case 'categories':
        $categories = array();
        $type       = $this->getArg('type', 'static');
        $calendar   = $this->getArg('calendar', $this->getDefaultFeed($type));
        $feed       = $this->getFeed($calendar, $type);
        $categoryObjects = $feed->getEventCategories();

        foreach ($categoryObjects as $categoryObject) {
          $categories[] = array(
            'title'   => $this->ucname($categoryObject->get_name()),
            'url'     => $this->categoryURL($categoryObject),
          );
        }
        
        $this->assign('categories', $categories);
        break;
      
      case 'category':
        $type    = $this->getArg('type', 'static');
        $calendar= $this->getArg('calendar', $this->getDefaultFeed($type));
        $catid   = $this->getArg('catid', '');
        $name    = $this->getArg('name', '');
        $current = $this->getArg('time', time());
        $next    = $current + DAY_SECONDS;
        $prev    = $current - DAY_SECONDS;

        $this->setBreadcrumbTitle($name);
        $this->setBreadcrumbLongTitle($name);

        $this->assign('category', $this->ucname($name));
        
        $dayRange = new DayRange(time());
        
        $this->assign('current', $current);
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextURL', $this->categoryDayURL($next, $catid, $name, false));
        $this->assign('prevURL', $this->categoryDayURL($prev, $catid, $name, false));
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
            $subtitle = $this->timeText($iCalEvent);
            if ($briefLocation = $iCalEvent->get_location()) {
              $subtitle .= " | $briefLocation";
            }
          
            $events[] = array(
              'url'      => $this->detailURL($iCalEvent, array(
                'catid'    => $catid,
                'calendar' => $calendar,
                'type'     => $type
              )),
              'title'    => $iCalEvent->get_summary(),
              'subtitle' => $subtitle,
            );
          }
        }
        
        $this->assign('events', $events);        
        break;
        
      case 'list':
        $current  = $this->getArg('time', time());
        $type     = $this->getArg('type', 'static');
        $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
        $limit    = $this->getArg('limit', 20);
        $feed     = $this->getFeed($calendar, $type);
        
        $this->setPageTitle($this->getFeedTitle($calendar, $type));
        $this->setBreadcrumbTitle('List');
        $this->setBreadcrumbLongTitle($this->getFeedTitle($calendar, $type));
        
        $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
        $start->setTime(0,0,0);

        $feed->setStartDate($start);
        $iCalEvents = $feed->items(0, $limit);
                        
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $subtitle = $this->timeText($iCalEvent);
          if ($briefLocation = $iCalEvent->get_location()) {
            $subtitle .= " | $briefLocation";
          }

          $events[] = array(
            'url'      => $this->detailURL($iCalEvent, array(
              'calendar' => $calendar,
              'type'     => $type
            )),
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => $subtitle
          );
        }

        $this->assign('feedTitle', $this->getFeedTitle($calendar, $type));
        $this->assign('calendar', $calendar);
        $this->assign('current', $current);
        $this->assign('events',  $events);        
        break;
        
      case 'day':  
        $current = $this->getArg('time', time());
        $type    = $this->getArg('type', 'static');
        $calendar= $this->getArg('calendar', $this->getDefaultFeed($type));
        $next    = strtotime("+1 day", $current);
        $prev    = strtotime("-1 day", $current);
        
        $feed = $this->getFeed($calendar, $type);
        
        $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
        $start->setTime(0,0,0);
        $end = clone $start;
        $end->setTime(23,59,59);

        $feed->setStartDate($start);
        $feed->setEndDate($end);
        $iCalEvents = $feed->items();
                
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $subtitle = $this->timeText($iCalEvent, true);
            if ($briefLocation = $iCalEvent->get_location()) {
              $subtitle .= " | $briefLocation";
            }
        
          $events[] = array(
            'url'      => $this->detailURL($iCalEvent, array('calendar'=>$calendar,'type'=>$type)),
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => $subtitle
          );
        }

        $this->assign('feedTitle', $this->getFeedTitle($calendar, $type));
        $this->assign('type',    $type);
        $this->assign('calendar',$calendar);
        $this->assign('current', $current);
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextURL', $this->dayURL($next, $type, $calendar, false));
        $this->assign('prevURL', $this->dayURL($prev, $type, $calendar, false));
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
        
        $time = $this->getArg('time', time());

        if ($event = $feed->getItem($this->getArg('id'), $time)) {
          $this->assign('event', $event);
        } else {
          throw new Exception("Event not found");
        }
            
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
                $field = array_merge($field, Kurogo::moduleLinkForValue($info['module'], $value, array($this->configModule=>$event)));
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
          $timeframeKey   = $this->getArg('timeframe', 0);
          $searchOption   = $this->searchOptions[$timeframeKey];
          $type           = $this->getArg('type', 'static');
          $searchCalendar = $this->getArg('calendar', $this->getDefaultFeed($type));
          
          if (preg_match("/^(.*?)\|(.*?)$/", $searchCalendar, $bits)) {
            $type     = $bits[1];
            $calendar = $bits[2];
          } else {
            $calendar = $searchCalendar;
          }
          
          $feed         = $this->getFeed($calendar, $type);
          
          list($start, $end) = $this->getDatesForSearchOption($searchOption);          
          $feed->setStartDate($start);
          $feed->setEndDate($end);
          $feed->addFilter('search', $searchTerms);
          $iCalEvents = $feed->items();

          $events = array();
          foreach($iCalEvents as $iCalEvent) {
            $subtitle = $this->timeText($iCalEvent);
            if ($briefLocation = $iCalEvent->get_location()) {
              $subtitle .= " | $briefLocation";
            }
        
            $events[] = array(
              'url'       => $this->detailURL($iCalEvent, array(
              'calendar'  => $calendar,
              'type'      => $type,
              'filter'    => $searchTerms, 
              'timeframe' => $timeframeKey)),
              'title'     => $iCalEvent->get_summary(),
              'subtitle'  => $subtitle
            );
          }
                    
          $this->assign('events'        , $events);        
          $this->assign('searchTerms'   , $searchTerms);        
          $this->assign('selectedOption', $timeframeKey);
          $this->assign('searchOptions' , $this->searchOptions);
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
        $defaultStartMonth = Kurogo::getOptionalSiteVar(strtoupper($calendar).'_CALENDAR_START_MONTH', 1);
        $defaultStartDay   = Kurogo::getOptionalSiteVar(strtoupper($calendar).'_CALENDAR_START_DAY', 1);

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
        $maxNextYears = Kurogo::getOptionalSiteVar(strtoupper($calendar).'_CALENDAR_MAX_NEXT_YEARS', 1);
        $maxPrevYears = Kurogo::getOptionalSiteVar(strtoupper($calendar).'_CALENDAR_MAX_PREV_YEARS', 1);

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
        $this->assign('feedTitle', $this->getFeedTitle($calendar, $type));
        break;
    }
  }
}
