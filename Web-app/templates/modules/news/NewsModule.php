<?php

require_once realpath(LIB_DIR.'/Module.php');

class NewsModule extends Module {
  protected $id = 'news';
  protected $feeds = array();
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

  private function feedURLForFeed($feedIndex) {
    return isset($this->feeds[$feedIndex]) ? 
      $this->feeds[$feedIndex]['baseURL'] : null;
  }
  
  private function getImageForStory($story) {
    $image = $story->getImage();
    
    if ($image) {
      return array(
        'src'    => $image->getURL(),
        'width'  => $image->getWidth(),
        'height' => $image->getHeight(),
      );
    }
    
    return null;
  }

  protected function urlForPage($pageNumber) {
    $args = $this->args;
    $args['storyPage'] = $pageNumber;
    return $this->buildBreadcrumbURL('story', $args, false);
  }

  private function feedURL($feedIndex, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('index', array(
      'feedIndex' => $feedIndex
    ), $addBreadcrumb);
  }

  private function storyURL($story, $feedIndex, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('story', array(
      'storyID'   => $story->getProperty($GLOBALS['siteConfig']->getVar('NEWS_STORY_ID_FIELD')),
      'feedIndex' => $feedIndex,
      'start'     => $this->argVal($this->args, 'start'),
      'filter'    => $this->argVal($this->args, 'filter')
    ), $addBreadcrumb);
    
  }
  
  private function loadFeeds() {
    $feeds      = $GLOBALS['siteConfig']->getVar('NEWS_FEEDS');
    $feedLabels = $GLOBALS['siteConfig']->getVar('NEWS_FEED_LABELS');

    if (!$feeds || (count($feeds) != count($feedLabels))) {
      throw new Exception("Invalid feed and label list");
    }
    
    $this->feeds = array();
    foreach ($feeds as $index => $feed) {
      $this->feeds[$index] = array(
        'baseURL' => $feed,
        'url'     => $this->feedURL($index),
        'title'   => $feedLabels[$index]
      );
    }
    
    return $this->feeds;
  }

  protected function initializeForPage() {
    $controllerClass = $GLOBALS['siteConfig']->getVar('NEWS_CONTROLLER_CLASS');
    $parserClass     = $GLOBALS['siteConfig']->getVar('NEWS_PARSER_CLASS');
    $maxPerPage      = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');
    
    $this->loadFeeds();

    $this->feedIndex = $this->getArg('section', 0);
    if (!isset($this->feeds[$this->feedIndex])) {
      $this->feedIndex = 0;
    }

    $feed = new $controllerClass($this->feedURLForFeed($this->feedIndex), new $parserClass);

    switch ($this->page) {
      case 'story':
        $searchTerms = $this->getArg('filter');
        if ($searchTerms) {
          $feed->addFilter('search', $searchTerms);
        }

        $storyID   = $this->getArg('storyID', false);
        $storyPage = $this->getArg('storyPage', '0');
        $story     = $feed->getItem($storyID);
        
        if (!$story) {
          Debug::die_here("Story $storyID not found");
          throw new Exception("Story $storyID not found");
        }
        
        $shareUrl = "mailto:@?".http_build_query(array(
          "subject" => $story->getTitle(),
          "body"    => $story->getDescription()."\n\n".$story->getLink()
        ));
        // mailto url's do nor respect '+' (as space) so we convert to %20
        $shareUrl = str_replace('+', '%20', $shareUrl);

        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        
        $content = $story->getProperty($GLOBALS['siteConfig']->getVar('NEWS_FEED_CONTENT_PROPERTY'));
        $this->enablePager($content, $feed->getEncoding(), $storyPage);
        
        $this->assign('date',     $date);
        $this->assign('shareUrl', $shareUrl);
        $this->assign('title',    $story->getTitle());
        $this->assign('author',   $story->getProperty('harvard:author'));
        $this->assign('image',    $image);
        $this->assign('image',    $this->getImageForStory($story));
        break;
        
      case 'search':
        $searchTerms = $this->getArg('filter');
        $start       = $this->getArg('start', 0);
        
        if ($searchTerms) {
          $this->setPageTitle('Search');

          $feed->addFilter('search', $searchTerms);
          $items = $feed->items($start, $maxPerPage, $totalItems);
          $stories = array();
          foreach ($items as $story) {
            $item = array(
              'title'       => $story->getTitle(),
              'description' => $story->getDescription(),
              'url'         => $this->storyURL($story, $this->feedIndex),
              'image'       => $this->getImageForStory($story),
            );
            $stories[] = $item;
           }

          $previousUrl = '';
          $nextUrl = '';
          
          if ($totalItems > $maxPerPage) {
            if ($start > 0) {
              $previousUrl = $this->buildBreadcrumbURL($this->page, array(
                'feedIndex' => $this->feedIndex, 
                'filter'    => $searchTerms,
                'start'     => $start - $maxPerPage
              ));
            }
            
            if ($totalItems - $start <= $maxPerPage) {
              $nextUrl = $this->buildBreadcrumbURL($this->page, array(
                'feedIndex' => $this->feedIndex, 
                'filter'    => $searchTerms,
                'start'     => $start + $maxPerPage
              ), false);
            }
          }

          $this->assign('searchTerms', $searchTerms);
          $this->assign('stories',     $stories);
          $this->assign('previousUrl', $previousUrl);
          $this->assign('nextUrl',     $nextUrl);
          
        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'index':
        $start = $this->getArg('start', 0);
        $totalItems = 0;
      
        $items = $feed->items($start, $maxPerPage, $totalItems);
       
        $previousUrl = null;
        $nextUrl = null;
        if ($totalItems > $maxPerPage) {
          $args = $this->args;
          if ($start > 0) {
            $args['start'] = $start - $maxPerPage;
            $previousUrl = $this->buildBreadcrumbURL($this->page, $args, false);
          }
          
          $args['start'] = $start + $maxPerPage;
          $nextUrl = $this->buildBreadcrumbURL($this->page, $args, false);
        }
        
        $stories = array();
        foreach ($items as $story) {
          $item = array(
            'title'       => $story->getTitle(),
            'description' => $story->getDescription(),
            'url'         => $this->storyURL($story, $this->feedIndex),
            'image'       => $this->getImageForStory($story),
          );
          $stories[] = $item;
        }
        
        $sections = array();
        foreach ($this->feeds as $index => $feedData) {
          $sections[] = array(
            'value'    => $index,
            'title'    => htmlentities($feedData['title']),
            'selected' => ($this->feedIndex == $index),
            'url'      => $this->feedURL($index, false),
          );
        }
        
        $this->assign('sections',    $sections);
        $this->assign('stories',     $stories);
        $this->assign('isHome',      true);
        $this->assign('previousUrl', $previousUrl);
        $this->assign('nextUrl',     $nextUrl);
        break;
    }
  }
}
