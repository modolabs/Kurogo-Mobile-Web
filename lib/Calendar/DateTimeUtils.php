<?php
/**
  * @package ExternalData
  * @subpackage Calendar
  */

interface CalendarInterface {
    public function getEventsInRange(TimeRange $range=null, $limit=null);
}

class DateFormatter
{
    const NO_STYLE=0;
    const SHORT_STYLE=1;
    const MEDIUM_STYLE=2;
    const LONG_STYLE=3;
    const FULL_STYLE=4;

    public static function formatDate($date, $dateStyle, $timeStyle) {
        $dateStyleConstant = self::getDateConstant($dateStyle);
        $timeStyleConstant = self::getTimeConstant($timeStyle);
        
        if ($date instanceOf DateTime) {
            $date = $date->format('U');
        }
        
        $string = '';
        if ($dateStyleConstant) {
            $string .= strftime(Kurogo::getLocalizedString($dateStyleConstant), $date);
            if ($timeStyleConstant) {
                $string .= " ";
            }
        }
        
        if ($timeStyleConstant) {
            // Work around lack of %P support in Mac OS X
            $format = Kurogo::getLocalizedString($timeStyleConstant);
            $lowercase = false;
            if (strpos($format, '%P') !== false) {
                $format = str_replace('%P', '%p', $format);
                $lowercase = true;
            }
            $formatted = strftime($format, $date);
            if ($lowercase) {
                $formatted = strtolower($formatted);
            }
            
            // Work around leading spaces that come from use of %l (but don't exist in date())
            if (strpos($format, '%l') !== false) {
                $formatted = trim($formatted);
            }
            
            $string .= $formatted;
        }
        
        return $string;
    }

    private static function getTimeConstant($timeStyle) {
        switch ($timeStyle)
        {
            case self::NO_STYLE:
                return '';
            case self::SHORT_STYLE:
                return 'SHORT_TIME_FORMAT';
            case self::MEDIUM_STYLE:
                return 'SHORT_TIME_FORMAT';
            case self::LONG_STYLE:
                return 'LONG_TIME_FORMAT';
            case self::FULL_STYLE:
                return 'FULL_TIME_FORMAT';
        }
    }
    
    private static function getDateConstant($dateStyle) {
        switch ($dateStyle)
        {
            case self::NO_STYLE:
                return '';
            case self::SHORT_STYLE:
                return 'SHORT_DATE_FORMAT';
            case self::MEDIUM_STYLE:
                return 'SHORT_DATE_FORMAT';
            case self::LONG_STYLE:
                return 'LONG_DATE_FORMAT';
            case self::FULL_STYLE:
                return 'FULL_DATE_FORMAT';
        }
    }

    public static function formatDateRange(TimeRange $range, $dateStyle, $timeStyle) {
        $string = '';
        if ($range instanceOf DayRange) {
            $timeStyle = self::NO_STYLE;
        }
        
        $string = self::formatDate($range->get_start(), $dateStyle, $timeStyle);
        if ($range->get_end() && $range->get_end() != $range->get_start()) {
            if (date('Ymd', $range->get_start()) == date('Ymd', $range->get_end())) {
                $dateStyle = self::NO_STYLE;
            }
            
            if ($dateStyle != self::NO_STYLE || $timeStyle != self::NO_STYLE) {
              $string .= ($dateStyle ? ' - ' : '-') .self::formatDate($range->get_end(), $dateStyle, $timeStyle);
            }
        }
        
        return $string;
    }
}

/**
  * TimeRange: class describing a time interval.
  * @package ExternalData
  * @subpackage Calendar
  */
class TimeRange {

  protected $start;
  protected $end;
  
    public function __toString()
    {
        return DateFormatter::formatDateRange($this, DateFormatter::MEDIUM_STYLE, DateFormatter::MEDIUM_STYLE);
    }

  protected static $precedence = Array(
    's', //second
    'i', //minute
    'G', 'g', 'H', 'h', //hour
    'A', 'a', //half day
    'D', 'l', 'N', 'w', //weekday
    'd', 'j', 'S', //monthday
    'z', //yearday
    'W', //week
    'F', 'M', 'm', 'n', 't', //month
    'Y', 'y', //year
    );

  public function get_start() {
    return $this->start;
  }

  public function set_start($start) {
    $this->start = $start;
  }

  public function get_end() {
    return $this->end;
  }
  
  public function set_icalendar_duration($duration) {
    $time = $this->start;
    
    if (preg_match('/^P([0-9]{1,2}[W])?([0-9]{1,3}[D])?([T]{0,1})?([0-9]{1,2}[H])?([0-9]{1,2}[M])?([0-9]{1,2}[S])?/', $duration, $bits)) {
        switch (count($bits)) {
            case 7:
                $time += intval($bits[6]); //seconds
            case 6:
                $time += (60*intval($bits[5])); //minutes
            case 5:
                $time += (3600*intval($bits[4])); // hours
            case 4:
            case 3:
                $time = strtotime("+" . intval($bits[2]) . " days", $time);
            case 2:
                $time = strtotime("+" . intval($bits[1]) . " weeks", $time);
        }
    }

    // if it ends on midnight and is a different day then use 11:59:59
    if ($this->start != $time && date('His', $time)=='000000') {
        $time -= 1;
    }
    
    $this->set_end($time);
  }

  public function set_end($end) {
    $this->end = $end;
  }

  public function format($format, $compress=TRUE) {
    if ($this->start == $this->end) {
      return date($format, $this->start);
    } else {
      $token_types = Array();
      // the letters in this regex are the parameters for date()
      preg_match_all('/([AaDdFGgHhijlMmNnoSstWwYyz])/', $format, $matches);
      foreach ($matches[1] as $match) {
    $prec = array_search($match, self::$precedence);
    $token_types[$prec] = $match;
      }
      krsort($token_types);

      $left = Array();  // string to show left of hyphen
      $right = Array(); // string to show right of hyphen

      if ($compress) {
    // iterate through tokens in decreasing grain size
    // until a tokens are different for start and end date
    while (list($prec, $token_type) = each ($token_types)) {
      $start = date($token_type, $this->start);
      $end = date($token_type, $this->end);
      $left[strpos($format, $token_type)] = $start;
      if ($start != $end) {
        $right[strpos($format, $token_type)] = $end;
        break;
      }
    }
      }

      // iterate through the rest of the array
      while (list($prec, $token_type) = each ($token_types)) {
    $start = date($token_type, $this->start);
    $end = date($token_type, $this->end);
    $left[strpos($format, $token_type)] = $start;
    $right[strpos($format, $token_type)] = $end;
      }

      $formatted = '';
      $buffer = '';
      for ($i = 0; $i < strlen($format); $i++) {
    if (array_key_exists($i, $left)) {
      if (array_key_exists($i, $right)) {
        $buffer .= $right[$i];
      } elseif ($buffer != '') {
        $formatted = rtrim($formatted, ' ,') . '-' . $buffer;
        //$formatted .= '-' . $buffer;
        $buffer = '';
      }
      $formatted .= $left[$i];
    } else {
      $formatted .= substr($format, $i, 1);
      if ($buffer != '') {
        $buffer .= substr($format, $i, 1);
      }
    }
      }
      if ($buffer != '') {
    $formatted = rtrim($formatted, ' ,') . '-' . $buffer;
    //$formatted .= '-' . $buffer;
      }

      return $formatted;
    }
  }

  /**
    * Returns whether any part of the 2 ranges overlap
    */
  public function overlaps(TimeRange $range) {
    if ($range->get_start() >= $this->end) return FALSE;
    elseif ($range->get_end() <= $this->start) return FALSE;
    else return TRUE;
  }

  /**
    * Returns whether the parameter is completely contained by the object 
    */
  public function contains(TimeRange $range) {
    if (!$this->overlaps($range)) return FALSE;
    elseif ($range->get_start() < $this->start) return FALSE;
    elseif ($range->get_end() > $this->end) return FALSE;
    else return TRUE;
  }

  /**
    * Returns whether the parameter completely surrounds the object 
    */
  public function contained_by(TimeRange $range) {
    if (!$this->overlaps($range)) return FALSE;
    elseif ($range->get_start() > $this->start) return FALSE;
    elseif ($range->get_end() < $this->end) return FALSE;
    else return TRUE;
  }

  public function contains_point($time) {
    return ($this->start <= $time && $this->end > $time);
  }

  public function __construct($start, $end=NULL) {
    if ($end === NULL) { // instantaneous event
      $end = $start;
    } elseif ($start > $end) {
      throw new KurogoDataException('argument 1 (start time) must be <= argument 2 (end time)');
    }

    $this->start = $start;
    $this->end = $end;
  }
}

/**
  * DayRange: child class of TimeRange describing a full day
  * @package ExternalData
  * @subpackage Calendar
  */
class DayRange extends TimeRange {
    public function __toString()
    {
        return DateFormatter::formatDateRange($this, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE);
    }
  public function __construct($start, $end=null, $tzid=NULL) {
    if (is_null($end)) {
        $end = $start;
    }
    // use mktime which uses system time zone if tzid is blank or is the same as system time zone TODO: what happens if it's different?
    if (!$tzid || $tzid == date_default_timezone_get()) {
        $this->start = mktime(0, 0, 0, date('m', $start), date('d', $start), date('Y', $start));
        $this->end = mktime(23, 59, 59, date('m', $end), date('d', $end), date('Y', $end));
    } else {
        throw new KurogoException("Timezone set ($tzid), but is not the same as system time zone (" . date_default_timezone_get() . "). This case needs to be handled");
    }
  }
}

