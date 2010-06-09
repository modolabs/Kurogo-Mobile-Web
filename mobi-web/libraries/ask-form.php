<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/security.php";
require WEBROOT . "page_builder/page_header.php";
require WEBROOT . "libraries/libraries_lib.php";

ssl_required();

$username = get_username();

$ask_us_lists = Array(
  'Art, Architecture &amp; Planning' => 'libraries-arch-plan@mit.edu',
  'Engineering &amp; Computer Science' => 'libraries-engineering@mit.edu',
  'Humanities' => 'libraries-humanities@mit.edu',
  'Management &amp; Business' => 'libraries-bus-man@mit.edu',
  'Science' => 'libraries-science@mit.edu',
  'Social Sciences' => 'libraries-socsci@mit.edu',
  'General' => 'libraries-general-help@mit.edu',
  'Circulation' => 'libraries-circulation@mit.edu',
  'Technical Help' => 'libraries-tech-help@mit.edu',
  );

$consultation_lists = Array(
  'General' => 'ask-hum@mit.edu',
  'Art, Architecture &amp; Planning' => 'ask-rotch@mit.edu',
  'Engineering &amp; Computer Science' => 'ask-barker@mit.edu',
  'GIS' => 'gishelp@mit.edu',
  'Humanities' => 'ask-hum@mit.edu',
  'Management &amp; Business' => 'ask-dewey@mit.edu',
  'Science' => 'ask-science@mit.edu',
  'Social Sciences' => 'ask-dewey@mit.edu',
  );

$required_text = '';
$missing_fields = Array();

if ($_REQUEST['ask_type']) { // user submitted a question

  $ask_type = $_REQUEST['ask_type'];

  $fullname = get_fullname();
  $name_parts = explode(' ', $fullname);
  $first_name = $name_parts[0];
  $last_name = end($name_parts);
  $email = "$username@mit.edu";
  $server = $_SERVER['SERVER_NAME'];

  $additional_headers = "From: $fullname <$username@$server>";
  $additional_headers .= "\r\n" . "Reply-To: $email";
  $additional_headers .= "\r\n" . 'Cc: lisah@mit.edu';

  $topic = $_REQUEST['topic'] or missing_input('topic', $missing_fields);
  $status = $_REQUEST['status'] or missing_input('status', $missing_fields);
  $department = $_REQUEST['department'] or missing_input('department', $missing_fields);
  $contact_phone = $_REQUEST['phone'];

  if ($ask_type == 'form') {

    $recipient = $ask_us_lists[$topic];

    $subject = $_REQUEST['subject'] or missing_input('subject', $missing_fields);
    if ($subject)
      $subject = 'via mobile: ' . $subject;
    $question = $_REQUEST['question'] or missing_input('question', $missing_fields);
    $details = '';
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if ($topic == 'Technical Help') {
      $on_campus = $_REQUEST['on_campus'] or missing_input('on_campus', $missing_fields);
      $vpn = $_REQUEST['vpn'] or missing_input('vpn', $missing_fields);
      $details = <<<DETAILS
------------------------
For Technical Questions Only

Is the problem happening on or off campus? $on_campus
Are you using VPN? $vpn
DETAILS;
    }

    $contents = <<<EMAIL
Email: $email
Last name: $last_name
First name: $first_name
Status: $status
Phone number: $contact_phone
Department: $department

Question: $question
$details

Browser: $ua

EMAIL;

    $thank_you_text = '<p>Thank you for your question.</p><p>Email is checked regularly Mon-Fri 10am-5pm, except Institute holidays. Your question will be answered as soon as possible, and no later than 5:00 PM the next business day.</p>';

  } else {

    $recipient = $consultation_lists[$topic];

    $research_subject = $_REQUEST['subject'] or missing_input('subject', $missing_fields);
    $subject = 'MIT Libraries Consultation Appointment';
    $timeframe = $_REQUEST['timeframe'];
    $description = $_REQUEST['description'] or missing_input('description', $missing_fields);
    $purpose = $_REQUEST['why'];
    $course = $_REQUEST['course'];

    $contents = <<<EMAIL
Email: $email
Last name: $last_name
First name: $first_name
Status: $status
Phone number: $contact_phone
Department: $department
Research topic: $research_subject
Subject: $topic
Timeframe: $timeframe
Description: $description
Purpose: $purpose
Course: $course

EMAIL;

    $thank_you_text = '<p>Thank you for your appointment request</p><p>Your request has been forwarded to the librarians handling your research topic. You will be contacted within 24 business horus.</p><p>We look forward to meeting with you!</p>';

  }

  if (count($missing_fields) === 0) {
    mail(
      $recipient,
      $subject,
      $contents,
      $additional_headers
      );
    $contents = nl2br($contents);
    require "$page->branch/ask-confirmation.html";
  } else {
    $required_text = '<p>Please fill in all required fields</p>';
    require "$page->branch/ask-$ask_type.html";
  }

} elseif ($_REQUEST['page'] == 'expert') {

  require "$page->branch/ask-form.html";

} elseif ($_REQUEST['page'] == 'appointment') {

  require "$page->branch/ask-consultation.html";

}

$page->output();

function missing_input($field, &$missing_fields) {
  $missing_fields[] = $field;
  return 1;
}

// show text in a different color if it was missing from input
function hilite($field, $missing_fields) {
  if (in_array($field, $missing_fields))
    return ' class="hilite_required"';
  return '';
}

function input_value($field) {
  return ($_REQUEST[$field]) ? ' value="' . $_REQUEST[$field] . '"' : '';
}

function option_value($field, $value, $extra='') {
  $value_str = '<option value="' . $value . '"' . $extra;
  $value_str .= ($value == $_REQUEST[$field]) ? ' selected>' : '>';
  return $value_str;
}

function radio_button($field, $value) {
  $button_str = '<input type="radio" name="' . $field . '" value="' . $value . '"';
  $button_str .= ($value == $_REQUEST[$field]) ? ' checked />' : '/>';
  return $button_str;
}

?>
