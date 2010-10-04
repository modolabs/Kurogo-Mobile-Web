<?

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(SITE_LIB_DIR.'/harvard_dining.php');
require_once realpath(SITE_LIB_DIR.'/diningHrs.php');

  
class DiningModule extends Module {
  protected $id = 'dining';
  private $activeTab = '';
  
  public static $MEALS = array(
      "breakfast" => array("days" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday"),
      "brunch"    => array("days" => "Sunday"),
      "lunch"     => array("days" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday"),
      "dinner"    => array(),
      "bb"        => array("name" => "brain break", "days" => "Sunday,Monday,Tuesday,Wednesday,Thursday"),
  );

  public static function mealName($meal) {
    if(isset(self::$MEALS[$meal]['name'])) {
      return self::$MEALS[$meal]['name'];
    } else {
      return $meal;
    }
  }
  
  private function collectFoodByCategory($items) {
    $food_categories = array();

    foreach($items as $item) {
      if(!array_key_exists($item[category], $food_categories)) {
        $food_categories[$item[category]] = array($item);
      } else {
        $food_categories[$item[category]][] = $item;
      }
    }

    // reorder food categories by priority
    $orderedFoodCategories = array();
    $priorityFoodCategories = array(
      "Breakfast Entrees",
      "Today's Soup",
      "Brunch",
      "Entrees",
      "Accompaniments",
      "Desserts",
      "Pasta a la Carte",
      "Vegetables",
      "Starch & Potatoes",
    );

    foreach($priorityFoodCategories as $foodCategory) {
      if(isset($food_categories[$foodCategory])) {
        $orderedFoodCategories[$foodCategory] = $food_categories[$foodCategory];
      }
    }

    foreach($food_categories as $category => $food_items) {
      if(!isset($orderedFoodCategories[$category])) {
        $orderedFoodCategories[$category] = $food_items;
      }
    }
   
    return $orderedFoodCategories;
  }
  
  private function todaysMealsHours($diningHall, $day) {
    $mealsHours = array();
    foreach(self::$MEALS as $meal => $mealData) {
      if($this->isMealToday($mealData, $day)) {
        $mealHours = $diningHall->{$meal . "_hours"};
        if($mealHours != "NA") {
          $mealsHours[$meal] = $mealHours;
        }
      }
    }
    return $mealsHours;
  }
  
  private function diningHallStatuses($dining_halls) {
    $statuses = array();
    $minute_of_the_day = $this->minuteOfTheDay(time());
    $day = date("l");
    foreach ($dining_halls as $dining_hall) {

      // first search for currently open meal
      $open_meal = NULL;
      $open_meal_hours = NULL;
      foreach($this->todaysMealsHours($dining_hall, $day) as $meal => $meal_hours) {
        if($this->isMinuteDuringHours($minute_of_the_day, $meal_hours)) {
          $open_meal = $meal;
          $open_meal_hours = $meal_hours;
          break;
        }
      }

      // first search for currently open meal
      $next_meal = NULL;
      $next_meal_hours = NULL;
      foreach($this->todaysMealsHours($dining_hall, $day) as $meal => $meal_hours) {
        if($this->isMinuteBeforeHours($minute_of_the_day, $meal_hours)) {
          $next_meal = $meal;
          $next_meal_hours = $meal_hours;
          break;
        }
      }


      $status = array("name" => $dining_hall->name);


      if($open_meal) {
        if($this->isMealRestricted($dining_hall, $open_meal, $day)) {
          $status['status'] = "openrestrictions";
        } else {
          $status['status'] = "open";
        }
        $status['open_meal'] = $open_meal;
        $status['open_meal_hours'] = $open_meal_hours;

      } else {
        if($next_meal) {
          if($this->isMealRestricted($dining_hall, $next_meal, $day)) {
            $status['status'] = "closedrestrictions";
          }
        }

        if(!isset($status['status'])) {
          $status['status'] = "closed";
        }
      }

      if($next_meal) {
        $status['next_meal'] = $next_meal;
        $status['next_meal_hours'] = $next_meal_hours;
      }

      $status['summary'] = $this->statusSummary($status);

      $statuses[] = $status;
    }

    return $statuses;
  }
  
  private function isMealToday($meal, $day) {
    if(isset($meal["days"])) {
      // check id $day is list in $meal['days']
      return strpos($meal["days"], $day) !== false;
    } else {
      // if we dont have a days field, that means
      // the meal is every day
      return true;
    }
  }
  
  private function isMinuteDuringHours($minute, $meal_hours) {
    $limits = $this->stringToStartEndLimits($meal_hours);

    if(isset($limits["end"])) {
      return ($minute >= $limits["start"]) && ($minute < $limits["end"]);
    } else {
      return ($minute >= $limits["start"]);
    }
  }
  
  private function isMinuteBeforeHours($minute, $meal_hours) {
    $limits = $this->stringToStartEndLimits($meal_hours);
    return ($minute < $limits["start"]);
  }
  
  private function stringToStartEndLimits($meal_hours) {
    // look for $meal_hours formatted as "starting 10:00pm"
    if(strpos($meal_hours, "starting") === 0) {
      $parts = explode(" ", $meal_hours);
      return array("start" => $this->stringToMinutes($parts[1]));
    }

    // other possible formats "Noon-2:15pm", "11:30am-2:15pm", "7:30-10:00am"
    $parts = explode("-", $meal_hours);
    $start = $parts[0];
    $end = $parts[1];

    // parse second part (because its format is more standard
    // and info from the second part is used to parse the first part)
    $end_total_minutes = $this->stringToMinutes($end);
    $start_total_minutes = $this->stringToMinutes($start, $end_total_minutes);
    return array("start" => $start_total_minutes, "end" => $end_total_minutes);
  
  }
  
  /*
   * if $time_string does not specify "am" or "pm",
   * use the fact that $time_string has to be before $before_minute.
   * specifically assume $time_string corresponds to the latest time
   * that is still before $before_minute
   */
  private function stringToMinutes($time_string, $before_minute=NULL) {
    if($time_string == "Noon") {
      return 12 * 60;
    }

    preg_match('/(\d+)\:(\d+)(am|pm)?/', $time_string, $matches);
    $hour = intval($matches[1]);
    $minute = intval($matches[2]);

    $total_minutes = $hour * 60 + $minute;

    // check if am or pm is in $time_string
    if(count($matches) > 3) {
      $am_or_pm = $matches[3];
      if($am_or_pm == "pm") {
        $total_minutes += 12*60;
      }
    } else {
      // am or pm not specified, so we first try pm
      // then try am constrained by $before_minute
      // we try pm first (because we are trying to
      // minimize the difference between $time_string and $before_minute
      if($total_minutes+12*60 < $before_minute) {
        $total_minutes += 12*60;
      }
    }
    return $total_minutes;
  }
  
  
  private function minuteOfTheDay($time) {
    return intval(date("G", $time)) * 60 + intval(date("i", $time));
  }
  
  private function isMealRestricted($dining_hall, $meal, $day) {
    // only some type of meals have restrictions
    $restricted_meals = array("brunch", "lunch", "dinner");
    if(array_search($meal, $restricted_meals) === false) {
      // this is not a restricted meal
      return false;
    }

    $restricted_array = $dining_hall->{$meal . '_restrictions'};
    $restricted_days = $restricted_array[0]->days;

    foreach ($restricted_days as $restricted_day) {
      if($restricted_day == $day) {
        return true;
      }
    }

    return false;
  }
  
  private function statusSummary($statusDetails) {
      
    $status = $statusDetails['status'];
    $summary = "";

    if($status == 'open' || $status == 'openrestrictions') {
      // determine meal name
      $meal = $statusDetails['open_meal'];
      $meal_hours = $statusDetails['open_meal_hours'];
      $meal_name = self::mealName($meal);

      $summary = "Open for {$meal_name}";
      if($status == 'openrestrictions') {
        $summary .= " with interhouse restrictions";
      }
      $summary .= " ";
    } else {
      $summary = "Closed.";
      if(isset($statusDetails['next_meal'])) {
        $meal = $statusDetails['next_meal'];
        $meal_hours = $statusDetails['next_meal_hours'];
        $meal_name = self::mealName($meal);
        $summary .= " Next meal: " . ucwords($meal_name) .", ";
      } else {
        $meal_hours = NULL;
      }
    }

    if($meal_hours) {
        $parts = explode(" ", $meal_hours);
        if($parts[0] == "starting") {
            $hours_summary = $parts[1];
        } else {
            $hours_summary = $meal_hours;
        }

        $summary .= $hours_summary;
    }
    return $summary;
  }
  
  private function dayURL($time, $addBreadcrumb=true) {
    $args = array('time' => $time);
    if($this->activeTab) {
      $args['tab'] = $this->activeTab;
    }
    return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
  }  

  private function detailURL($statusDetails, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'location' => $statusDetails['name'],      
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
          "breakfast" => DINING_DATA::getDiningData($day, "BRK"),
          "lunch"     => DINING_DATA::getDiningData($day, "LUN"),
          "dinner"    => DINING_DATA::getDiningData($day, "DIN"),
        );
        
        foreach($foodItems as $meal => $items) {
          $foodItems[$meal] = $this->collectFoodByCategory($items);
        }
        
        $hour = intval(date('G'));
        if($hour < 12) {
            $currentMeal = "breakfast";
        } else if ($hour < 15) {
            $currentMeal = "lunch";
        } else {
            $currentMeal = "dinner";
        }
        
        $hours = DINING_HOURS::getDiningHours();
        $diningStatuses = $this->diningHallStatuses($hours);

        error_log(print_r($hours, true));
        error_log(print_r($diningStatuses, true));
        
        $this->assign('currentMeal',    $currentMeal);
        $this->assign('foodItems',      $foodItems);
        $this->assign('hours',          $hours);
        $this->assign('diningStatuses', $diningStatuses);

        $this->addInlineJavascriptFooter("showTab('{$currentMeal}tab');");

        break;
        
      case 'detail':
        break;
    }
  }
}
