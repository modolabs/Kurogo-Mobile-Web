<?
require_once("nusoap/nusoap.php");

class SoapClientWrapper {
  /* 
    This Wrapper automatically invokes ther generic error handler
    When the Soap Server returns an exception
  */ 
  private $php_client;

  public function __construct($url) {
    $this->php_client = new SoapClient($url);
  }

  public function __call($method, $args) {
    try {
      return call_user_func_array(array($this->php_client, $method), $args);  
    } catch(SoapFault $error) {
      throw new DataServerException("MIT Calendar SOAP server problem");
    }
  }

}

class MIT_Calendar {
  private static $url;
  private static $php_client;

  public static function init($url) {
    if(self::$url) {
      throw new Exception("MIT_Calendar already initialized");
    } else {
      self::$url = $url;
      self::$php_client = new SoapClientWrapper($url);
    }
  }

  public static function Categorys() {
    return self::$php_client->getCategoryList();
  }  

  public static function Category($id) {
    return self::$php_client->getCategory($id);
  }  

  public static function subCategorys($category) {
    return self::$php_client->getCategoryList($category->catid);
  }

  public static function TodaysExhibitsHeaders($date) {
    return self::HeadersByCatID(5, $date, $date);
  }

  public static function CategoryEventsHeaders($category, $start, $end=NULL) {
    if(!$end) {
      $end = $start;
    }
    return self::HeadersByCatID($category->catid, $start, $end);
  }
    
  public static function HeadersByCatID($catID, $start, $end) {
    $criteria = array(
      new SearchCriterion('start', $start),
      new SearchCriterion('end', $end),
      new SearchCriterion('catid', $catID) 
    );
    return self::$php_client->findEventsHeaders(SearchCriterion::forSOAP($criteria));
  }

  public static function hourSearch($date, $hour, $offsetHours = 1) {
    $time1 = strtotime("$date $hour:00:00");
    $time2 = $time1 + 60 * 60 * $offsetHours - 1;
    $start = date('Y/m/d H:00:00', $time1);
    $end = date('Y/m/d H:00:00', $time2);

    $criteria = array(
      new SearchCriterion('start', $start),
      new SearchCriterion('end', $end),
    );
    return self::$php_client->findEventsHeaders(SearchCriterion::forSOAP($criteria));
  }

  public static function fullTextSearch($text, $start, $end, $category=NULL) {
    $criteria = array(
      new SearchCriterion('start', $start),
      new SearchCriterion('end', $end),
      new SearchCriterion('fulltext')
    );
    
    foreach(explode(' ', $text) as $word) {
      if($word) {
        $criteria[2]->addValue($word);
      }
    }

    if($category) {
      $criteria[] = new SearchCriterion('catid', $category->catid);
    }
    return self::$php_client->findEventsHeaders(SearchCriterion::forSOAP($criteria));
  }

  public static function TodaysEventsHeaders($date) {
    // Get all today's events including exhibits
    $all_events = self::$php_client->getDayEventsHeaders($date);
    
    // Get only exhibits
    $exhibitions = self::TodaysExhibitsHeaders($date);
   
    $without_exhibitions = array(); //Initalize empty array
    
    //Remove the exhibitions form the list of today's events
    foreach($all_events as $event) {

      $found = false; //Initialize $found flag
      $id = $event->id;
      // Search through the list of exhibits to see if it matches the event id
      foreach($exhibitions as $exhibition) {
        if($exhibition->id == $id) {
          $found = true;
        }
      }

      if(!$found) {
        // Not an exhibition so add it to the list
        $without_exhibitions[] = $event;
      }
    }
    return $without_exhibitions;
  }     

  public static function getEvent($id) {
    return self::$php_client->getEvent($id);
  }    

  public static function standard_time($hour, $minute) {
    $end = ($hour < 12) ? 'am' : 'pm';
    $hour = $hour % 12;
    if($hour == 0) {
      $hour = 12;
    }
    if($minute < 10) {
      $minute = '0' . (int) $minute;
    }
    return "$hour:$minute$end";
  }

  public static function timeText($event) {
    if(self::compare($event->start, $event->end) == 1) {
      //the end can not be before the begginning
      $event['end'] = NULL;
    }

    if($event->start->hour === '00' &&
       $event->start->minute === '00' && (
        ($event->end->hour === '00' && $event->end->minute === '00')  ||
        ($event->end->hour === '23' && $event->end->minute === '59')) )  {
          $out .= 'All day';
    } else {
       if($event->start) {
         $out .= self::standard_time($event->start->hour, $event->start->minute);
       }
       if($event->end) {
         $out .= '-';
         $out .= self::standard_time($event->end->hour, $event->end->minute);
       }
    }
    return $out;
  }

  public static function compare($day1, $day2) {
    //compare the two different times
    foreach(array("year", "month", "day", "hour", "minute") as $key) {
      if($day->$key > $day2->$key) {
        return 1;
      }

      if($day2->$key > $day1->$key) {
        return -1;
      }
    }
    return 0;
  }

}

class SearchCriterion {
  
  private $field;
  private $value;

  public function __construct($field) {
    $this->field = $field;
    $args = func_get_args();
    $this->value = array_slice($args, 1);    
  }

  public function addValue($value) {
    $this->value[] = $value;
  }

  public static function forSOAP(array $criteria) {
    $soap_array = array();
    foreach($criteria as $criterian) {
      $soap_array[] = array(
	"field" => $criterian->field,
	"value" => $criterian->value,
      );
    }
    return $soap_array;
  }

  public static function fromArray($field, $values) {
    $new_obj = new self($field);
    $new_obj->value = $values;
    return $new_obj;
  }
}

MIT_Calendar::init("http://events.mit.edu/MITEventsFull.wsdl");

?>
