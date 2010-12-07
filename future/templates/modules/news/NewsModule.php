<?php

require_once realpath(LIB_DIR.'/Module.php');

if (!function_exists('mb_substr')) {
    die('Multibyte String Functions not available (mbstring)');
}

class NewsModule extends Module {
  protected $id = 'news';
  protected $feeds = array();
  private $feedIndex=0;
  protected $feed;
  protected $maxPerPage;
  
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
        'width'  => $image->getProperty('width'),
        'height' => $image->getProperty('height'),
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
      'section' => $feedIndex
    ), $addBreadcrumb);
  }

  private function storyURL($story, $addBreadcrumb=true) {
    if ($storyID = $story->getGUID()) {
        return $this->buildBreadcrumbURL('story', array(
          'storyID'   => $storyID,
          'section'   => $this->feedIndex,
          'start'     => self::argVal($this->args, 'start'),
          'filter'    => self::argVal($this->args, 'filter')
        ), $addBreadcrumb);
    } elseif ($link = $story->getProperty('link')) {
        return $link;
    } else {
        return '';
    }
  }
  
  public function getFeeds()
  {
    return $this->feeds;
  }
  
  public function getFeed($index)
  {
    if (isset($this->feeds[$index])) {
        $feedData = $this->feeds[$index];
        $controller = RSSDataController::factory($feedData);
        $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
        return $controller;
    } else {
        throw new Exception("Error getting news feed for index $index");
    }
  }

  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $start           = 0;
    $feedIndex       = 0; // currently it only searches the first feed. TO DO: search all feeds
    
    $this->feed->addFilter('search', $searchTerms);
    $items = $this->feed->items($start, $maxCount+1, $totalItems);
    
    $limit = min($maxCount, count($items));
    for ($i = 0; $i < $limit; $i++) {
      $results[] = array(
        'title' => $items[$i]->getTitle(),
        'url'   => $this->buildBreadcrumbURL("/{$this->id}/story", array(
          'storyID' => $items[$i]->getGUID(),
          'section' => $feedIndex,
          'start'   => $start,
          'filter'  => $searchTerms,
        ), false),
      );
    }
    
    return count($items);
  }
  
  protected function initialize() {
    $this->feeds      = $this->loadFeedData();
    $this->maxPerPage = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');
    
    $this->feedIndex = $this->getArg('section', 0);
    if (!isset($this->feeds[$this->feedIndex])) {
      $this->feedIndex = 0;
    }
    
    $this->feed = $this->getFeed($this->feedIndex);
    
  }

  protected function initializeForPage() {

    switch ($this->page) {
      case 'story':
        $searchTerms = $this->getArg('filter', false);
        if ($searchTerms) {
          $this->feed->addFilter('search', $searchTerms);
        }

        $storyID   = $this->getArg('storyID', false);
        $storyPage = $this->getArg('storyPage', '0');
        $story     = $this->feed->getItem($storyID);
        
        if (!$story) {
          throw new Exception("Story $storyID not found");
        }
        
        if (!$content = $story->getProperty('content')) {
            if ($url = $story->getProperty('link')) {
                header("Location: $url");
                exit();
            } else {
                throw new Exception("No content or link found for story $storyID");
            }
        }
        
        $placeholder = ''; 
        if ($this->pagetype == 'basic' && $this->platform == 'blackberry') {
          $placeholder = '@'; // Some old blackberries don't like empty email links
        }
        $shareUrl = "mailto:{$placeholder}?".http_build_query(array(
          "subject" => $story->getTitle(),
          "body"    => $story->getDescription()."\n\n".$story->getLink()
        ));
        // mailto url's do not respect '+' (as space) so we convert to %20
        $shareUrl = str_replace('+', '%20', $shareUrl);

        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        
        $this->enablePager($content, $this->feed->getEncoding(), $storyPage);
        
        $this->assign('date',     $date);
        $this->assign('shareUrl', $shareUrl);
        $this->assign('title',    $story->getTitle());
        $this->assign('author',   $story->getProperty('harvard:author'));
        $this->assign('image',    $this->getImageForStory($story));
        break;
        
      case 'search':
        $searchTerms = $this->getArg('filter');
        $start       = $this->getArg('start', 0);
        
        if ($searchTerms) {
          $this->setPageTitle('Search');

          $this->feed->addFilter('search', $searchTerms);
          $items = $this->feed->items($start, $this->maxPerPage, $totalItems);
          $stories = array();
          foreach ($items as $story) {
            $item = array(
              'title'       => $story->getTitle(),
              'description' => $story->getDescription(),
              'url'         => $this->storyURL($story),
              'image'       => $this->getImageForStory($story),
            );
            $stories[] = $item;
           }

          $previousUrl = '';
          $nextUrl = '';
          
          if ($totalItems > $this->maxPerPage) {
            $args = $this->args;
            if ($start > 0) {
              $args['start'] = $start - $this->maxPerPage;
              $previousUrl = $this->buildBreadcrumbURL($this->page, $args, false);
            }
            
            if ($totalItems - $start <= $this->maxPerPage) {
              $args['start'] = $start + $this->maxPerPage;
              $nextUrl = $this->buildBreadcrumbURL($this->page, $args, false);
            }
          }

               $extraArgs = array(
                'section'=>$this->feedIndex
            );

          $this->assign('extraArgs',     $extraArgs);
          $this->assign('searchTerms', $searchTerms);
          $this->assign('stories',     $stories);
          $this->assign('previousUrl', $previousUrl);
          $this->assign('nextUrl',     $nextUrl);
          
        } else {
          $this->redirectTo('index'); // search was blank
        }
        break;
        
      case 'index':
        $start = $this->getArg('start', 0);
        $totalItems = 0;
      
        $items = $this->feed->items($start, $this->maxPerPage, $totalItems);
       
        $previousUrl = null;
        $nextUrl = null;
        if ($totalItems > $this->maxPerPage) {
          $args = $this->args;
          if ($start > 0) {
            $args['start'] = $start - $this->maxPerPage;
            $previousUrl = $this->buildBreadcrumbURL($this->page, $args, false);
          }
          
          $args['start'] = $start + $this->maxPerPage;
          $nextUrl = $this->buildBreadcrumbURL($this->page, $args, false);
        }
        
        $stories = array();
        foreach ($items as $story) {
          $item = array(
            'title'       => $story->getTitle(),
            'description' => $story->getDescription(),
            'url'         => $this->storyURL($story),
            'image'       => $this->getImageForStory($story),
          );
          $stories[] = $item;
        }
        
        $sections = array();
        foreach ($this->feeds as $index => $feedData) {
          $sections[] = array(
            'value'    => $index,
            'title'    => htmlentities($feedData['TITLE']),
            'selected' => ($this->feedIndex == $index),
            'url'      => $this->feedURL($index, false),
          );
        }
        
        $hiddenArgs = array(
            'section'=>$this->feedIndex
        );
        
        $this->assign('hiddenArgs',     $hiddenArgs);
        $this->assign('sections',       $sections);
        $this->assign('currentSection', $sections[$this->feedIndex]);
        $this->assign('stories',        $stories);
        $this->assign('isHome',         true);
        $this->assign('previousUrl',    $previousUrl);
        $this->assign('nextUrl',        $nextUrl);
        break;
    }
  }
}
