<?php


require_once "../config/mobi_web_constants.php";
require_once PAGE_HEADER;
require_once LIBDIR . "StellarData.php";
require_once "stellar_lib.php";


if (!isset($_REQUEST['refresh']) && $page->branch != "Webkit") {
	header("Location: index.php?refresh=true");
        die(0);
} 


require "$page->branch/index.html";

removeOldMyStellar();

$page->prevent_caching($pagetype);
$page->output();

?>
