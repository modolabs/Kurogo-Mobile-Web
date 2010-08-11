<?php

define("PARAGRAPH_LIMIT", 4);

require_once LIBDIR . '/GazetteRSS.php';
require_once 'NewsURL.php';

$newsURL = new NewsURL($_REQUEST);

if($newsURL->isSearchResults()) {

    $stories = GazetteRSS::searchArticlesArray(
            $newsURL->searchTerms(),
            $newsURL->searchSeekId(),
            $newsURL->searchSeekDirection());

} else {

    $stories = GazetteRSS::getMoreArticlesArray(
            $newsURL->categoryId(),
            $newsURL->categorySeekId(),
            $newsURL->categorySeekDirection());

}

$story_id = $newsURL->storyId();
for($i = 0; $i < count($stories); $i++) {
    if($stories[$i]["story_id"] == $story_id) {
        $story = $stories[$i];
        break;
    }
}

$story_pages = HTMLPager($story["body"], PARAGRAPH_LIMIT);

$all_pages = "";
foreach($story_pages as $story_page) {
    $all_pages .= $story_page->getText();
}

$date = date("M d, Y", $story["unixtime"]);
require "Webkit/story.html";

$page->output();

?>