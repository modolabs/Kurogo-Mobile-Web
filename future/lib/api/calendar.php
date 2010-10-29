<?php

require_once(LIB_DIR . '/ICalendar.php');

$timezone = new DateTimeZone($GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE'));
$data = array();

$module = Module::factory('calendar', '', $_GET);

switch ($_REQUEST['command']) {

  case 'day':
    $type = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : 'events';
    $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : time();
    
    $feed = $module->getFeed($type);
    
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
    $type = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : 'events';
    $searchString = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
    
    $feed = $module->getFeed($type);
    
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
    $type = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : 'events';
    if ($id = $_REQUEST['id']) {
      $start = isset($_REQUEST['start']) ? $_REQUEST['start'] : time();
      $end = isset($_REQUEST['end']) ? $_REQUEST['end'] : $start + 86400;

      $events = array();
      
      if (strlen($id) > 0) {
        $feed = $module->getFeed($type);
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
    $type = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : 'events';
    $feed = $module->getFeed($type);
    $categoryObjects = $feed->getEventCategories();

    foreach ($categoryObjects as $categoryObject) {
 
      $name = ucwords($categoryObject->get_name());
      $catid = $categoryObject->get_cat_id();
      $url = $categoryObject->get_url();

      $catData = array(
        'name' => $name,
        'catid' => $catid,
        'url' => $url
      );

      $data[] = $catData;
    }
    break;

  case 'academic':
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

    $start = new DateTime( $year   ."0901", $timezone);        
    $end   = new DateTime(($year+1)."0831", $timezone);
    
    $feed = $module->getFeed('academic');
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
