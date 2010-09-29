<?php

require_once realpath(LIB_DIR.'/Module.php');

require_once realpath(LIB_DIR.'/feeds/GazetteRSS.php');
require_once realpath(dirname(__FILE__).'/NewsURL.php');

class NewsModule extends Module {
  protected $id = 'news';
  private $newsURL = null;
  private $story = null;
  
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
      
  protected function urlForPage($pageNumber) {
    return $this->newsURL->storyURL($this->story, $pageNumber);
  }

  protected function initializeForPage() {
    if (isset($this->args['allpages'])) {
      $this->args['story_page'] = 'all'; // backwards compat with old paging system
    }
    
    $this->newsURL = new NewsURL($this->args);
    
    if($this->newsURL->isSearchResults()) {
      $stories = GazetteRSS::searchArticlesArray(
        $this->newsURL->searchTerms(),
        $this->newsURL->searchSeekId(),
        $this->newsURL->searchSeekDirection());
    } else {
      $stories = GazetteRSS::getMoreArticlesArray(
        $this->newsURL->categoryId(),
        $this->newsURL->categorySeekId(),
        $this->newsURL->categorySeekDirection());
    }

    switch ($this->page) {
      case 'story':

        $storyId = $this->newsURL->storyId();
        for($i = 0; $i < count($stories); $i++) {
          if($stories[$i]["story_id"] == $storyId) {
            $this->story = $stories[$i];
            break;
          }
        }
        
        //$categories = GazetteRSS::getChannels();
        
        $date = date("M d, Y", $this->story["unixtime"]);
        
        $shareUrl = "mailto:@?".http_build_query(array(
          "subject" => $this->story["title"],
          "body"    => $this->story["description"] . "\n\n" . $this->story["link"]
        ));
        
        //mailto url's do nor respect '+' (as space) so we convert to %20
        $shareUrl = str_replace('+', '%20', $shareUrl);
        
        $this->assign('date',     $date);
        $this->assign('shareUrl', $shareUrl);
        $this->assign('story',    $this->story);
        $this->enablePager($this->story["body"], $this->newsURL->storyPage());

        break;
        
      case 'index':
      default:
        if($this->newsURL->isHome()) {
          $storiesFirstId = GazetteRSS::getArticlesFirstId($this->newsURL->categoryId());
          $storiesLastId  = GazetteRSS::getArticlesLastId ($this->newsURL->categoryId());
          
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
        
        } else if ($this->newsURL->isSearchResults()) {
          $storiesFirstId = GazetteRSS::getSearchFirstId($this->newsURL->searchTerms());
          $storiesLastId  = GazetteRSS::getSearchLastId ($this->newsURL->searchTerms());
          
          $this->assign('searchTerms', trim($this->args['search_terms']));       
        }
        
        $categories = GazetteRSS::getChannels();
        $categoryId = $this->newsURL->categoryId();
        $category = $categories[$categoryId];
        
        if($this->newsURL->isReverse()) {
          $stories = array_reverse($stories);
        }
        
        foreach ($stories as &$story) {
          $story['url'] = $this->newsURL->storyURL($story).$this->getBreadcrumbArgString('&');
        }
        
        $previousUrl = NULL;
        $nextUrl = NULL;
        if (sizeof($stories)) {
          $firstId = $stories[0]["story_id"];
          if($storiesFirstId != $firstId) {
            $previousUrl = $this->newsURL->previousURL($firstId);
          }
        
          $lastId = $stories[sizeof($stories)-1]["story_id"];
          if($storiesLastId != $lastId) {
            $nextUrl = $this->newsURL->nextURL($lastId);
          }
        }
        
        $categoryLinks = array();
        foreach ($categories as $id => $title) {
          $categoryLinks[] = array(
            'url'   => 'index.php?'.http_build_query(array('category_id' => $id)),
            'title' => $title,
          );
        }
        
        $this->assign('isHome',             $this->newsURL->isHome());
        $this->assign('isSearchResults',    $this->newsURL->isSearchResults());
        $this->assign('hiddenArgs',         $this->newsURL->hiddenArgs());
        
        $this->assign('categories',         $categories);
        $this->assign('categoryLinks',      $categoryLinks);
        $this->assign('category',           $category);
        $this->assign('categoryId',         $categoryId);
        
        $this->assign('stories',            $stories);
        
        $this->assign('previousUrl',        $previousUrl);
        $this->assign('nextUrl',            $nextUrl);
        break;
    }
  }
}
