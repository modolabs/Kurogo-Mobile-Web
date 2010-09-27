<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/HTMLPager.php');

require_once realpath(LIB_DIR.'/feeds/GazetteRSS.php');
require_once realpath(dirname(__FILE__).'/NewsURL.php');

define("PARAGRAPH_LIMIT", 4);

class NewsModule extends Module {
  protected $id = 'news';
  
  private function basicDeck($story, $bbplus) {
    $limit = $bbplus ? 95 : 75;
    
    $deck = $story["description"];
    if(strlen($deck) > $limit) {
      $deck = mb_substr($deck, 0, $limit, 'UTF-8');
      return trim($deck) . "...";
    } else {
      return $deck;
    }
  }

  protected function initializeForPage() {
    $newsURL = new NewsURL($this->args);
        
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

    switch ($this->page) {
      case 'story':

        $storyId = $newsURL->storyId();
        for($i = 0; $i < count($stories); $i++) {
          if($stories[$i]["story_id"] == $storyId) {
            $story = $stories[$i];
            break;
          }
        }
        
        //$categories = GazetteRSS::getChannels();
        
        $storyPages = HTMLPager($story["body"], PARAGRAPH_LIMIT);
        
        $allPages = "";
        foreach($storyPages as $storyPage) {
          $allPages .= $storyPage->getText();
        }
        
        $pageNumber = $newsURL->storyPage();
        $storyPage = $storyPages[$pageNumber];
        $totalPageCount = count($storyPages);
        $isFirstPage = ($newsURL->storyPage() == 0);
        
        $date = date("M d, Y", $story["unixtime"]);
        
        $shareUrl = "mailto:@?".http_build_query(array(
          "subject" => $story["title"],
          "body"    => $story["description"] . "\n\n" . $story["link"]
        ));
        
        //mailto url's do nor respect '+' (as space) so we convert to %20
        $shareUrl = str_replace('+', '%20', $shareUrl);
        
        $previousPageUrl = NULL;
        if ($pageNumber > 0) {
          $previousPageUrl = $newsURL->storyURL($story, $pageNumber - 1);
        }
        
        $nextPageUrl = NULL;
        if ($pageNumber + 1 < $totalPageCount ) {
          $nextPageUrl = $newsURL->storyURL($story, $pageNumber + 1);
        }

        $this->assign('story',           $story);
        $this->assign('storyPages',      $storyPages);
        $this->assign('allPages',        $allPages);
        $this->assign('storyPage',       $storyPage);
        $this->assign('pageNumber',      $pageNumber);
        $this->assign('totalPageCount',  $totalPageCount);
        $this->assign('isFirstPage',     $isFirstPage);
        $this->assign('date',            $date);
        $this->assign('shareUrl',        $shareUrl);
        $this->assign('previousPageUrl', $previousPageUrl);
        $this->assign('nextPageUrl',     $nextPageUrl);
        break;
        
      case 'index':
      default:
        if($newsURL->isHome()) {
          $storiesFirstId = GazetteRSS::getArticlesFirstId($newsURL->categoryId());
          $storiesLastId  = GazetteRSS::getArticlesLastId ($newsURL->categoryId());
          
          if (isset($stories)) {
            $featuredIndex = 0;
            foreach ($stories as $story) {
              if ($story['featured']) break;
              $featuredIndex++;
            }
            if ($featuredIndex > 0 && isset($stories[$featuredIndex])) {
              $featuredStory = $stories[$featuredIndex];
              array_splice ($stories, $featuredIndex, 1);
              array_unshift($stories, $featuredStory);
            }
          }
        
        } else if ($newsURL->isSearchResults()) {
          $storiesFirstId = GazetteRSS::getSearchFirstId($newsURL->searchTerms());
          $storiesLastId  = GazetteRSS::getSearchLastId ($newsURL->searchTerms());
          
          $this->assign('searchTerms', trim($this->args['filter']));       
        }
        
        $categories = GazetteRSS::getChannels();
        $category = $categories[$newsURL->categoryId()];
        
        if($newsURL->isReverse()) {
          $stories = array_reverse($stories);
        }
        
        foreach ($stories as &$story) {
          $story['url'] = $newsURL->storyURL($story).$this->getBreadcrumbArgString('&');
        }
        
        $previousUrl = NULL;
        $nextUrl = NULL;
        if (sizeof($stories)) {
          $firstId = $stories[0]["story_id"];
          if($storiesFirstId != $firstId) {
            $previousUrl = $newsURL->previousURL($first_id);
          }
        
          $lastId = $stories[sizeof($stories)-1]["story_id"];
          if($storiesLastId != $lastId) {
            $nextUrl = $newsURL->nextURL($lastId);
          }
        }
        
        $this->assign('isHome', $newsURL->isHome());
        
        $this->assign('categories', $categories);
        $this->assign('newsURLCategoryId', $newsURL->categoryId());
        $this->assign('stories', $stories);
        
        $this->assign('previousUrl', $previousUrl);
        $this->assign('nextUrl', $nextUrl);
        break;
    }
  }
}
