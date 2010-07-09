<?

require_once LIBDIR . '/mit_ical_lib.php';
require_once LIBDIR . '/LibraryInfo.php';
require "libraries_lib.inc";

$library = urldecode($_GET['library']);
LibraryInfo::cache_ical($library);
$attribs = LibraryInfo::get_library_info($library);
$today = time();
$hours_today = hours_today($library, $today);

// search 6 months into the future
$search_range = new TimeRange($today, increment_month($today, 6));

$cal = new ICalendar(LibraryInfo::ical_url($library));
$nearby_events = $cal->search_by_range($search_range);
/* stuff in term_data array:
 * 'hours': a list like Array('mon-wed' => '8a-5p', 'thu-fri' => '9a-6p')
 * 'range': e.g. sep 14-dec 22
 */
$term_data = Array();
$closings = Array();
foreach ($nearby_events as $event) {
  $term = extract_term_name($event->get_summary());

  if ($term) {
    // cluster all events with the same term heading
    if (!array_key_exists($term, $term_data)) {
      $term_data[$term]['range'] = $event->get_range();
      $term_data[$term]['hours'] = Array();
    } else {
      $new_start = $event->get_range()->get_start();
      if ($new_start < $term_data[$term]['range']->get_start())
	$term_data[$term]['range']->set_start($new_start);
      $new_end = $event->get_range()->get_end();
      if ($new_end > $term_data[$term]['range']->get_end())
	$term_data[$term]['range']->set_end($new_end);
    }

    // create hour string, e.g. 10am-5pm
    $hourstr = $event->get_range()->format('g:ia', FALSE);
    $hourstr = str_replace(':00', '', $hourstr);
    if (stripos($event->get_summary(), 'by appointment') !== FALSE) {
      $hourstr .= ' (by appointment)';
    }

    // operate on recurring events
    if ($event->is_recurring()) {
      // figure out how long the term lasts
      // by seeing if there are more days in the term
      // every time we come across an event with the same term name
      if ($event->get_start() < $term_data[$term]['range']->get_start()) {
	$term_data[$term]['range']->set_start($event->get_start());
      }
      if ($event->get_end() > $term_data[$term]['range']->get_end()) {
	$term_data[$term]['range']->set_end($event->get_end());
      }

      // sort weekdays so we can make useful ranges
      $weekdays = Array();
      foreach ($event->get_occurrences() as $occurrence) {
	$weekdays[] = date('l', $occurrence);
      }
      usort($weekdays, 'compare_weekdays');
      if ($weekdays[0] == end($weekdays)) {
	$title = $weekdays[0];
      } else {
	$title = $weekdays[0] . '-' . end($weekdays);
      }
    }

    $term_data[$term]['hours'][$title] = $hourstr;

  } elseif (stripos($event->get_summary(), 'closed') !== FALSE) {

    // date range of closing
    $hourstr = $event->get_range()->format('M j');

    // look for reason for closing, if any
    preg_match('/Closed (for |)\(?([^\)]+)\)?/i', $event->get_summary(), $matches);
    if ($matches[2]) {
      $hourstr .= ' (' . $matches[2] . ')';
    }

    $closings[$event->get_start()] = $hourstr;
  } elseif (stripos($event->get_summary(), 'by appointment') !== FALSE) {
    // annoying use case -- they don't put in term names for 'by appointment'

    // create hour string
    $hourstr = $event->get_range()->format('g:ia');
    $hourstr = str_replace(':00', '', $hourstr) . ' (by appointment)';

    // sort weekdays -- if they didn't make it recurring we'll get a warning
    $weekdays = Array();
    foreach ($event->get_occurrences() as $occurrence) {
      $weekdays[] = date('l', $occurrence);
    }
    usort($weekdays, 'compare_weekdays');
    if ($weekdays[0] == end($weekdays)) {
      $title = $weekdays[0];
    } else {
      $title = $weekdays[0] . '-' . end($weekdays);
    }

    $term_data[] = Array('hours' => Array($title => $hourstr),
			 'range' => $event->get_series_range());
  }
}

if (isset($_GET['termday'])) {
  $termday = $_GET['termday'];
} else {
  $termday = $today;
}

// split all term data relative to today
$terms_before_today = Array();
$term_today = Array();
$terms_after_today = Array();
foreach ($term_data as $term => $data) {
  uksort($data['hours'], 'compare_weekdays');

  // figure out which closing dates belong to this term
  foreach ($closings as $time => $hourstr) {
    if ($data['range']->overlaps(new TimeRange($time))) {
      if (!array_key_exists('Closed', $data['hours'])) {
	$data['hours']['Closed'] = $hourstr;
      } else {
	$data['hours']['Closed'] .= ', ' . $hourstr;
      }
    }
  }

  // now we no longer need to index by term name
  // since we're done clustering
  $data['name'] = $term;

  $data_start = $data['range']->get_start();
  $data_end = $data['range']->get_end();

  if ($data_start > $termday) {
    $data['termday'] = $data_start;
    if (is_int($term))
      $data['name'] = 'Next term'; // for libraries w/o term name
    $terms_after_today[] = $data;
  } elseif ($data_end < $termday) {
    $data['termday'] = $data_start + 1;
    if (is_int($term))
      $data['name'] = 'Previous term'; // for libraries w/o term name
    $terms_before_today[] = $data;
  } else {
    if (is_int($term))
      $data['name'] = ''; // for libraries w/o term name

    // if this interval is within another, e.g. spring break
    // the outer interval should be both previous and next
    if (!$term_today) {
      $term_today = $data;
    } else {
      if ($data_start < $term_today['range']->get_start()) {
	$data['termday'] = $data_start;
	$terms_before_today[] = $data;

	if ($data_end > $term_today['range']->get_end()) {
	  $data['termday'] = $term_today['range']->get_end() + 1;
	  $terms_after_today[] = $data;
	} else {
	  // this should never happen as long as ranges don't overlap
	}

      } else {
	$term_today['termday'] = $term_today['range']->get_start();
	$terms_before_today[] = $term_today;

	$term_today['termday'] = $term_today['range']->get_end();
	$terms_after_today[] = $term_today;

	$term_today = $data;
      }

    }
  }
}

usort($terms_after_today, 'compare_terms');
usort($terms_before_today, 'rcompare_terms');

$prev_next_links = Array();

if ($terms_before_today) {
  $data = $terms_before_today[0];
  $prev_next_links[] = '<a href="location-detail.php?library=' . $library . '&termday=' . $data['termday'] . '">&lt;' . $data['name'] . ' hours</a>';
}

if ($terms_after_today) {
  $data = $terms_after_today[0];
  $prev_next_links[] = '<a href="location-detail.php?library=' . $library . '&termday=' . $data['termday'] . '">' . $data['name'] . ' hours &gt;</a>';
}

require "$page->branch/location-detail.html";

$page->output();

function extract_term_name($str) {
  preg_match('/(Spring Vacation|Fall|Spring|Summer|IAP|Winter Vacation)/i', $str, $matches);
  $term = $matches[1];
  // sometimes they put special cases in parens after the term name
  if ($term && strpos($str, '(') !== FALSE) {
    preg_match('/\((.+)\)/', $str, $matches);
    return "$term ({$matches[1]})";
  }
  return $term;
}

function compare_terms($term_data_a, $term_data_b) {
  $a_start = $term_data_a['range']->get_start();
  $b_start = $term_data_b['range']->get_start();
  if ($a_start == $b_start)
    return 0;
  return ($a_start < $b_start) ? -1 : 1;
}

function rcompare_terms($a, $b) {
  return -1 * compare_terms($a, $b);
}

?>
