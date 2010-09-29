<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/harvard_calendar.php');

class CalendarModule extends Module {
  protected $id = 'calendar';
  
  private $searchOptions = array(
    array("phrase" => "in the next 7 days",   "offset" => 7),
    //array("phrase" => "in the next 15 days",  "offset" => 15),
    //array("phrase" => "in the next 30 days",  "offset" => 30),
    //array("phrase" => "in the past 15 days",  "offset" => -15),
    //array("phrase" => "in the past 30 days",  "offset" => -30),
    //array("phrase" => "this school term",     "offset" => "term"),
    //array("phrase" => "this school year",     "offset" => "year")
  );


  private function dayInfo($time, $offset=0) {
    $time += $offset * 24 * 60 * 60;
    return array(
      "weekday"       => date('l', $time),
      "month"         => date('F', $time),
      "month_3Let"    => date('M', $time),
      "day_num"       => date('j', $time),
      "year"          => date('Y', $time),
      "month_num"     => date('m', $time),
      "day_3Let"      => date('D', $time),
      "day_num_2dig"  => date('d', $time),
      "date"          => date('Y/m/d', $time),
      "time"          => strtotime(date("Y-m-d 12:00:00", $time))
    );
  }
  
  private function timeText($event) {
    if ($event->get_end() - $event->get_start() == -1) {
      return $event->get_range()->format('D M j').' '.date('g:i a', $event->get_start());
    }
    return $event->get_range()->format('D M j g:i a');
  
    /*
    $out = substr($event->start->weekday, 0, 3) . ' ';
    $out .= substr($event->start->monthname, 0, 3) . ' ';
    $out .= (int)$event->start->day . ' ';
  
    $out .= MIT_Calendar::timeText($event);
    return $out;
    */
  }
  
  private function briefLocation($event) {
    //if($loc = $event->shortloc) {
    //  return $loc;
    //} else {
      return $event->get_location();
    //}
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

  private function searchDates($option) {
    $offset = $this->options[$option]["offset"];
    $time = time();
    $day1 = dayInfo($time);

    if(is_int($offset)) {
      $day2 = dayInfo($time, $offset);
      if($offset > 0) {
        return array("start" => $day1['date'], "end" => $day2['date']);
      } else {
        return array("start" => $day2['date'], "end" => $day1['date']); 
      }
    } else {
      switch($offset) {
        case "term":
          if($day1['month_num'] < 7) {
            $endDate = "{$day1['year']}/07/01";
	  } else {
            $endDate = "{$day1['year']}/12/31";
          }
          break;

        case "year": 
          if($day1['month_num'] < 7) {
            $endDate = "{$day1['year']}/07/01";
	  } else {
            $year = $day1['year'] + 1;
            $endDate = "$year/07/01";
          }
          break;
      }    
      return array("start" => $day1['date'], "end" => $endDate); 
    }
  }

  private function phoneURL($number) {
    if ($number) {
  
      // add the local area code if missing
      if (preg_match('/^\d{3}-\d{4}/', $number)) {
        $number = '617' . $number;
      }
  
      // remove all non-word characters from the number
      $number = preg_replace('/\W/', '', $number);
      return "tel:1$number";
    }
  }
  
  private function URLize($url) {
    $url = str_replace("http://http://", "http://", $url);
    
    
    if (strlen($url) && !preg_match('/^http\:\/\//', $url)) {
      $url = 'http://'.$url;
    }
    
    return $url;
  }
  
  // URL DEFINITIONS
  private function dayURL($day, $type, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('day', array(
      'time' => $day['time'],
      'type' => $type,
    ), $addBreadcrumb);
  }
  
  private function categoryDayURL($day, $categoryID, $name, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'time' => $day['time'],
      'id'   => $categoryID,
      'name' => $name, 
    ), $addBreadcrumb);
  }
  
  private function academicURL($year, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('academic', array(
      'year' => $year,
    ), $addBreadcrumb);
  }
  
  private function holidaysURL($year=NULL, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('holidays', array(
      'year' => isset($year) ? $year : $this->args['year'],
    ), $addBreadcrumb);
  }
  
  private function religiousURL($year=NULL, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('holidays', array(
      'page' => 'religious',
      'year' => isset($year) ? $year : $this->args['year'],
    ), $addBreadcrumb);
  }
  
  private function categorysURL($addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('categorys', array(), $addBreadcrumb);
  }
  
  private function categoryURL($category, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'id'   => is_array($category) ? $category['catid'] : $category->get_cat_id(),
      'name' => is_array($category) ? $category['name']  : $this->ucname($category->get_name()),
    ), $addBreadcrumb);
  }
  
  private function subCategorysURL($category, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('sub-categorys', array(
      'id' => is_array($category) ? $category['catid'] : $category->get_cat_id(),
    ), $addBreadcrumb);
  }
  
  private function detailURL($event, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'id' => $event->get_uid(),
    ), $addBreadcrumb);
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $today = $this->dayInfo(time());
      
        $this->assign('today',           $today);
        $this->assign('searchOptions',   $this->searchOptions);
        
        $this->assign('todaysEventsUrl', $this->dayURL($today, 'events'));
        $this->assign('holidaysUrl',     $this->holidaysURL($today['year']));
        $this->assign('categorysUrl',    $this->categorysURL());
        $this->assign('academicUrl',     $this->academicURL($today['year']));

        break;
        
      case 'day':  
        $this->setPageTitle("Browse by day");
        $this->setBreadcrumbTitle("Day");
      
        $type = $this->args['type'];
        $this->assign('Type', ucwords($type));

        $time = $this->args['time'];
        $next = $this->dayInfo($time, 1);
        $prev = $this->dayInfo($time, -1);
        $this->assign('current', $this->dayInfo($time));
        $this->assign('next',    $next);
        $this->assign('prev',    $prev);
        $this->assign('nextUrl', $this->dayURL($next, $type, false));
        $this->assign('prevUrl', $this->dayURL($prev, $type, false));
        
        // copied from api/HarvardCalendar.php
        $time = isset($this->args['time']) ? $this->args['time'] : time();
        $url = $GLOBALS['siteConfig']->getVar('HARVARD_EVENTS_ICS_BASE_URL').'?'.http_build_query(array(
          'startdate' => date('Ym', $time).'01',
          'months'    => 1,
        ));
        $iCalEvents = makeIcalDayEvents($url, date('Ymd', $time), NULL);
        
        $events = array();
        foreach($iCalEvents as $iCalEvent) {
          $subtitle = $this->timeText($iCalEvent);
          $briefLocation = $this->briefLocation($iCalEvent);
          if (isset($briefLocation)) {
            $subtitle .= " | $briefLocation";
          }
        
          $events[] = array(
            'url'      => $this->detailURL($iCalEvent),
            'title'    => $iCalEvent->get_summary(),
            'subtitle' => $subtitle,
          );
        }
        $this->assign('events', $events);        
        break;
        
      case 'detail':  
        $this->setPageTitle("Detail");

        // copied from api/HarvardCalendar.php
        $time = isset($this->args['time']) ? $this->args['time'] : time();
        $url = $GLOBALS['siteConfig']->getVar('HARVARD_EVENTS_ICS_BASE_URL').'?'.http_build_query(array(
          'startdate' => date('Ym', $time).'01',
          'months'    => 1,
        ));
        $event = getIcalEvent($url, date('Ymd', $time), $this->args['id']);
        
        // Time
        if ($event->get_end() - $event->get_start() == -1) {
          $timeString = date('g:i a', $event->get_start());
        } else {
          $timeString = $event->get_range()->format('g:i a');
        }
        
        $fields = $event->get_customFields();
        
        // Categories
        $categories = explode('\,',$fields['"Gazette Classification"']);
        $allCategories = Harvard_Calendar::get_categories($GLOBALS['siteConfig']->getVar('PATH_TO_EVENTS_CAT'));
        
        $eventCategories = array();
        foreach ($categories as $category) {
          $categoryObject = null;
          foreach ($allCategories as $aCategory) {
            // The strings from $harvard_cat may be null-terminated
            if (strcmp(trim($aCategory->get_name()), trim($category)) == 0) {
              $categoryObject = $aCategory;
              break;
            }
          }
          $eventCategories[] = array(
            'title' => $this->ucname($category), 
            'url'   => $this->categoryURL($categoryObject),
          );
        }
        
        // Description
        $description = str_replace('\,', ',', $event->get_description());
        
        foreach ($fields as $key => $value) {
          $skipKeys = array(
            '"Event Type"', 
            '"Gazette Classification"', 
            '"Contact Info"', 
            '"Location"', 
            '"Ticket Web Link"'
          );
          $strippedKey = strtr($key, '"', '');
          if (!in_array($key, $skipKeys) && strlen(strtr($key, '"', ''))) {
            if (strlen($description)) { $description .= '<br /><br />'; }
            
            $description .= $strippedKey.': '.str_replace('\,', ',', $value);
          }
        }
        
        $ticketsLink = $this->URLize($this->argVal($fields, '"Ticket Web Link"', ''));
        $url = $this->URLize($event->get_url());
        
        $contact = $this->argVal($fields, '"Contact Info"');
        $phone = isset($contact, $contact->phone[0]) ? $contact->phone[0] : '';
        $phoneUrl = $this->phoneURL($phone);
        $emailUrl = isset($contact, $contact->email[0]) ? $contact->email[0] : '';
        $email = $emailUrl;
        
        // Get Lat Long info
        //$jsonString = json_encode($fields);
        //$latLongArray = explode('<\/Latitude>',$jsonString);
        //$tempArray = explode(">", $latLongArray[0]);
        //$tempArrayCount = count($tempArray);
        //$tempArray2 = explode("<\/Longitude>", $latLongArray[1]);
        
        //$latitude = $tempArray[$tempArrayCount - 1];
        //$longitude = $tempArray2[0];
        
        $this->assign('summary',      $event->get_summary());
        $this->assign('date',         $event->get_range()->format('D M j, Y'));
        $this->assign('time',         $timeString);
        $this->assign('location',     $this->briefLocation($event));
        $this->assign('description',  $description);
        $this->assign('ticketsLink',  $ticketsLink);
        $this->assign('url',          $url);
        $this->assign('phone',        $phone);
        $this->assign('phoneUrl',     $phoneUrl);
        $this->assign('email',        $email);
        $this->assign('emailUrl',     $emailUrl);
        $this->assign('categories',   $eventCategories);
    }
  }
}
