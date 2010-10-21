<?php

$header = "Libraries";
$module = "libraries";
$help_title = 'About this Beta';

$help = array(
    'Send us your ideas and comments:<br/><a href="mailto:betas-lib@mit.edu">betas-lib@mit.edu</a>',
    'Coming in future versions:<br/>- Search the catalog<br>- Your account: place holds, renew items &amp; more<br/>- Pages optimized for other mobile devices'
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
