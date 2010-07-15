<?php

require_once LIBDIR . '/GazetteRSS.php';

if (isset($_REQUEST['command'])) {

  switch($_REQUEST['command']) {
   case 'channels':
    $result = GazetteRSS::getChannels();
    echo json_encode($result);
    break;
   default:
     break;
  }

} else {

  $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 0;
  $lastStoryId = isset($_REQUEST['storyId']) ? $_REQUEST['storyId'] : NULL;

  $stories = GazetteRSS::getMoreArticles($channel, $lastStoryId);

  echo $stories;
}
