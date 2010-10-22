<?php

/****************************************************************
 *
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

$controllerClass = $GLOBALS['siteConfig']->getVar('NEWS_CONTROLLER_CLASS');
$parserClass     = $GLOBALS['siteConfig']->getVar('NEWS_PARSER_CLASS');
$channelClass    = $GLOBALS['siteConfig']->getVar('NEWS_CHANNEL_CLASS');
$itemClass       = $GLOBALS['siteConfig']->getVar('NEWS_ITEM_CLASS');
$imageClass      = $GLOBALS['siteConfig']->getVar('NEWS_IMAGE_CLASS');
$maxPerPage      = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');
$feeds = $GLOBALS['siteConfig']->getVar('NEWS_FEEDS');


$content = "";

if (isset($_REQUEST['command'])) {

  switch($_REQUEST['command']) {
   case 'channels':
     $feed_labels = $GLOBALS['siteConfig']->getVar('NEWS_FEED_LABELS');
     $content = json_encode($feed_labels);
     break;

   case 'search':
     if (isset($_REQUEST['q']) && ($searchTerms = $_REQUEST['q'])) {
        $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;

        
        if (isset($feeds[$channel])) {
        
            $feed = new $controllerClass($feeds[$channel], new $parserClass);
            $feed->setObjectClass('channel', $channelClass);
            $feed->setObjectClass('item', $itemClass);
            $feed->setObjectClass('image', $imageClass);
    
           $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;
           if ($lastStoryId) {
               // TODO: this has not been handled yet. I need more info on when this is used
              break;
           }
    
           $feed->addFilter('search', $searchTerms);
           $content = $feed->getData(); // this returns everything.......
        }

    }       
     break;

   default:
     break;
  }

} else {
    $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;
  
    if (isset($feeds[$channel])) {
    
        $feed = new $controllerClass($feeds[$channel], new $parserClass);
        $feed->setObjectClass('channel', $channelClass);
        $feed->setObjectClass('item', $itemClass);
        $feed->setObjectClass('image', $imageClass);
      
       $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;
       if ($lastStoryId) {
           // TODO: this has not been handled yet. I need more info on when this is used
           break;
      }
  
      $content = $feed->getData();
    }
}

header('Content-Length: ' . strlen($content));
echo $content;
