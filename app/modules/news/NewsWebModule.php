<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Module
  * @subpackage News
  */

/**
  * @package Module
  * @subpackage News
  */

if (!function_exists('mb_convert_encoding')) {
    throw new KurogoException('Multibyte String PHP extension is not installed. http://www.php.net/manual/en/book.mbstring.php');
}

includePackage('News');
includePackage('DateTime');
class NewsWebModule extends WebModule {
  protected static $defaultModel = 'NewsDataModel';
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
  protected $showBodyPubDate = true;
  protected $showBodyAuthor = true;
  protected $showBodyThumbnail = true;
  protected $showBodyImage = true;
  
  public static function validateFeed($section, $feedData) {
        if (!self::argVal($feedData, 'TITLE')) {
            return new KurogoError(1, $this->getLocalizedString('ERROR_NO_TITLE'),$this->getLocalizedString('ERROR_NO_TITLE_DESCRIPTION'));
        }

        if (!isset($feedData['MODEL_CLASS'])) {
			$feedData['MODEL_CLASS'] = self::$defaultModel;
        }

        try {
            $controller = NewsDataModel::factory($feedData['MODEL_CLASS'], $feedData);
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
            'width'  => $image->getWidth(),
            'height' => $image->getHeight()
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
        
        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
        $controller = NewsDataModel::factory($modelClass, $feedData);

        return $controller;
    } else {
        throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
    }
  }

    public function searchItems($searchTerms, $limit=null, $options=null) {  
        
        $start = isset($options['start']) ? $options['start'] : 0;
        $this->feed->setStart($start);
        $this->feed->setLimit($limit);
        return $this->feed->search($searchTerms);
    }

    public function linkForItem(KurogoObject $story, $data=null) {
        
        if ($pubDate = $story->getPubDate()) {
            $date = DateFormatter::formatDate($pubDate, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE);
        } else {
            $date = "";
        }              

        $image = false;
        $large = false;
        if ($this->showImages) {
            if ($this->page == 'pane' && $image = $story->getImage()) {
                $large = true;
            } elseif ($image = $story->getThumbnail()) {
                $large = false;
            }
        }
        
        if (isset($data['federatedSearch']) && $data['federatedSearch'] && !$this->getOptionalModuleVar('SHOW_DESCRIPTION_IN_FEDERATED_SEARCH', 1)) {
            $subtitle = '';
        } else {
          $subtitle = $this->htmlEncodeFeedString($story->getDescription());
        	if ($this->getOptionalModuleVar('STRIP_TAGS_IN_DESCRIPTION', 1)) {
        		$subtitle = Sanitizer::sanitizeHTML($subtitle, array());
        	} else {
        		$subtitle = Sanitizer::sanitizeHTML($subtitle, 'inline');
        	}
        }
        
        $link = array(
            'title'   => $this->htmlEncodeFeedString($story->getTitle()),
            'pubDate' => $date,
            'author'  => $this->htmlEncodeFeedString($story->getAuthor()),
            'subtitle'=> $subtitle,
            'img'     => $image ? $image->getURL() : '',
            'large'   => $large
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

        } elseif ($url = $story->getLink()) {
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
        
        $this->feedIndex = $this->getArg('section');
        if (!isset($this->feeds[$this->feedIndex])) {
            $this->feedIndex = key($this->feeds);
        }

        $feedData = $this->feeds[$this->feedIndex];
        $this->feed = $this->getFeed($this->feedIndex);
        $this->showImages = isset($feedData['SHOW_IMAGES']) ? $feedData['SHOW_IMAGES'] : true;
        $this->showPubDate = isset($feedData['SHOW_PUBDATE']) ? $feedData['SHOW_PUBDATE'] : false;
        $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : false;
        $this->showLink = isset($feedData['SHOW_LINK']) ? $feedData['SHOW_LINK'] : false;
        $this->showBodyPubDate = isset($feedData['SHOW_BODY_PUBDATE']) ? $feedData['SHOW_BODY_PUBDATE'] : true;
        $this->showBodyAuthor = isset($feedData['SHOW_BODY_AUTHOR']) ? $feedData['SHOW_BODY_AUTHOR'] : true;
        $this->showBodyThumbnail = isset($feedData['SHOW_BODY_THUMBNAIL']) ? $feedData['SHOW_BODY_THUMBNAIL'] : true;
        $this->showBodyImage = isset($feedData['SHOW_BODY_IMAGE']) ? $feedData['SHOW_BODY_IMAGE'] : true;
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
          $this->feed->setOption('search', $searchTerms);
        }

        $storyID   = $this->getArg('storyID', false);
        $storyPage = $this->getArg('storyPage', '0');
        $story     = $this->feed->getItem($storyID);
        $ajax      = $this->getArg('ajax', false);
        
        if (!$story) {
          throw new KurogoUserException($this->getLocalizedString('ERROR_STORY_NOT_FOUND', $storyID));
        }

        $this->setLogData($storyID, $story->getTitle());
        
        if (!$content = $this->cleanContent($story->getContent())) {
          if ($ajax) {
            $content = $story->getDescription();
            if ($url = $story->getLink()) {
                $content .= $this->getLocalizedString('READ_MORE_LINK', $url);
            }
          } else {
            if ($url = $story->getLink()) {
              Kurogo::redirectToURL($url);
            }

            // no content or link. Attempt to get description
            if (!$content = $story->getDescription()) {
                throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND', $storyID));
            }
          } 
        }

        if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {

            $truncated = false;
            $body = Sanitizer::sanitizeAndTruncateHTML($story->getDescription(), $truncated,
                $this->getOptionalModuleVar('SHARE_EMAIL_DESC_MAX_LENGTH', 500),
                $this->getOptionalModuleVar('SHARE_EMAIL_DESC_MAX_LENGTH_MARGIN', 50),
                $this->getOptionalModuleVar('SHARE_EMAIL_DESC_MIN_LINE_LENGTH', 50),
                '')."\n\n".$story->getLink();

            $shareEmailURL = $this->buildMailToLink("", $story->getTitle(), $body);
            $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_STORY'));
            $this->assign('shareEmailURL', $shareEmailURL);
            $this->assign('shareRemark',   $story->getTitle());
            $this->assign('storyURL',      $story->getLink());
        }

        if ($pubDate = $story->getPubDate()) {
            $date = DateFormatter::formatDate($pubDate, DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE);
        } else {
            $date = "";
        }              
        
        $this->enablePager($content, $this->feed->getEncoding(), $storyPage);
        
        $this->assign('date',          $date);
        $this->assign('title',         $this->htmlEncodeFeedString($story->getTitle()));
        $this->assign('author',        $this->htmlEncodeFeedString($story->getAuthor()));
        $this->assign('link',          $story->getLink());
        $this->assign('showLink',      $this->showLink);
        $this->assign('showBodyImage', $this->showBodyImage);
        $this->assign('showBodyThumbnail', $this->showBodyThumbnail);
        $this->assign('showBodyPubDate', $this->showBodyPubDate);
        $this->assign('showBodyAuthor', $this->showBodyAuthor);

        if ($this->showImages) {
            if ($image = $story->getImage()) {
                $this->assign('image', 
                  array(
                    'src'    => $image->getURL(),
                    'width'  => $image->getWidth(),
                    'height' => $image->getHeight()
                  )
                );
            } elseif ($thumbnail = $story->getThumbnail()) {
                $this->assign('thumbnail', 
                  array(
                    'src'    => $thumbnail->getURL(),
                    'width'  => $thumbnail->getWidth(),
                    'height' => $thumbnail->getHeight()
                  )
                );
            }
        }
        
        break;
        
      case 'search':
        $searchTerms = $this->getArg('filter');
        $start       = $this->getArg('start', 0);
        
        if ($searchTerms) {

            $options = array(
                'start'=>$start
            );

            $items = $this->searchItems($searchTerms, $this->maxPerPage, $options);
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
        if ($this->ajaxContentLoad) {
            $start = 0;
            $this->feed->setStart(0);
            $this->feed->setLimit($this->maxPerPane);
            $items = $this->feed->items();

            $stories = array();
            $options = array(
                'noBreadcrumbs'=>true,
                'section' => $this->feedIndex
            );
    
            foreach ($items as $story) {
                $link = $this->linkForItem($story, $options);
                $link['url'] = $this->buildURL('index').
                    '#'.urlencode(FULL_URL_PREFIX.ltrim($link['url'], '/'));
                $stories[] = $link;
            }
            
            $this->assign('showImages', $this->showImages);
            $this->assign('stories', $stories);
        }
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addInternalJavascript('/common/javascript/lib/paneStories.js');
        break;
      
      case 'index':
        $start = $this->getArg('start', 0);
        $this->feed->setStart($start);
        $this->feed->setLimit($this->maxPerPage);
        $items = $this->feed->items();

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
          $sections[$index] = array(
            'value'    => $index,
            'title'    => $feedData['TITLE'],
            'selected' => ($this->feedIndex == $index),
            'url'      => $this->feedURL($index, false),
          );
        }
        
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addOnLoad('setupNewsListing();');

        $this->assign('maxPerPage',     $this->maxPerPage);
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
