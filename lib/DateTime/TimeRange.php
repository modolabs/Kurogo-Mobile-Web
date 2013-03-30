<?php

/*
 * Copyright © 2009 - 2010 Massachusetts Institute of Technology
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

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
    
    if($this->start == $this->end) { // KGO-666
          if ($range->get_start() > $this->end) return FALSE;
          elseif ($range->get_end() < $this->start) return FALSE;
          else return TRUE;
      }else {
          if ($range->get_start() >= $this->end) return FALSE;
          elseif ($range->get_end() <= $this->start) return FALSE;
          else return TRUE;
      }
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

    $this->set_start($start);
    $this->set_end($end);
  }
}
