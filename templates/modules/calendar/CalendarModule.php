<?php
/**
  * @package Module
  * @subpackage Calendar
  */

/**
  */
require_once realpath(LIB_DIR.'/TimeRange.php');

/**
  */
require_once realpath(LIB_DIR.'/ICalendar.php');

define('DAY_SECONDS', 24*60*60);

/**
  * @package Module
  * @subpackage Calendar
  */
class CalendarModule extends Module {
  protected $id = 'calendar';
  protected $feeds = array();
  protected $hasFeeds = true;
  protected $timezone;
  protected $feedFields = array('CACHE_LIFETIME'=>'Cache lifetime (seconds)', 'CONTROLLER_CLASS'=>'Controller Class','PARSER_CLASS'=>'Parser Class','EVENT_CLASS'=>'Event Class');
  protected $defaultSearchOption = 0;

  public function timezone()
  {
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
          $valueForType = $this->getSiteVar('LOCAL_AREA_CODE').$value;
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
          $urlForType = $this->getSiteVar('LOCAL_AREA_CODE').$value;
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
  private function dayURL($time, $type, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('day', array(
      'time' => $time,
      'type' => $type,
    ), $addBreadcrumb);
  }

  private function yearURL($year, $month, $type, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('year', array(
      'year'  => $year,
      'month' => $month,
      'type'  => $type,
    ), $addBreadcrumb);
  }
  
  private function categoryDayURL($time, $categoryID, $name, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'time' => $time,
      'id'   => $categoryID,
      'name' => $name, 
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
  
  private function subCategorysURL($category, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('sub-categorys', array(
      'id' => is_array($category) ? $category['catid'] : $category->get_cat_id(),
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
    $type = $this->getDefaultFeed();
    
    $feed = $this->getFeed($type); // this allows us to have multiple feeds in the future
    
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
        'url'      => $this->detailURL($iCalEvents[$i], array(), false, false),
        'title'    => $iCalEvents[$i]->get_summary(),
        'subtitle' => $subtitle,
      );
    }
    
    return count($iCalEvents);
  }
  
  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section) {
        case 'feeds':
            $feeds = $this->loadFeedData();
            $adminModule->assign('feeds', $feeds);
            $adminModule->assign('showFeedLabels', true);
            $adminModule->setTemplatePage('feedAdmin', $this->id);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
    }
  }

  public function getDefaultFeed() {
     if ($indexes = array_keys($this->feeds)) {
         return current($indexes);
     }
  }
  
  protected function getFeedTitle($index) {
    if (isset($this->feeds[$index])) {
        
        $feedData = $this->feeds[$index];
        return $feedData['TITLE'];
    } else {
        throw new Exception("Error getting calendar title for index $index");
    }
  }
  
  public function getFeed($index) {
    if (isset($this->feeds[$index])) {
        $feedData = $this->feeds[$index];
        $controller = CalendarDataController::factory($feedData);
        $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
        return $controller;
    } else {
        throw new Exception("Error getting calendar feed for index $index");
    }
  }
 
  protected function initialize() {
    $this->feeds    = $this->loadFeedData();
    $this->timezone = new DateTimeZone($this->getSiteVar('LOCAL_TIMEZONE'));
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
        
        $feed = $this->getFeed('events'); 
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
        
        $this->assign('events',  $events);        
        break;

      case 'index':
        $this->loadWebAppConfigFile('calendar-index','calendarPages');
        $today = mktime(12,0,0);
        $year = date('Y', $today);
      
        $this->assign('today',           $today);
        $this->assign('searchOptions',   $this->searchOptions);
        break;
      
      case 'categories':
        $categories = array();
        $type = $this->getArg('type', $this->getDefaultFeed());
        
        $feed = $this->getFeed($type);
        
        $categoryObjects = $feed->getEventCategories();

        foreach ($categoryObjects as $categoryObject) {
          $categories[] = array(
            'title' => $this->ucname($categoryObject->get_name()),
            'url' => $this->categoryURL($categoryObject),
          );
        }
        
        $this->assign('categories', $categories);
        break;
      
      case 'category':
        $type    = $this->getArg('type', $this->getDefaultFeed());
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
            $feed = $this->getFeed($type); // this allows us to have multiple feeds in the future
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
              'url'      => $this->detailURL($iCalEvent, array('catid' => $catid)),
              'title'    => $iCalEvent->get_summary(),
              'subtitle' => $subtitle,
            );
          }
        }
        
        $this->assign('events', $events);        
        break;
        
      case 'list':
        $current = $this->getArg('time', time());
        $type = $this->getArg('type', $this->getDefaultFeed());
        $limit = $this->getArg('limit', 20);
        $feed = $this->getFeed($type); 
        $this->setPageTitle($this->getFeedTitle($type));
        $this->setBreadcrumbTitle('List');
        $this->setBreadcrumbLongTitle($this->getFeedTitle($type));
        
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
            'url'      => $this->detailURL($iCalEvent),
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => $subtitle
          );
        }

        $this->assign('feedTitle', $this->getFeedTitle($type));
        $this->assign('type',    $type);
        $this->assign('current', $current);
        $this->assign('events',  $events);        
        break;
        
      case 'day':  
        $current = $this->getArg('time', time());
        $type = $this->getArg('type', $this->getDefaultFeed());
        $next = strtotime("+1 day", $current);
        $prev = strtotime("-1 day", $current);
        
        $feed = $this->getFeed($type); 
        
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
            'url'      => $this->detailURL($iCalEvent),
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => $subtitle
          );
        }

        $this->assign('feedTitle', $this->getFeedTitle($type));
        $this->assign('type',    $type);
        $this->assign('current', $current);
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextURL', $this->dayURL($next, $type, false));
        $this->assign('prevURL', $this->dayURL($prev, $type, false));
        $this->assign('events',  $events);        
        break;
        
      case 'detail':  
        $calendarFields = $this->loadWebAppConfigFile('calendar-detail', 'detailFields');
        $type = $this->getArg('type', $this->getDefaultFeed());
        
        $feed = $this->getFeed($type); // this allows us to have multiple feeds in the future
        
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
            } else {
              $field['title'] = nl2br($value);
            }
          }
          
          $fields[] = $field;
        }        

        $this->assign('fields', $fields);
        //error_log(print_r($fields, true));
        break;
        
      case 'search':
        $searchTerms  = $this->getArg('filter');
        $timeframeKey = $this->getArg('timeframe', 0);
        $type = $this->getArg('type', $this->getDefaultFeed());
          
        if ($searchTerms && isset($this->searchOptions[$timeframeKey])) {
          $searchOption = $this->searchOptions[$timeframeKey];
          
          $feed = $this->getFeed($type);
          
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
              'url'      => $this->detailURL($iCalEvent, array(
                'filter'    => $searchTerms, 
                'timeframe' => $timeframeKey)),
              'title'    => $iCalEvent->get_summary(),
              'subtitle' => $subtitle
            );
          }
                    
          $this->assign('events',      $events);        
          $this->assign('searchTerms', $searchTerms);        
          $this->assign('selectedOption', $timeframeKey);
          $this->assign('searchOptions',  $this->searchOptions);

        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'year':
        $year  = $this->getArg('year', date('Y'));
        $type  = $this->getArg('type', $this->getDefaultFeed());
        $month = $this->getArg('month', 1); //default to january
        
        $start = new DateTime(sprintf("%d%02d01", $year, $month), $this->timezone);
        $end   = new DateTime(sprintf("%d%02d01", $year+1, $month), $this->timezone);
        
        $feed = $this->getFeed($type);
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

        if ((date('Y')+1) > $year) {
          $this->assign('next',    $next);
          $this->assign('nextURL', $this->yearURL($year+1, $month, $type, false));
        }
        if ($year > intval(date('Y'))) {
          $this->assign('prev',    $prev);
          $this->assign('prevURL', $this->yearURL($year-1, $month, $type, false));
        }

        $this->assign('current', $current);
        $this->assign('events',  $events);        
        $this->assign('feedTitle', $this->getFeedTitle($type));
        break;
    }
    
  }
}
