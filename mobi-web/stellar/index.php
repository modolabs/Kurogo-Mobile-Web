<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once LIBDIR . "StellarData.php";
require_once WEBROOT . "stellar/stellar_lib.php";


if (!isset($_REQUEST['refresh']) && $page->branch != "Webkit") {
	header("Location: index.php?refresh=true");
        die(0);
} 


require "$page->branch/index.html";

removeOldMyStellar();

$page->prevent_caching($pagetype);
$page->output();

?>
