<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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

    // we need to change the system timezone if it is different in order to generate the correct time stamp
    if ($tzid) {
        // retain the old timezone
        $old_timezone = date_default_timezone_get();
        if ($old_timezone != $tzid) {
            date_default_timezone_set($tzid);
        }
    }
    
    $this->set_start(mktime(0, 0, 0, date('m', $start), date('d', $start), date('Y', $start)));
    $this->set_end(mktime(23, 59, 59, date('m', $end), date('d', $end), date('Y', $end)));
    
    if ($tzid) {
        // restore the old timezone
        if ($old_timezone != $tzid) {
            date_default_timezone_set($old_timezone);
        }
    }
  }
}

