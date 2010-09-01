<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once LIBDIR . '/GazetteRSS.php';

$content = "";

if (isset($_REQUEST['command'])) {

  switch($_REQUEST['command']) {
   case 'channels':
     $result = GazetteRSS::getChannels();
     $content = json_encode($result);
     break;

   case 'search':
     if (isset($_REQUEST['q']) && ($searchTerms = $_REQUEST['q'])) {
       $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;
       $content = GazetteRSS::searchArticles($searchTerms, $lastStoryId);
     }
     break;

   default:
     break;
  }

} else {

  $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;
  $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;

  $content = GazetteRSS::getMoreArticles($channel, $lastStoryId);
}

header('Content-Length: ' . strlen($content));
echo $content;
