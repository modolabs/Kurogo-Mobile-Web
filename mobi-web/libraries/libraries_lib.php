<?

$custom_footer = <<<END
    <img src="Webkit/images/footer-libraries-3.png" alt="MIT Libraries" width="58" height="25"  class="leftfoot" />
    <img src="../Webkit/images/ist-logo-2.png" width="26" height="25" alt="IST" class="rightfoot" />
END;

function hours_today($library, $time=NULL) {
  if ($time === NULL) {
    $time = time();
  }
  $cal = LibraryInfo::get_calendar($library);
  $hours = $cal->get_day_events($time);
  $hour_strings = Array();
  foreach ($hours as $hour) {
    if (stripos($hour->get_summary(), 'closed') !== FALSE)
      continue;
    $hourstr = $hour->get_range()->format('g:ia');
    $hourstr = str_replace(':00', '', $hourstr);
    if (stripos($hour->get_summary(), 'by appointment') !== FALSE) {
      $hourstr .= ' (by appointment)';
    }
    $hour_strings[] = $hourstr;
  }

  if (count($hour_strings) > 0) {
    $hours_today = implode(', ', $hour_strings);
  } else {
    $hours_today = 'Closed';
  }

  return $hours_today;
}

function compare_weekdays($a, $b) {
  $weekdays = Array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
  $atokens = explode('-', $a);
  $btokens = explode('-', $b);
  $apos = array_search($atokens[0], $weekdays);
  $bpos = array_search($btokens[0], $weekdays);
  if ($apos == $bpos)
    return 0;
  // things not found should be sorted last
  if ($apos == -1 && $bpos != -1)
    return 1;
  if ($bpos == -1 && $apos != -1)
    return -1;
  return ($apos < $bpos) ? -1 : 1;
}

?>