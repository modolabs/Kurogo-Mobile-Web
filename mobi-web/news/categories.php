<?php

require_once LIBDIR . '/GazetteRSS.php';
require_once 'NewsURL.php';

$newsURL = new NewsURL($_REQUEST);

$categories = GazetteRSS::getChannels();

require "$page->branch/categories.html";

$page->output();

?>