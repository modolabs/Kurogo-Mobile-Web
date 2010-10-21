<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";
require_once WEBROOT . "page_builder/page_tools.php";
require_once LIBDIR . "NewsOffice.php";

require_once "story_request_lib.php";

define("MAX_DECK_LENGTH", 50);

$story_id = seekStoryID();
$channel_id = channelID();

$news_office = new NewsOffice();
if(isSearchResult()) {
  $seek_search_id = seekSearchID();
  $search_query = $_REQUEST['query'];
  $search_results = $news_office->get_search_results($search_query, $seek_search_id, 10);
  $stories = $search_results['items'];
  $search_results_count = $search_results['totalResults'];  


  if($search_results_count > $seek_search_id + 10) {
    $next_search_id = $seek_search_id + 10;
  } else {
    $next_search_id = NULL;
  }

  if($seek_search_id >= 10) {
    $previous_search_id = $seek_search_id - 10;
  }

  $extra_params = newsHomeQueryArray();
  $extra_params["query"] = $search_query;
  $next_previous_data = new LoadNextPreviousData($extra_params, "seek_search_id", $next_search_id, $previous_search_id);

  if($search_results_count == 0) {
    $search_results_description = "No matches found.";
  } else if($search_results_count == 1) {
    $search_results_description = "1 match found.";
  } else {
    $search_results_description = "{$search_results_count} matches found.";
  }

  if($search_results_count <= 10) {
    $search_results_displayed_description = $search_results_description;
  } else {
    $search_results_displayed_description = ($seek_search_id+1) . '-' . ($seek_search_id+sizeof($stories)) . ' of ' . $search_results_description;
  }

} else {
  $stories = $news_office->get_news($channel_id, $story_id, afterOrBefore());

  $last_story = end($stories);
  if($news_office->get_last_story_id($channel_id) == $last_story['story_id']) {
    // no more stories exists                                                                                             
    $load_next_story_id = NULL;
  } else {
    $load_next_story_id = $last_story['story_id'];
  }

  $first_story = $stories[0];
  if($news_office->get_first_story_id($channel_id) == $first_story['story_id']) {
    // no previous stories exists                                                                                             
    $load_previous_story_id = NULL;
  } else {
    $load_previous_story_id = $first_story['story_id'];
  }

  $next_previous_data = new LoadNextPreviousData(array("channel_id" => channelID()), "seek_story_id", $load_next_story_id, $load_previous_story_id, 'next');
}

$channels = channels();
$channel_title = $channels[channelID()];
if($next_params = $next_previous_data->load_next_params()) {
  $load_next_url = "./?" . http_build_query($next_params);
}
if($previous_params = $next_previous_data->load_previous_params()) {
  $load_previous_url = "./?" . http_build_query($previous_params);
}

$next_phrase = isSearchResult() ? "Next" : "Older articles";
$previous_phrase = isSearchResult() ? "Previous" : "Newer articles";

if($page->branch == 'Webkit') {
  // capture the items html
  ob_start();
    require "$page->branch/items.html";
  $items_html = ob_get_clean();
  $items_json_html = json_encode(array(
    'next_params' => $next_params, 
    'previous_params' => $previous_params,
    'items_html' => $items_html));
  $previous_params_json = json_encode($previous_params);
}

if(isset($_REQUEST['ajax'])) {
  echo $items_json_html;
} else {
  require "$page->branch/index.html";
  $page->output();
}

function storySummary($story) {
  $deck = $story['description'];
  if(strlen($deck) > 0) {
    if(strlen($deck) > MAX_DECK_LENGTH) {
      return trim(substr($deck, 0, MAX_DECK_LENGTH)) . "...";
    } else {
      return $deck;
    }
  }
  return NULL;
}

function thumbURL($story) {
  if(isset($story['main_image'])) {
    return $story['main_image']->thumb_url;
  }

  return "news-placeholder.gif";
}

/**
 * might want to rewrite the Pager in the page_tools to extract this functionality
 */
class LoadNextPreviousData {

  public $query;
  public $id_name;
  public $next;
  public $previous;
  private $after_or_before_id;

  public function __construct($query, $id_name, $next, $previous, $after_or_before_id=NULL) {
    $this->query = $query;
    $this->id_name = $id_name;
    $this->next = $next;
    $this->previous = $previous;
    $this->after_or_before_id = $after_or_before_id;
  }

  public function load_next_params() {
    if($this->next !== NULL) {
      $params = $this->query;
      $params[$this->id_name] = $this->next;
      if($this->after_or_before_id) {
        $params[$this->after_or_before_id] = 1;
      }
      return $params;
    }
  }

  public function load_previous_params() {
    if($this->previous !== NULL) {
      $params = $this->query;
      $params[$this->id_name] = $this->previous;
      if($this->after_or_before_id) {
        $params[$this->after_or_before_id] = 0;
      }
      return $params;
    }
  }
}

?>