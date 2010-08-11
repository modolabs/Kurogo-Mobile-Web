<?php

require_once 'harvard_ical_lib.php';

$categories = array();

class Harvard_Event_Category {

private static $name;
private static $id;
private static $urlLink;

public function set_name($nm) {

    $this->name = $nm;
    }

public function set_cat_id($uid) {

    $this->id = $uid;
    }

public function set_url($url) {

    $this->urlLink = $url;
    }

public function get_name() {

    return $this->name;
    }

public function get_cat_id() {

    return $this->id;
    }

public function get_url() {

    return $this->urlLink;
    }


}


class Harvard_Calendar {

    public function get_categories($pathToFile) {

        $filename = fopen($pathToFile, "r");

       
        while(!feof($filename)) {
            
            $event_cat = new Harvard_Event_Category();
            
            $cat_name = fgets($filename);
            $cat_uid = fgets($filename);
            $cat_url = fgets($filename);
            
            $event_cat->set_name(str_replace("\n", "", $cat_name));
            $event_cat->set_cat_id(str_replace("\n", "", $cat_uid));
            $event_cat->set_url(str_replace("\n", "", $cat_url));

            $categories[] = $event_cat;
        }
    
        return $categories;

        
    }

}

// TODO: use session variables on website whenever possible
// to avoid parsing the entire .ics file on each web call
function getIcalEvent($icsURL, $dateString, $eventId) {
	$fileN = TrumbaCache::retrieveData($icsURL, $dateString, NULL, NULL);
        $ical = new ICalendar($fileN);
error_log($eventId, 0);
	return $ical->get_event($eventId);
}

function makeIcalDayEvents($icsURL, $dateString, $category=NULL)
{
	$fileN = TrumbaCache::retrieveData($icsURL, $dateString, NULL, $category);
        $ical = new ICalendar($fileN);	

	$yr = (int)substr($dateString, 0, 4);
	$mth = (int)substr($dateString, 4, 2);
	$day = (int)substr($dateString, 6, 2);

	$time = mktime(0,0,0, $mth,$day,$yr); 

	return  $ical->get_day_events($time);

	
}


function makeIcalSearchEvents($icsURL, $terms)
{
	$time = time();
        $date = date('Ymd', $time);
        
        $fileN = TrumbaCache::retrieveData($icsURL, $date, $terms, NULL);
        $ical = new ICalendar($fileN);
	//$ical = new ICalendar($icsURL);

	return  $ical->search_events($terms, NULL);
}


/*function makeIcalAcademicEvents($academic_ics_url, $month, $year)
{
        $date = $year .$month .'01';
        $academic = 'academic';
        $fileN = TrumbaCache::retrieveData($academic_ics_url, $date, NULL, $academic);
        
        $ical = new ICalendar($fileN);

	return  $ical->search_events(NULL, NULL);
       
}*/

function makeIcalAcademicEvents($academic_ics_url, $startDate, $endDate)
{
        $date = $startDate . $endDate;
        $academic = 'academic';
        $fileN = TrumbaCache::retrieveData($academic_ics_url, $date, NULL, $academic);

        $ical = new ICalendar($fileN);

	return  $ical->search_events(NULL, NULL);

}



require_once "DiskCache.inc";

TrumbaCache::init();

class TrumbaCache {

  private static $ical = NULL;
  private static $diskCache = NULL;

  public function init() {
    if (self::$diskCache === NULL) {
      self::$diskCache = new DiskCache(TRUMBA_CALENDAR_CACHE_DIR, TRUMBA_CALENDAR_CACHE_LIFESPAN, TRUE);
      self::$diskCache->preserveFormat();
    }
  }

  public static function retrieveData($urlLink, $dateString, $searchField=NULL, $category=NULL) {

     $yr = substr($dateString, 0, 4);
     $mth = substr($dateString, 4, 2);


     if (($searchField == NULL) &&($category == NULL))
        $filename = $yr . $mth . '.ics';

     else if (($searchField != NULL) && ($category == NULL))
         $filename = $dateString .'search=' .$searchField .'.ics';

     else if (($searchField == NULL) && ($category = 'academic'))
         $filename = $dateString . 'ACADEMIC.ics';

     else if (($searchField == NULL) && ($category != NULL))
         $filename = $yr . $mth .'category=' .$category .'.ics';

     if (!self::$diskCache->isFresh($filename)) {

       self::$diskCache->write(file_get_contents($urlLink), $filename);
     }

     return self::$diskCache->getFullPath($filename);
  }

}

?>
