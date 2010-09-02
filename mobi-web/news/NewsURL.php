<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class NewsURL {   

    private static $home = "index.php";
    private static $story = "story.php";
    private static $categories = "categories.php";

    // the state of the news home page
    // such as the category, how far down the list of stories
    private $homeState = array();

    // the state of the news search page
    // such as the search term, how far down the list of search
    // results
    private $searchState = NULL;

    // the state of a news article page
    // such as the story_id and which pages of the story
    private $storyState = NULL;

    public function __construct($request) {

        $this->homeState["category_id"] = self::get($request, "category_id", 0);
        $this->homeState["category_seek_id"] = self::get($request, "category_seek_id", NULL);
        $this->homeState["category_seek_direction"] = self::get($request, "category_seek_direction", "forward");

        if(isset($request["search_terms"])) {
            $this->searchState["search_terms"] = $request["search_terms"];
            $this->searchState["search_seek_id"] = self::get($request, "search_seek_id", NULL);
            $this->searchState["search_seek_direction"] = self::get($request, "search_seek_direction", "forward");
        }

        if(isset($request["story_id"])) {
            $this->storyState["story_id"] = $request["story_id"];
            $this->storyState["story_page"] = self::get($request, "story_page", 0);
        }

    }

    public function isHome() {
        if( ($this->searchState != NULL) || ($this->storyState) ) {
            return false;
        }
        return true;
    }

    public function isSearchResults() {
        return ($this->searchState != NULL);
    }

    public function isReverse() {
        if($this->isHome()) {
            return ($this->homeState["category_seek_direction"] == "reverse");
        } else {
           return ($this->searchState["search_seek_direction"] == "reverse");
        }
    }

    public function categoryId() {
        return $this->homeState["category_id"];
    }

    public function categorySeekId() {
        return $this->homeState["category_seek_id"];
    }

    public function categorySeekDirection() {
        return $this->homeState["category_seek_direction"];
    }

    public function categoryURL($categoryId) {
        return self::$home . '?category_id=' . $categoryId;
    }

    public function categoriesURL() {
        return self::$categories . '?' . $this->homeStateQuery();
    }

    public function searchTerms() {
        return $this->searchState["search_terms"];
    }

    public function searchSeekId() {
        return $this->searchState["search_seek_id"];
    }

    public function searchSeekDirection() {
        return $this->searchState["search_seek_direction"];
    }

    private static function get($values, $field, $default) {
        if(isset($values[$field])) {
            return $values[$field];
        } else {
            return $default;
        }
    }


    public function nextURL($last_story_id) {
        return self::$home . "?" . $this->moreListQuery($last_story_id, "forward");
    }

    public function previousURL($first_story_id) {
        return self::$home . "?" . $this->moreListQuery($first_story_id, "reverse");
    }

    private function homeStateQuery() {
        return http_build_query($this->homeState);
    }

    private function searchStateQuery() {
        return $this->homeStateQuery() . "&" . http_build_query($this->searchState);
    }

    public function storyId() {
        return $this->storyState["story_id"];
    }

    public function storyPage() {
        return $this->storyState["story_page"];
    }

    public function storyURL($story=NULL, $page=0) {
        if($this->searchState) {
            $query = $this->searchStateQuery();
        } else {
            $query = $this->homeStateQuery();
        }

        if(!$story) {
            $storyState = $this->storyState;
        } else {
            $storyState = array("story_id" => $story["story_id"]);
        }

        $storyState["story_page"] = $page;

        return self::$story . "?" . $query . "&" . http_build_query($storyState);
    }


    private function moreListQuery($story_id, $direction) {

        // this is a search list
        if($this->searchState) {
            $url = $this->homeStateQuery();
            $query = $this->searchState;
            $query["search_seek_id"] = $story_id;
            $query["search_seek_direction"] = $direction;
            return $url . '&' . http_build_query($query);
        }

        // this is a category list
        $query = $this->homeState;
        $query["category_seek_id"] = $story_id;
        $query["category_seek_direction"] = $direction;
        return http_build_query($query);
    }

    public function homeURL() {
        return self::$home . "?" . $this->homeStateQuery();
    }

    public function breadCrumbs($newsLabel, $searchLabel, $storyLabel=NULL) {
        $breadCrumbs = array($newsLabel => $this->homeURL());

        if($this->searchState) {
            $breadCrumbs[$searchLabel] = self::$home . "?" . $this->searchStateQuery();
        }

        if($this->storyState) {
            $breadCrumbs[$storyLabel] = $this->storyURL();
        }

        return $breadCrumbs;
    }

    public function hiddenHomeStateForm() {
        $form = self::hiddenInputForm($this->homeState, "category_id", 0);
        $form .= self::hiddenInputForm($this->homeState, "category_seek_id", NULL);
        $form .= self::hiddenInputForm($this->homeState, "category_seek_direction", "forward");
        return $form;
    }

    private static function hiddenInputForm($values, $field, $default) {
        if(isset($values[$field])) {
            $value = $values[$field];
        } else if ($default != NULL) {
            $value = $default;
        } else {
            return "";
        }

        return "<input type=\"hidden\" name=\"$field\" value=\"$value\" />";
    }
}