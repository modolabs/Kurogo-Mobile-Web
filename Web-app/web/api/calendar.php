<?php

$controllerClass = $GLOBALS['siteConfig']->getVar('CALENDAR_CONTROLLER_CLASS');
$parserClass     = $GLOBALS['siteConfig']->getVar('CALENDAR_PARSER_CLASS');
$eventClass      = $GLOBALS['siteConfig']->getVar('CALENDAR_EVENT_CLASS');
$baseURL         = $GLOBALS['siteConfig']->getVar('CALENDAR_ICS_URL');

$timezone = new DateTimeZone($GLOBALS['siteConfig']->getVar('SITE_TIMEZONE'));
$data = array();

switch ($_REQUEST['command']) {
  case 'day':

    $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'events';
    $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
    
    $feed = new $controllerClass($baseURL, new $parserClass);
    $feed->setObjectClass('event', $eventClass);
    
    $start = new DateTime(date('Y-m-d H:i:s', $time), $timezone);
    $start->setTime(0,0,0);
    $end = clone $start;
    $end->setTime(23,59,59);

    $feed->setStartDate($start);
    $feed->setEndDate($end);
    $iCalEvents = $feed->items();
            
    foreach($iCalEvents as $iCalEvent) {
        $data[] = $iCalEvent->apiArray();
    }
   break;


   case 'search':
     $searchString = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';

      $feed = new $controllerClass($baseURL, new $parserClass);
      $feed->setObjectClass('event', $eventClass);
      
      $start = new DateTime(null, $timezone);
      $start->setTime(0,0,0);
      $feed->setStartDate($start);
      $feed->setDuration(7,'day');
      $feed->addFilter('search', $searchString);
      $iCalEvents = $feed->items();

        foreach($iCalEvents as $iCalEvent) {
            $data[] = $iCalEvent->apiArray();
        }
      
      $data = array('events'=>$data);

   break;

   case 'category':
    if ($id = $_REQUEST['id']) {
     $start = isset($_REQUEST['start']) ? $_REQUEST['start'] : time();
     $end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $start + 86400;

        $events = array();
        
        if (strlen($id) > 0) {
            $feed = new $controllerClass($baseURL, new $parserClass);
            $feed->setObjectClass('event', $eventClass);
            
            $start = new DateTime(date('Y-m-d H:i:s', $start), $timezone);
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
    
            $feed->setStartDate($start);
            $feed->setEndDate($end);
            $feed->addFilter('category', $id);
            $events = $feed->items();
            foreach ($events as $event) {
                $data[] = $event->apiArray();
            }
        }

    }

   break;

   case 'categories':
    $categories = call_user_func(array($eventClass, 'get_all_categories'));

       foreach ($categories as $categoryObject) {
     
           $name = ucwords($categoryObject->get_name());
           $catid = $categoryObject->get_cat_id();
           $url = $categoryObject->get_url();

           $catData = array('name' => $name,
		      'catid' => $catid,
                      'url' => $url);

           $data[] = $catData;
       }
   break;

   case 'academic':
        $baseURL = $GLOBALS['siteConfig']->getVar('CALENDAR_ACADEMIC_ICS_URL');
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

        $start = new DateTime( $year   ."0901", $timezone);        
        $end   = new DateTime(($year+1)."0831", $timezone);
        
        $feed = new $controllerClass($baseURL, new $parserClass);
        $feed->setObjectClass('event', $eventClass);
        $feed->setStartDate($start);
        $feed->setEndDate($end);
        $iCalEvents = $feed->items();

        foreach($iCalEvents as $event) {
            $data[] = $event->apiArray();
        }

       	break;

   default:
       break;

 
}

echo json_encode($data);
