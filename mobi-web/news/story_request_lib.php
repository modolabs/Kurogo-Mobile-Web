<?php
require_once LIBDIR . "NewsOffice.php";

function channelID() {
  return isset($_REQUEST['channel_id']) ? $_REQUEST['channel_id'] : 0;
}

function seekStoryID() {
  return isset($_REQUEST['seek_story_id']) ? $_REQUEST['seek_story_id'] : NULL;
}

function seekSearchID() {
  return isset($_REQUEST['seek_search_id']) ? $_REQUEST['seek_search_id'] : 0;
}

function isSearchResult() {
  return isset($_REQUEST['query']);
}

function afterOrBefore() {
  if(isset($_REQUEST['next'])) {
    return ($_REQUEST['next'] == 1);
  }

  return TRUE;
}

function afterOrBeforeInt() {
  return afterOrBefore() ? 1 : 0;
}

function newsHomeQueryArray() {
  $query = array('channel_id' => channelID());

  if(seekStoryID()) {
    $query["seek_story_id"] = seekStoryID();
    if(afterOrBefore()) {
      $query["next"] = '1';
    } else {
      $query["next"] = '0';
    }
  }
  return $query;
}

function newsHomeQuery() {
 return http_build_query(newsHomeQueryArray());
}

function searchQuery() {
  $query = "";
  if(isSearchResult()) {
    $query = "query=" . urlencode($_REQUEST['query']) . "&seek_search_id=" . seekSearchID();
  }
  return $query;
}

function newsHomeURL() {
  return "./?" . newsHomeQuery();
}

function searchHomeURL() {
  return "./?" . newsHomeQuery() . '&' . searchQuery();
}

function storyPathQuery() {
  $querys = array(newsHomeQuery());

  if(searchQuery()) {
    $querys[] = searchQuery();
  }

  if(isset($_REQUEST['search_id'])) {
    $querys[] = "search_id={$_REQUEST['search_id']}";
  }

  if(isset($_REQUEST['story_id'])) {
    $querys[] = "story_id={$_REQUEST['story_id']}";
  }

  return implode('&', $querys);
}

function storyURL($index, $story) {
  $url = "./detail.php?" . storyPathQuery();
  if(isSearchResult()) {
    $url .= "&search_id=$index";
  } else {
    $url .= "&story_id={$story['story_id']}";
  }

  return $url;
}

function path2storyURL() {
  return "./detail.php?" . storyPathQuery();
}

function mainPhotoURL() {
  return "./photo.php?" . storyPathQuery() . "&id=main";
}

function storyPageURL($page_index) {
  return "./detail.php?" . storyPathQuery() . "&page={$page_index}";
}


function getStoryFromRequest() {
  $news_office = new NewsOffice();
  if(isSearchResult()) {
    $search_results = $news_office->get_search_results($_REQUEST['query'], seekSearchID()+$_REQUEST['search_id'], 1);
    return $search_results['items'][0];
  } else {
    return $news_office->get_news_story(channelID(), $_REQUEST['story_id']);
  }
}

function channelsURL() {
  return "./channels.php?" . storyPathQuery();
}

function channels() {
  return NewsOffice::channels();
}

?>