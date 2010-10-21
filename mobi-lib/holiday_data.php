<?php

function is_holiday($time) {
  if (date('md', $time) == '0101') {
    return TRUE;
  }

  global $holiday_data;

  // not sure why the holiday data are stored this way but whatever
  if (date('M', $time) == 'Jul')
    $datestr = date('F j', $time);
  else
    $datestr = date('M j', $time);

  $year = (int) date('Y', $time);

  $holidays_this_year = &$holiday_data[$year];
  // it doesn't matter that $holiday_data isn't an associative array
  return in_array($datestr, $holidays_this_year);
}

$holiday_data = array(
  2007 => array(
    "Jan 1", "New Year's Day",
    "Jan 15", "MLK, Jr. Day",
    "Feb 19", "Presidents' Day",
    "Apr 16", "Patriots' Day",
    "May 28", "Memorial Day",
    "July 4", "Independence Day",
    "Sep 3", "Labor Day",
    "Oct 8", "Columbus Day",
    "Nov 12", "Veteran's Day (Observed)",
    "Nov 22", "Thanksgiving Day",
    "Nov 23", "Day after Thanksgiving",
    "Dec 25", "Christmas Day",
  ),
  
  2008 => array(
    "Jan 1", "New Year's Day",
    "Jan 21", "MLK, Jr. Day",
    "Feb 18", "Presidents' Day",
    "Apr 21", "Patriots' Day",
    "May 26", "Memorial Day",
    "July 4", "Independence Day",
    "Sep 1", "Labor Day",
    "Oct 13", "Columbus Day",
    "Nov 11", "Veteran's Day",
    "Nov 27", "Thanksgiving",
    "Nov 28", "Day after Thanksgiving",
    "Dec 25", "Christmas",
  ),

  2009 => array(
    "Jan 1", "New Year's Day",
    "Jan 2", "Special Holiday Closing",
    "Jan 19", "MLK, Jr. Day",
    "Feb 16", "Presidents' Day",
    "Apr 20", "Patriots' Day",
    "May 25", "Memorial Day",
    "July 3", "Independence Day",
    "Sep 7", "Labor Day",
    "Oct 12", "Columbus Day",
    "Nov 11", "Veteran's Day",
    "Nov 26", "Thanksgiving",
    "Nov 27", "Day after Thanksgiving",
    "Dec 25", "Christmas",
  ),

  2010 => array(
    "May 31", "Memorial Day",
    "Sep 6", "Labor Day",
  ),
);

$religious_days = array(
  2007 => array(
    "Sep 12-14, Wed(sundown)-Fri" => "Rosh Hashanah",
    "Sep 13, Thursday" => "Ramadan begins (approximate)",
    "Sep 21-22, Fri(sundown)-Sat" => "Yom Kippur",
    "Sep 26-28, Wed(sundown)-Fri" => "Sukkot",
    "Oct 3-5, Wed(sundown)-Fri" => "Shemini Atzeret/Simchat Torah",
    "Oct 13, Saturday" => "Eidul-Fitr (Ramadan ends, approximate)",
    "Nov 1, Thursday" => "All Saints",
    "Dec 8, Saturday" => "Immaculate Conception",
    "Dec 20, Thursday" => "Eidul-Adha (approximate)",
    "Dec 25, Tuesday" => "Christmas",
  ),
  2008 => array(
    "Jan 6, Sun" => "Epiphany",
    "Feb 6, Wed" => "Ash Wednesday",
    "Mar 16, Sun" => "Palm Sunday (Western)",
    "Mar 20, Thu" => "Maundy Thursday (Western)",
    "Mar 21/23, Fri/Sun" => "Good Friday/Easter (Western)",
    "Apr 19-21, Sat(sundown)-Mon" => "Pesach (Passover) begins",
    "Apr 20, Sun" => "Palm Sunday (Orthodox)",
    "Apr 25-27, Fri(sundown)-Sun" => "Pesach (Passover) ends",
    "Apr 25/27, Fri/Sun" => "Holy Friday/Easter (Orthodox)",
    "May 1, Thu" => "Ascension",
    "Jun 8-10, Sun(sundown)-Tue" => "Shavuot",
  ),
);

?>