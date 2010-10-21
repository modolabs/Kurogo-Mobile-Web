<?

require LIBDIR . 'StellarData.php';
if (isset($_REQUEST['command'])) {

  $data = Array();

  switch ($_REQUEST['command']) {
  case 'courses':
    $courses = StellarData::get_courses();
    foreach ($courses as $short => $course) {
      $is_course = ($course['is_course']) ? 1 : 0;
      $data[] = Array(
        'short' => sprintf('%s', $short),
	'name' => $course['name'],
	'is_course' => $is_course,
	);
    }
    break;

  case 'subjectList':
    $courseId = urldecode($_REQUEST['id']);
    $subjectList = StellarData::get_subjects_with_xref($courseId);
    foreach ($subjectList as $subjectId => $info) {
      $info['term'] = StellarData::get_term();
      $data[] = $info;
    }
    if(isset($_REQUEST['checksum'])) {
      $checksum = md5(json_encode($data));
      if(isset($_REQUEST['full'])) {
        $data = array('checksum' => $checksum, 'classes' => $data);
      } else {
        $data = array('checksum' => $checksum);
      }
    }
    break;

  case 'subjectInfo':
    $subjectId = urldecode($_REQUEST['id']);
    $data = StellarData::get_subject_info($subjectId);
    if($data) {
      $data['announcements'] = StellarData::get_announcements($subjectId);
      
      // some classes dont have stellar announcements
      if($data['announcements'] === False) {
	unset($data['announcements']);
      }

      $data['term'] = StellarData::get_term();
    } else {
      $data = array('error' => 'SubjectNotFound', 'message' => 'Stellar could not find this subject'); 
    }
    break;

  case 'search':
    $query = urldecode($_REQUEST['query']);
    $data = StellarData::search_subjects($query);
    $term = StellarData::get_term();
    foreach($data as $index => $value) {
      $data[$index]['term'] = $term;
    }
    break;

  case 'term':
    $data = array('term' => StellarData::get_term());
    break;

  case 'myStellar':
    require_once 'push/apns_lib.php';
    $pass_key = intval($_REQUEST['pass_key']);
    $device_id = intval($_REQUEST['device_id']);       
    $device_type = $_REQUEST['device_type'];       
    $subject = $_REQUEST['subject'];
    $term = $_REQUEST['term'];

    if($device_type == 'apple') {
      if(!APNS_DB::verify_device_id($device_id, $pass_key)) {
	Throw new Exception("invalid {$pass_key} for {$device_id}");
      }
    } else {
      Throw new Exception("Device type='${device_type}' not yet supported");
    }

    switch($_REQUEST['action']) {
    case 'subscribe':
      StellarData::push_subscribe($subject, $term, $device_id, $device_type);
      $data = array('success' => True);
      break;
    case 'unsubscribe':
      StellarData::push_unsubscribe($subject, $term, $device_id, $device_type);
      $data = array('success' => True);
      break;
    }

  default:
    break;
  }
}

echo json_encode($data);

?>