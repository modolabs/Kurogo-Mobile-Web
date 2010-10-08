<?php

require_once realpath(LIB_DIR.'/Module.php');

//require_once realpath(LIB_DIR.'/feeds/GazetteRSS.php');
//require_once realpath(dirname(__FILE__).'/NewsURL.php');

class NewsModule extends Module {
  protected $id = 'news';
  protected $feeds = array();
  private $story = null;
  private $feedIndex=0;
  
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

  protected function urlForFeed($feedIndex, $addBreadcrumb=true)
  {
    return $this->buildBreadcrumbURL('index', array(
      'feedIndex' => $feedIndex
    ), $addBreadcrumb);
  }

  protected function feedURLForFeed($feedIndex)
  {
    return isset($this->feeds[$feedIndex]) ? 
            $this->feeds[$feedIndex]['baseURL'] : null;
  }
  
  
  protected function urlForStory($story, $feedIndex, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('story', array(
      'storyID' => $story->getProperty($GLOBALS['siteConfig']->getVar('NEWS_STORY_ID_FIELD')),
      'feedIndex' => $feedIndex,
      'start'=> $this->argVal($this->args, 'start'),
      'filter'=> $this->argVal($this->args, 'filter')
    ), $addBreadcrumb);
    
  }

  protected function urlForPage($pageNumber) {
    return $this->buildBreadcrumbURL('story', array(
      'storyID' => $this->story->getProperty($GLOBALS['siteConfig']->getVar('NEWS_STORY_ID_FIELD')),
      'feedIndex' => $this->feedIndex,
      'filter'=> $this->argVal($this->args, 'filter'),
      'story_page' => $pageNumber
    ), false);
  }
  
  protected function loadFeeds()
  {
    $feeds           = $GLOBALS['siteConfig']->getVar('NEWS_FEEDS');
    $feedLabels      = $GLOBALS['siteConfig']->getVar('NEWS_FEED_LABELS');

    if (!$feeds || (count($feeds) != count($feedLabels))) {
        throw new Exception("Invalid feed and label list");
    }
    
    $this->feeds = array();
    foreach ($feeds as $index=>$feed) {
        $this->feeds[$index] = array(
            'baseURL'=>$feed,
            'url'=>$this->urlForFeed($index),
            'title'=>$feedLabels[$index]
        );
    }
    
    return $this->feeds;
  }

  protected function initializeForPage() {
    $controllerClass = $GLOBALS['siteConfig']->getVar('NEWS_CONTROLLER_CLASS');
    $parserClass     = $GLOBALS['siteConfig']->getVar('NEWS_PARSER_CLASS');
    $maxPerPage      = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');
    $this->loadFeeds();
    $this->assign('feeds',              $this->feeds);
    
    if (isset($this->args['allpages'])) {
      $this->args['story_page'] = 'all'; // backwards compat with old paging system
    }
    
    
    $this->feedIndex = isset($this->args['feedIndex']) ? $this->args['feedIndex'] : 0;
    if (!isset($this->feeds[$this->feedIndex])) {
        $this->feedIndex = 0;
    }

    $feed = new $controllerClass($this->feedURLForFeed($this->feedIndex), new $parserClass);

    switch ($this->page) {
      case 'story':
        $searchTerms = isset($this->args['filter']) ? $this->args['filter'] : "";
        if ($searchTerms) {
            $feed->addFilter('search', $searchTerms);
        }

        $storyID = isset($this->args['storyID']) ? $this->args['storyID'] : false;
        $story_page = isset($this->args['story_page']) ? $this->args['story_page'] : "0";
        if (!$this->story = $feed->getItem($storyID)) {
            Debug::die_here("Story $storyID not found");
            throw new Exception("Story $storyID not found");
        }
        
        $shareUrl = "mailto:@?".http_build_query(array(
          "subject" => $this->story->getTitle(),
          "body"    => $this->story->getDescription() . "\n\n" . $this->story->getLink()
        ));
        
        //mailto url's do nor respect '+' (as space) so we convert to %20
        $shareUrl = str_replace('+', '%20', $shareUrl);
        
        $this->assign('shareUrl', $shareUrl);
        
       $this->enablePager($this->story->getProperty($GLOBALS['siteConfig']->getVar('NEWS_FEED_CONTENT_PROPERTY')), $story_page);

        $pubDate = strtotime($this->story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        $this->assign('date',     $date);
        $this->assign('story',    $this->story);
        
        break;

      case 'search':
        $searchTerms = isset($this->args['filter']) ? $this->args['filter'] : "";
        $start = isset($this->args['start']) ? $this->args['start'] : 0;
        if ($searchTerms) {
            $this->setPageTitle('Search');

            $feed->addFilter('search', $searchTerms);
            $items = $feed->items($start, $maxPerPage, $totalItems);
            $stories = array();
            foreach ($items as $story) {
                $item = array(
                    'title'=>$story->getTitle(),
                    'description'=>$story->getDescription(),
                    'url'=>$this->urlForStory($story, $this->feedIndex),
                    'image'=>$story->getImage()
                );
                $stories[] = $item;
             }

            $previousUrl = '';
            $nextUrl = '';
            
            if ($totalItems > $maxPerPage) {
                if ($start > 0) {
                    $previousUrl = sprintf("%s.php?%s", $this->page, http_build_query(array('feedIndex'=>$this->feedIndex, 'filter'=>$searchTerms, 'start'=>$start-$maxPerPage)));
                }
    
                $nextUrl = sprintf("%s.php?%s", $this->page, http_build_query(array('feedIndex'=>$this->feedIndex, 'filter'=>$searchTerms, 'start'=>$start+$maxPerPage)));
            }

            $this->assign('searchTerms',    $searchTerms);
            $this->assign('stories',        $stories);
            $this->assign('previousUrl',        $previousUrl);
            $this->assign('nextUrl',            $nextUrl);
        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'index':
      default:
        $start = isset($this->args['start']) ? $this->args['start'] : 0;
        $totalItems = 0;
      
        $items = $feed->items($start, $maxPerPage, $totalItems);
        $stories = array();
        $previousUrl = '';
        $nextUrl = '';
        
        if ($totalItems > $maxPerPage) {
            if ($start > 0) {
                $previousUrl = sprintf("%s.php?%s", $this->page, http_build_query(array('feedIndex'=>$this->feedIndex, 'start'=>$start-$maxPerPage)));
            }

            $nextUrl = sprintf("%s.php?%s", $this->page, http_build_query(array('feedIndex'=>$this->feedIndex, 'start'=>$start+$maxPerPage)));
        }
        
        foreach ($items as $story) {
            $item = array(
                'title'=>$story->getTitle(),
                'description'=>$story->getDescription(),
                'url'=>$this->urlForStory($story, $this->feedIndex),
                'image'=>$story->getImage()
            );
            $stories[] = $item;
         }
        
        $this->assign('stories',            $stories);
        $this->assign('feed',               $this->feeds[$this->feedIndex]);
        $this->assign('feedIndex',          $this->feedIndex);
        $this->assign('isHome',             true);
        $this->assign('previousUrl',        $previousUrl);
        $this->assign('nextUrl',            $nextUrl);
        break;
    }
  }
}
