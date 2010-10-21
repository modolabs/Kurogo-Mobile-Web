<?php

$header = "People Directory";
$module = "people";

$help = array(
  'Search for MIT students, faculty, staff, and affiliates by part or all of their name, email address, or phone number.',

  'Example: To find William Barton Rogers, you could search by:<br />' .
  '- <strong>Name</strong> (full or partial): e.g., &quot;William Rogers,&quot; &quot;will rog&quot;, &quot;w rogers&quot;, &quot;will&quot;, etc.<br />' .
  '- <strong>Email address</strong>: &quot;wbrogers&quot;, &quot;wbrogers@mit.edu&quot;<br />' .
  '- <strong>Phone number</strong> (full or partial):  e.g., &quot;6172531000&quot;, &quot;31000&quot;',

  'Depending on the person that you looked up and the capabilities of your mobile device, you can call or email the person directly, or find their office on the campus map.',

  'If you run into difficulty, please try calling 617-253-1000 for voice-assisted directory search.',
);
$docRoot = getenv("DOCUMENT_ROOT");

  require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
