<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once LIBDIR . '/GazetteRSS.php';
require_once 'NewsURL.php';

$newsURL = new NewsURL($_REQUEST);

$categories = GazetteRSS::getChannels();

require "$page->branch/categories.html";

$page->output();

?>