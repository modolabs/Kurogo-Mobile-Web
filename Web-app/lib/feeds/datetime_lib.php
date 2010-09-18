<?

/* time manipulating functions */

function day_of($time, $tzid=NULL) {
  $tztime = $time - ($time % 86400);
  if ($tzid !== NULL) {
    $timezone = new DateTimeZone($tzid);
    $transitions = $timezone->getTransitions();
    $is_dst = date('I', $tztime);
    $offset = 0;

    foreach ($transitions as $transition) {
      if ($transition['isdst'] == $is_dst) {
	$offset = $transition['offset'];
	break;
      }
    }

    $tztime = $tztime - $offset;
    if ($tztime > $time)
      $tztime -= 86400;
  }
  return $tztime;
}

function datetime2unix(DateTime $dtime) {
  $ver = phpversion();
  if (version_compare($ver, '5.3') == -1) {
    // DateTime::getTimeStamp not supported
    // in PHP versions before 5.3
    return mktime(
      $dtime->format('G'),
      $dtime->format('i'),
      $dtime->format('s'),
      $dtime->format('n'),
      $dtime->format('j'),
      $dtime->format('Y')
      );
  } else {
    return $dtime->getTimestamp();
  }
}

function increment_second($date, $numseconds=1) {
  return $date + $numseconds;
}

function increment_minute($date, $nummins=1) {
  return $date + 60 * $nummins;
}

function increment_hour($date, $numhours=1) {
  return $date + 3600 * $numhours;
}

function increment_day($date, $numdays=1) {
  return $date + 86400 * $numdays;
}

function increment_week($date, $numweeks=1) {
  return $date + 86400 * 7 * $numweeks;
}

function increment_year($date, $numyears=1) {
  return mktime(
   date('H', $date),
   date(trim('i', '0'), $date),
   date(trim('s', '0'), $date),
   date('n', $date),
   date('d', $date),
   date('Y', $date) + $numyears
   );
}

function increment_month($date, $nummonths=1) {
  $month = date('n', $date) + $nummonths;
  if ($month > 12) {
    $remainder = $month % 12;
    $year = date('Y', $date) + ($month - $remainder) / 12;
    $month = $remainder;
  } else {
    $year = date('Y', $date);
  }

  return mktime(
    date('H', $date),
    date(trim('i', '0'), $date),
    date(trim('s', '0'), $date),
    $month,
    date('d', $date),
    $year
    );
}

function seconds_since_midnight($humantime) {
  $add_secs = 0;
  preg_match('/(a|p)m?$/i', $humantime, $ampm);
  if (strtolower($ampm[1]) == 'p') {
    $add_secs = 3600 * 12;
  }

  if (preg_match('/(\d{1,2}):?(\d{2}):?(\d{2})[^\d]*$/', $humantime, $matches)) {
    return $matches[1] * 3600 + $matches[2] * 60 + $matches[3] + $add_secs;
  } elseif (preg_match('/(\d{1,2}):?(\d{2})[^\d]*$/', $humantime, $matches)) {
    return $matches[1] * 3600 + $matches[2] * 60 + $add_secs;
  }

}

?>
