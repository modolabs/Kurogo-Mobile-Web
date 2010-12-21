<?php

/****************************************************************
 *
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

$maxPerPage      = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');

$module = Module::factory('news', '', $_GET);

$content = "";

if (isset($_REQUEST['command'])) {

  switch($_REQUEST['command']) {
   case 'channels':
     $feeds = $module->getFeeds();
     $feed_labels = array();
     foreach ($feeds as $feedData) {
        $feed_labels[] = $feedData['TITLE'];
     }

     $content = json_encode($feed_labels);
     break;

   case 'search':
     if (isset($_REQUEST['q']) && ($searchTerms = $_REQUEST['q'])) {
        $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;

        
        $feed = $module->getFeed($channel);

       $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;
       if ($lastStoryId) {
           // TODO: this has not been handled yet. I need more info on when this is used
          break;
       }

       $feed->addFilter('search', $searchTerms);
       $content = $feed->getData(); // this returns everything.......

    }       
     break;

   default:
     break;
  }

} else {
    $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;
  
        $feed = $module->getFeed($channel);
      
       $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;
       if ($lastStoryId) {
           // TODO: this has not been handled yet. I need more info on when this is used
           break;
      }
  
      $content = $feed->getData();
    
}

header('Content-Length: ' . strlen($content));
echo $content;
