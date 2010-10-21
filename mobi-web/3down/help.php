<?php

$header = "3DOWN";
$module = "3down";

$help = array(
    'Get the latest status updates on many of MIT&apos;s essential tech services, including phone, email, web, network services, and more.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
