<?php

$header = "Campus Map";
$module = "map";

$help = array(
  'Find places on the MIT campus in two ways:',

  '1. <strong>Search</strong> by building number or name, or for specific facilities by name or keyword<br />' .
  'Examples: &quot;W20&quot;, &quot;hayden&quot;, &quot;kresge&quot;, &quot;la verdes&quot;, &quot;tennis&quot;, etc.',

  '2. <strong>Browse</strong> by any of the categories on the Campus Map homepage',

  'For each building, you&apos;ll be shown its name, street address and map. This map can be scrolled and zoomed using the links or buttons just below the map image. For most buildings, you can also view a photo of the building and a list of what&apos;s in the building using the tabs above the map image.', 

  'Note that some buildings are located away from streets; for those buildings, the street address shown is usually the main pedestrian access from the street.',

  'On the iPhone and iPod Touch, you can also enter full-screen mode using the right-most button below the map image. Once in full-screen mode, you can rotate your device to switch to horizontal (landscape) orientation. The iPhone and iPod Touch also give you one-button access to locate the building on the device&apos;s native Map application. This button is located below the map zoom and scroll buttons.',

  'The Campus Map is provided by the MIT Facilities Department and IS&amp;T.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
