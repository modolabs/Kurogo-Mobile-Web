<?php

require_once realpath(LIB_DIR.'/Module.php');

if (realpath(SITE_LIB_DIR.'/HarvardDining.php')) {
    require_once realpath(SITE_LIB_DIR.'/HarvardDining.php');
    require_once realpath(SITE_LIB_DIR.'/HarvardDiningHalls.php');
}

if (!class_exists('DiningData')) {
    throw new Exception("Dining not configured");
}

  
class DiningModule extends Module {
  protected $id = 'dining';
  
  private function dayURL($time, $addBreadcrumb=true) {
    $args = array('time' => $time);
    if(isset($this->args['tab'])) {
      $args['tab'] = $this->args['tab'];
    }
    return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
  }  

  private function detailURL($diningStatus, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'location' => $diningStatus['name'],      
    ), $addBreadcrumb);
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $time  = isset($this->args['time']) ? $this->args['time'] : time();
        $today = time();
        $next  = $time + 24*60*60;
        $prev  = $time - 24*60*60;
        
        $this->assign('current', $time);

        // limit how far into the past/future we can see
        if ((($next - $today)/(24*60*60)) < 7) {
          $this->assign('next', array(
            'timestamp' => $next,
            'url'       => $this->dayURL($next, false),
          ));
        }
        if ((($today - $prev)/(24*60*60)) < 7) {
          $this->assign('prev', array(
            'timestamp' => $prev,
            'url'       => $this->dayURL($prev, false),
          ));
        }
        
        $day = date('Y-m-d', $time);
        $foodItems = array(
          'breakfast' => DiningData::getDiningData($day, 'BRK'),
          'lunch'     => DiningData::getDiningData($day, 'LUN'),
          'dinner'    => DiningData::getDiningData($day, 'DIN'),
        );
        
        $hour = intval(date('G'));
        if($hour < 12) {
            $currentMeal = 'breakfast';
        } else if ($hour < 15) {
            $currentMeal = 'lunch';
        } else {
            $currentMeal = 'dinner';
        }
        
        $diningStatuses = DiningHalls::getDiningHallStatuses();
        foreach ($diningStatuses as &$diningStatus) {
          $diningStatus['url'] = $this->detailURL($diningStatus);
        }
  
        //error_log(print_r($foodItems, true));
        //error_log(print_r($diningHours, true));
        //error_log(print_r($diningStatuses, true));
        
        $this->assign('currentMeal',    $currentMeal);
        $this->assign('foodItems',      $foodItems);
        $this->assign('diningStatuses', $diningStatuses);
        
        $tabs = array_keys($foodItems);
        $tabs[] = 'location';
        
        $this->enableTabs($tabs, $currentMeal);
        break;
        
      case 'detail':
        $diningHall = $this->args['location'];

        $allHours = DiningHalls::getDiningHallHours();
        $theseHours = null;
        
        foreach ($allHours as $hours) {
          if($hours->name == $diningHall) {
            $theseHours = $hours;
            break;
          }
        }
        
        $diningHallHours = array(
          'breakfast'   => $theseHours->breakfast_hours,
          'lunch'       => $theseHours->lunch_hours,
          'dinner'      => $theseHours->dinner_hours,
          'brain break' => $theseHours->bb_hours,
          'brunch'      => $theseHours->brunch_hours,
        );

        foreach ($diningHallHours as &$hour) {
          if($hour == 'NA') {
             $hour = 'Closed';
          }
        }
        
        if ($diningHallHours['brain break'] != 'Closed') {
          $diningHallHours['brain break'] = 'Sunday-Thursday '.
            preg_replace(';starting(\s+at|);', 'starting at', $diningHallHours['brain break']);
        }
        
        if ($diningHallHours['brunch'] != 'Closed') {
          $diningHallHours['brunch'] = "Sunday {$diningHallHours['brunch']}";
        }
        
        $diningHallRestrictions = array(
          'lunch'  => $theseHours->lunch_restrictions[0]->message,
          'dinner' => $theseHours->dinner_restrictions[0]->message,
          'brunch' => $theseHours->brunch_restrictions[0]->message,
        );
        
        foreach ($diningHallRestrictions as &$restriction) {
          if($restriction == 'NA') {
            $restriction = 'None';
          }
        }
        
        // super special cases
        if ($diningHall == 'Hillel') {
          $diningHallHours['lunch'] = 'Saturday only';
          $diningHallHours['dinner'] .= ' (Sunday-Thursday)';
        }
        
        if ($diningHall == 'Fly-By') {
          $diningHallHours['lunch'] .= ' (Monday-Friday)';
        }

        $this->assign('diningHall',             $diningHall);
        $this->assign('diningHallHours',        $diningHallHours);
        $this->assign('diningHallRestrictions', $diningHallRestrictions);
        break;
    }
  }
}
