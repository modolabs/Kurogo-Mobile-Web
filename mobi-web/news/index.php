<?php

require_once LIBDIR . '/GazetteRSS.php';
require_once 'NewsURL.php';

$newsURL = new NewsURL($_REQUEST);

if($newsURL->isHome()) {

    $stories = GazetteRSS::getMoreArticlesArray(
            $newsURL->categoryId(),
            $newsURL->categorySeekId(),
            $newsURL->categorySeekDirection());

    $stories_first_id = GazetteRSS::getArticlesFirstId($newsURL->categoryId());
    $stories_last_id = GazetteRSS::getArticlesLastId($newsURL->categoryId());

} else if($newsURL->isSearchResults()) {

    $stories = GazetteRSS::searchArticlesArray(
            $newsURL->searchTerms(),
            $newsURL->searchSeekId(),
            $newsURL->searchSeekDirection());

    $stories_first_id = GazetteRSS::getSearchFirstId($newsURL->searchTerms());
    $stories_last_id = GazetteRSS::getSearchLastId($newsURL->searchTerms());
}

$categories = GazetteRSS::getChannels();
$category = $categories[$newsURL->categoryId()];

if($newsURL->isReverse()) {
    $stories = array_reverse($stories);
}

if(sizeof($stories)) {
    $first_id = $stories[0]["story_id"];
    if($stories_first_id != $first_id) {
        $previous_url = $newsURL->previousURL($first_id);
    } else {
        $previous_url = NULL;
    }

    $last_id = $stories[sizeof($stories)-1]["story_id"];
    if($stories_last_id != $last_id) {
        $next_url = $newsURL->nextURL($last_id);
    } else {
        $next_url = NULL;
    }
}

require "$page->branch/index.html";


function deck($story) {
    $deck = $story["description"];
    if(strlen($deck) > 30) {
        $deck = substr($deck, 0, 30);
        return trim($deck) . "...";
    } else {
        return $deck;
    }
}

$page->output();

?>