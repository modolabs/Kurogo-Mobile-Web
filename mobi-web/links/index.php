<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";

class Links {
  public static $links = array(
    'MBTA ("The T": Bus & Subway)' => 'www.mbta.com',
    'Zipcar' => 'www.zipcar.com',
    'MIT Technology Review' => 'mobile.technologyreview.com',
    'Fidelity NetBenefits - Staff & Faculty 401(k)' => 'www.fi-w.com/fiw/NBLogin',
  );
}

$links = array();
foreach(Links::$links as $name => $link) {
  $links[] = array(
    "name" => htmlentities($name),
    "link" => $link,
  );
}

require "$page->branch/index.html";

$page->cache();
$page->output();
    
?>
