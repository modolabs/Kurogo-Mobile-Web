<?php
/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

define('PATH_TO_DINING_HRS', DATA_DIR.'/DiningHours');

class MealRestrictions {
    
  public $days = array();
  public $time;
  public $message;

  public function get_days() {
    return $this->days;
  }

  public function get_time() {
    return $this->time;
  }

  public function get_message() {
    return $this->message;
  }
}

class DiningHall {

  public $name;
  public $breakfast_hours;
  public $lunch_hours;
  public $dinner_hours;
  public $bb_hours;
  public $brunch_hours;
  public $lunch_restrictions = array();
  public $dinner_restrictions = array();
  public $brunch_restrictions = array();

  public function __construct($n, $b, $l, $d, $bb, $br) {
    $this->name = $n;
    $this->breakfast_hours = $b;
    $this->lunch_hours = $l;
    $this->dinner_hours = $d;
    $this->bb_hours = $bb;
    $this->brunch_hours = $br;
  }

  public function process_restrictions($line) {

    $restrictions = preg_split("/;/", $line);

    $meal_restrictions = array();

    foreach ($restrictions as $rest) {

      $components = preg_split('/,/', $rest);

      $days = preg_split("/\//", $components[0]);

      $time = $components[1];
      $msg = str_replace("$", ",", $components[2]);

      $meal_rest = new MealRestrictions();
      $meal_rest->days = $days;
      $meal_rest->time = $time;
      $meal_rest->message = $msg;

      $meal_restrictions[] = $meal_rest;
    }


    foreach($meal_restrictions as $lr){

      //print_r($lr->get_days());
      //printf("%s\n", $lr->get_time());
      //printf("%s\n", $lr->get_message());
      //printf("\n");
    }

    return $meal_restrictions;
  }
}


class DiningHalls {

  public static function getDiningHallHours() {
    $filename = PATH_TO_DINING_HRS;
    $handle = fopen($filename, 'r');

    $diningHalls = array();

    while(!feof($handle)) {

      $line1 = str_replace("\n", "", fgets($handle)); // name
      $line2 = str_replace("\n", "", fgets($handle)); // breakfast hrs
      $line3 = str_replace("\n", "", fgets($handle)); // lunch hrs
      $line4 = str_replace("\n", "", fgets($handle)); // dinner hrs
      $line5 = str_replace("\n", "", fgets($handle)); // brain-break hrs
      $line6 = str_replace("\n", "", fgets($handle)); // nrunch hrs

      $hall = new DiningHall($line1, $line2, $line3, $line4, $line5, $line6);
      //printf("%s\n", $line1);
      //printf("%s\n", $line2);
      //printf("%s\n", $line3);
      //printf("%s\n", $line4);
      //printf("%s\n", $line5);
      //printf("%s\n", $line6);
      //printf("\n");


      $line7 = str_replace("\n", "", fgets($handle));
      $hall->lunch_restrictions = $hall->process_restrictions($line7);

      $line8 = str_replace("\n", "", fgets($handle));
      $hall->dinner_restrictions = $hall->process_restrictions($line8);

      $line9 = str_replace("\n", "", fgets($handle));
      $hall->brunch_restrictions = $hall->process_restrictions($line9);

      $line10 = str_replace("\n", "", fgets($handle));  // empty line

      $diningHalls[] = $hall;
    }
    fclose($handle);

    return $diningHalls;
  }
  
  public static function getDiningHallStatuses() {
    $diningHalls = self::getDiningHallHours();
  
    $statuses = array();
    $minuteOfTheDay = self::minuteOfTheDay(time());
    $day = date("l");
    foreach ($diningHalls as $diningHall) {

      // first search for currently open meal
      $openMeal = NULL;
      $openMealHours = NULL;
      foreach(self::todaysMealsHours($diningHall, $day) as $meal => $mealHours) {
        if(self::isMinuteDuringHours($minuteOfTheDay, $mealHours)) {
          $openMeal = $meal;
          $openMealHours = $mealHours;
          break;
        }
      }

      // first search for currently open meal
      $nextMeal = NULL;
      $nextMealHours = NULL;
      foreach(self::todaysMealsHours($diningHall, $day) as $meal => $mealHours) {
        if(self::isMinuteBeforeHours($minuteOfTheDay, $mealHours)) {
          $nextMeal = $meal;
          $nextMealHours = $mealHours;
          break;
        }
      }

      $status = array("name" => $diningHall->name);

      if($openMeal) {
        if(self::isMealRestricted($diningHall, $openMeal, $day)) {
          $status['status'] = "openrestrictions";
        } else {
          $status['status'] = "open";
        }
        $status['openMeal'] = $openMeal;
        $status['openMealHours'] = $openMealHours;

      } else {
        if($nextMeal) {
          if(self::isMealRestricted($diningHall, $nextMeal, $day)) {
            $status['status'] = "closedrestrictions";
          }
        }

        if(!isset($status['status'])) {
          $status['status'] = "closed";
        }
      }

      if($nextMeal) {
        $status['nextMeal'] = $nextMeal;
        $status['nextMealHours'] = $nextMealHours;
      }

      $status['summary'] = self::statusSummary($status);

      $statuses[] = $status;
    }

    return $statuses;
  }
  
  private static function todaysMealsHours($diningHall, $day) {
    $mealsHours = array();
    foreach(HarvardDining::$meals as $meal => $mealData) {
      if(self::isMealToday($mealData, $day)) {
        $mealHours = $diningHall->{$meal . "_hours"};
        if($mealHours != "NA") {
          $mealsHours[$meal] = $mealHours;
        }
      }
    }
    return $mealsHours;
  }
  
  private static function isMealToday($meal, $day) {
    if(isset($meal["days"])) {
      // check id $day is list in $meal['days']
      return strpos($meal["days"], $day) !== false;
    } else {
      // if we dont have a days field, that means
      // the meal is every day
      return true;
    }
  }
  
  private static function isMinuteDuringHours($minute, $mealHours) {
    $limits = self::stringToStartEndLimits($mealHours);

    if(isset($limits["end"])) {
      return ($minute >= $limits["start"]) && ($minute < $limits["end"]);
    } else {
      return ($minute >= $limits["start"]);
    }
  }
  
  private static function isMinuteBeforeHours($minute, $mealHours) {
    $limits = self::stringToStartEndLimits($mealHours);
    return ($minute < $limits["start"]);
  }
  
  private static function stringToStartEndLimits($mealHours) {
    // look for $mealHours formatted as "starting 10:00pm"
    if(strpos($mealHours, "starting") === 0) {
      $parts = explode(" ", $mealHours);
      return array("start" => self::stringToMinutes($parts[1]));
    }

    // other possible formats "Noon-2:15pm", "11:30am-2:15pm", "7:30-10:00am"
    $parts = explode("-", $mealHours);
    $start = $parts[0];
    $end = $parts[1];

    // parse second part (because its format is more standard
    // and info from the second part is used to parse the first part)
    $endTotalMinutes = self::stringToMinutes($end);
    $startTotalMinutes = self::stringToMinutes($start, $endTotalMinutes);
    return array("start" => $startTotalMinutes, "end" => $endTotalMinutes);
  
  }
  
  /*
   * if $time_string does not specify "am" or "pm",
   * use the fact that $time_string has to be before $before_minute.
   * specifically assume $time_string corresponds to the latest time
   * that is still before $before_minute
   */
  private static function stringToMinutes($time_string, $before_minute=NULL) {
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
  
  
  private static function minuteOfTheDay($time) {
    return intval(date("G", $time)) * 60 + intval(date("i", $time));
  }
  
  private static function isMealRestricted($diningHall, $meal, $day) {
    // only some type of meals have restrictions
    $restricted_meals = array("brunch", "lunch", "dinner");
    if(array_search($meal, $restricted_meals) === false) {
      // this is not a restricted meal
      return false;
    }

    $restrictedArray = $diningHall->{$meal . '_restrictions'};
    $restricted_days = $restrictedArray[0]->days;

    foreach ($restricted_days as $restricted_day) {
      if($restricted_day == $day) {
        return true;
      }
    }

    return false;
  }
  
  private static function statusSummary($statusDetails) {
      
    $status = $statusDetails['status'];
    $summary = "";

    if($status == 'open' || $status == 'openrestrictions') {
      // determine meal name
      $meal = $statusDetails['openMeal'];
      $mealHours = $statusDetails['openMealHours'];
      $mealName = HarvardDining::mealName($meal);

      $summary = "Open for {$mealName}";
      if($status == 'openrestrictions') {
        $summary .= " with interhouse restrictions";
      }
      $summary .= " ";
    } else {
      $summary = "Closed.";
      if(isset($statusDetails['nextMeal'])) {
        $meal = $statusDetails['nextMeal'];
        $mealHours = $statusDetails['nextMealHours'];
        $mealName = HarvardDining::mealName($meal);
        $summary .= " Next meal: " . ucwords($mealName) .", ";
      } else {
        $mealHours = NULL;
      }
    }

    if($mealHours) {
        $parts = explode(" ", $mealHours);
        if($parts[0] == "starting") {
            $hoursSummary = $parts[1];
        } else {
            $hoursSummary = $mealHours;
        }

        $summary .= $hoursSummary;
    }
    return $summary;
  }
}
