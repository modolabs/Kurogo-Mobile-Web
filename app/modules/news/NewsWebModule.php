<?php
/**
  * @package Module
  * @subpackage News
  */

/**
  * @package Module
  * @subpackage News
  */

if (!function_exists('mb_convert_encoding')) {
    die('Multibyte String Functions not available (mbstring)');
}

class NewsWebModule extends WebModule {
  const defaultController = 'RSSDataController';
  protected $id = 'news';
  protected $feeds = array();
  protected $feedIndex = 0;
  protected $feed;
  protected $maxPerPage = 10;
  protected $maxPerPane = 5;
  protected $showImages = true;
  protected $showPubDate = false;
  protected $showAuthor = false;
  protected $showLink = false;
  
  public static function validateFeed($section, $feedData) {
        if (!self::argVal($feedData, 'TITLE')) {
            return new KurogoError(1, $this->getLocalizedString('ERROR_NO_TITLE'),$this->getLocalizedString('ERROR_NO_TITLE_DESCRIPTION'));
        }

        if (!isset($feedData['CONTROLLER_CLASS'])) {
            $feedData['CONTROLLER_CLASS'] = self::defaultController;
        }
        
        try {
            $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
        } catch (KurogoConfigurationException $e) {
            return KurogoError::errorFromException($e);
        }
        
        return true;
  }

  protected function feedURLForFeed($feedIndex) {
    return isset($this->feeds[$feedIndex]) ? 
      $this->feeds[$feedIndex]['baseURL'] : null;
  }
  
  protected function getImageForStory($story) {
    if ($this->showImages) {
        $image = $story->getImage();
        
        if ($image) {
          return array(
            'src'    => $image->getURL(),
            'width'  => $image->getProperty('width'),
            'height' => $image->getProperty('height'),
          );
        }
    }
    
    return null;
  }

  protected function urlForPage($pageNumber) {
    $args = $this->args;
    $args['storyPage'] = $pageNumber;
    return $this->buildBreadcrumbURL('story', $args, false);
  }

  protected function feedURL($feedIndex, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('index', array(
      'section' => $feedIndex
    ), $addBreadcrumb);
  }

    protected function cleanContent($content) {
    
        //deal with pre tags. strip out pre tags and add <br> for newlines
        $bits = preg_split( '#(<pre.*?'.'>)(.*?)(</pre>)#s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $content = array_shift($bits);
        $i=0;
        while ($i<count($bits)) {
            $tag = $bits[$i++];
            $content .= nl2br($bits[$i++]);
            $close = $bits[$i++];
            $i++;
        }
    
        return $content;
    }

  public function getFeeds() {
    return $this->feeds;
  }
  
  public function getFeed($index) {
    if (isset($this->feeds[$index])) {
        $feedData = $this->feeds[$index];
        if (!isset($feedData['CONTROLLER_CLASS'])) {
            $feedData['CONTROLLER_CLASS'] = self::defaultController;
        }
        $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
        return $controller;
    } else {
        throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
    }
  }
    public function searchItems($searchTerms, $limit=null, $data=null) {
    
        $this->feed->addFilter('search', $searchTerms);
        $items = $this->feed->items(0, $limit);
        
        return $items;
    }

    public function linkForItem(KurogoObject $story, $data=null) {
        
        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        $image = $this->showImages ? $story->getImage() : false;
        
        $link = array(
            'title'   => $this->htmlEncodeFeedString($story->getTitle()),
            'pubDate' => $date,
            'author'  => $this->htmlEncodeFeedString($story->getAuthor()),
            'subtitle'=> $this->htmlEncodeFeedString($story->getDescription()),
            'img'     => $image ? $image->getURL() : ''
        );
        
        if ($storyID = $story->getGUID()) {
            $options = array(
                'storyID'=>$storyID
            );    
            
            foreach (array('section','start','filter') as $field) {
                if (isset($data[$field])) {
                    $options[$field] = $data[$field];
                }
            }
                
            $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
            $noBreadcrumbs = isset($data['noBreadcrumbs']) ? $data['noBreadcrumbs'] : false;
    
            if ($noBreadcrumbs) {
              $link['url'] = $this->buildURL('story', $options);
            } else {
              $link['url'] = $this->buildBreadcrumbURL('story', $options, $addBreadcrumb);
            }

        } elseif ($url = $story->getProperty('link')) {
            $link['url'] = $url;
        }

        return $link;
    }

    protected function initialize() {

        $this->feeds      = $this->loadFeedData();
        $this->maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
        
        if (count($this->feeds)==0) {
            return;
        }
        
        $this->feedIndex = $this->getArg('section', 0);
        if (!isset($this->feeds[$this->feedIndex])) {
            $this->feedIndex = key($this->feeds);
        }

        $feedData = $this->feeds[$this->feedIndex];
        $this->feed = $this->getFeed($this->feedIndex);
        $this->showImages = isset($feedData['SHOW_IMAGES']) ? $feedData['SHOW_IMAGES'] : true;
        $this->showPubDate = isset($feedData['SHOW_PUBDATE']) ? $feedData['SHOW_PUBDATE'] : false;
        $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : false;
        $this->showLink = isset($feedData['SHOW_LINK']) ? $feedData['SHOW_LINK'] : false;
    }    
    
    protected function htmlEncodeFeedString($string) {
        return mb_convert_encoding($string, 'HTML-ENTITIES', $this->feed->getEncoding());
    }
    
    protected function initializeForPage() {
        if (!$this->feed) {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_NOT_CONFIGURED'));
        }

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
          throw new KurogoUserException($this->getLocalizedString('ERROR_STORY_NOT_FOUND', $storyID));
        }

        $this->setLogData($storyID, $story->getTitle());
        
        if (!$content = $this->cleanContent($story->getProperty('content'))) {
          if ($url = $story->getProperty('link')) {
              header("Location: $url");
              exit();
          } else {
              throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND', $storyID));
          }
        }

        if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
            $body = $story->getDescription()."\n\n".$story->getLink();
            $shareEmailURL = $this->buildMailToLink("", $story->getTitle(), $body);
            $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_STORY'));
            $this->assign('shareEmailURL', $shareEmailURL);
            $this->assign('shareRemark',   $story->getTitle());
            $this->assign('storyURL',      $story->getLink());
        }

        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        
        $this->enablePager($content, $this->feed->getEncoding(), $storyPage);
        
        $this->assign('date',          $date);
        $this->assign('title',         $this->htmlEncodeFeedString($story->getTitle()));
        $this->assign('author',        $this->htmlEncodeFeedString($story->getAuthor()));
        $this->assign('image',         $this->getImageForStory($story));
        $this->assign('link',          $story->getLink());
        $this->assign('ajax',          $this->getArg('ajax'));
        $this->assign('showLink',      $this->showLink);
        break;
        
      case 'search':
        $searchTerms = $this->getArg('filter');
        $start       = $this->getArg('start', 0);
        
        if ($searchTerms) {

            $this->feed->addFilter('search', $searchTerms);
            $items = $this->feed->items($start, $this->maxPerPage);
            $this->setLogData($searchTerms);
            $totalItems = $this->feed->getTotalItems();
            $stories = array();

            $options = array(
                'filter' => $searchTerms,
                'section' => $this->feedIndex
            );

            foreach ($items as $story) {
                $stories[] = $this->linkForItem($story, $options);
            }

            $previousURL = '';
            $nextURL = '';
          
          if ($totalItems > $this->maxPerPage) {
            $args = $this->args;
            if ($start > 0) {
              $args['start'] = $start - $this->maxPerPage;
              $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }
            
            if (($totalItems - $start) > $this->maxPerPage) {
              $args['start'] = $start + $this->maxPerPage;
              $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }
          }
          
          $extraArgs = array(
              'section' => $this->feedIndex
          );

          $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          $this->addOnLoad('setupNewsListing();');

          $this->assign('maxPerPage',  $this->maxPerPage);
          $this->assign('extraArgs',   $extraArgs);
          $this->assign('searchTerms', $searchTerms);
          $this->assign('stories',     $stories);
          $this->assign('previousURL', $previousURL);
          $this->assign('nextURL',     $nextURL);
          $this->assign('showImages',  $this->showImages);
          $this->assign('showPubDate', $this->showPubDate);
          $this->assign('showAuthor',  $this->showAuthor);
          
        } else {
          $this->redirectTo('index'); // search was blank
        }
        break;
        
      case 'pane':
        $start = 0;
        $items = $this->feed->items($start, $this->maxPerPane);
        $stories = array();
        $options = array(
            'noBreadcrumbs'=>true,
            'section' => $this->feedIndex
        );

        foreach ($items as $story) {
            $stories[] = $this->linkForItem($story, $options);
        }
        
        foreach ($stories as $i => $story) {
            $stories[$i]['url'] = $this->buildURL('index').
                '#'.urlencode(FULL_URL_PREFIX.ltrim($story['url'], '/'));
        }
        
        $this->assign('stories', $stories);
        break;
      
      case 'index':
        $start = $this->getArg('start', 0);
      
        $items = $this->feed->items($start, $this->maxPerPage);
        $totalItems = $this->feed->getTotalItems();
        $this->setLogData($this->feedIndex, $this->feed->getTitle());
       
        $previousURL = null;
        $nextURL = null;
        if ($totalItems > $this->maxPerPage) {
          $args = $this->args;
          if ($start > 0) {
            $args['start'] = $start - $this->maxPerPage;
            $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
          }
          
          if (($totalItems - $start) > $this->maxPerPage) {
            $args['start'] = $start + $this->maxPerPage;
            $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
          }
        }

        $options = array(
            'section' => $this->feedIndex
        );
        
        $stories = array();
        foreach ($items as $story) {
            $stories[] = $this->linkForItem($story, $options);
        }
        
        $sections = array();
        foreach ($this->feeds as $index => $feedData) {
          $sections[] = array(
            'value'    => $index,
            'title'    => $feedData['TITLE'],
            'selected' => ($this->feedIndex == $index),
            'url'      => $this->feedURL($index, false),
          );
        }
        
        $hiddenArgs = array(
          'section'=>$this->feedIndex
        );
        
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addOnLoad('setupNewsListing();');

        $this->assign('maxPerPage',     $this->maxPerPage);
        $this->assign('hiddenArgs',     $hiddenArgs);
        $this->assign('sections',       $sections);
        $this->assign('currentSection', $sections[$this->feedIndex]);
        $this->assign('placeholder',    $this->getLocalizedString('SEARCH_MODULE', $this->getModuleName()));
        $this->assign('stories',        $stories);
        $this->assign('isHome',         true);
        $this->assign('previousURL',    $previousURL);
        $this->assign('nextURL',        $nextURL);
        $this->assign('showImages',     $this->showImages);
        $this->assign('showPubDate',    $this->showPubDate);
        $this->assign('showAuthor',     $this->showAuthor);
        break;
    }
  }
}
