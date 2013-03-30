<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('News');
includePackage('DateTime');
class AthleticsWebModule extends WebModule {

    protected $id = 'athletics';
    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected $feeds = array();
    protected $navFeeds = array();
    protected $maxPerPage = 10;
    protected $maxPerPane = 5;
    protected $showImages = true;
    protected $showBodyThumbnail = true;
    protected $showPubDate = false;
    protected $showAuthor = false;
    protected $showLink = false;
    protected $newsFeed;
    protected $scheduleFeed;
    
    public function loadScheduleData() {
        $scheduleFeeds = $this->getModuleSections('schedule');
        $default = $this->getOptionalModuleSection('schedule','module');
        foreach ($scheduleFeeds as $index=>&$feedData) {
            $feedData = array_merge($default, $feedData);
        }
        return $scheduleFeeds;
    }
    
    public function getGenders() {
        return array(
            'men'=>$this->getLocalizedString('GENDER_MEN'),
            'women'=>$this->getLocalizedString('GENDER_WOMEN'),
            'coed'=>$this->getLocalizedString('GENDER_COED')
        );
        
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
    
    protected function htmlEncodeFeedString($string) {
        return mb_convert_encoding($string, 'HTML-ENTITIES', $this->newsFeed->getEncoding());
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
    
    protected function getThumbnailForStory($story) {
      if ($this->showImages) {
          $thumbnail = $story->getThumbnail();
          if ($thumbnail) {
            return array(
              'src'    => $thumbnail->getURL(),
              'width'  => $thumbnail->getWidth(),
              'height' => $thumbnail->getHeight()
            );
          }
      }
      
      return null;
    }
    
    protected function linkForNewsItem($story, $data = array()) {
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

        $subtitle = $this->htmlEncodeFeedString($story->getDescription());
        if ($this->getOptionalModuleVar('STRIP_TAGS_IN_DESCRIPTION', 1)) {
            $subtitle = Sanitizer::sanitizeHTML($subtitle, array());
        } else {
            $subtitle = Sanitizer::sanitizeHTML($subtitle, 'inline');
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
            
            foreach (array('section', 'start', 'filter') as $field) {
                if (isset($data[$field])) {
                    $options[$field] = $data[$field];
                }
            }
    
            $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
            $link['url'] = $this->buildBreadcrumbURL('news_detail', $options, $addBreadcrumb);
        } elseif ($url = $story->getLink()) {
            $link['url'] = $url;
        }
        return $link;
    }

    protected function timeText(AthleticEvent $event, $timeOnly=false) {
        return DateFormatter::formatDateRange($event->getRange(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
    }
    
    protected function linkForScheduleItem(KurogoObject $event, $data=null) {
    
        $options = array(
            'id'   => $event->getID()
        );
        
        if (isset($data['sport'])) {
            $options['sport'] = $data['sport'];
        }

        $return = array(
            'title'=>$event->getTitle(),
            'subtitle'=>sprintf("%s<br />%s", $this->timeText($event), $event->getLocation()),
            'url'=> $this->buildBreadcrumbURL('schedule_detail', $options, true)
        );
        
        if (isset($data['label'])) {
            $return['label']=$data['label'];
        }
        
        return $return;
    }
    
    protected function valueForType($type, $value, $event = null) {
        $valueForType = $value;
  
        switch ($type) {
            case 'datetime':
                $valueForType = $this->timeText($event);
            break;

        case 'url':
            $valueForType = str_replace("http://http://", "http://", $value);
            if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
                $valueForType = 'http://'.$valueForType;
            }
            break;

        }
        return $valueForType;
    }
  
    protected function urlForType($type, $value) {
        $urlForType = null;
  
        switch ($type) {
            case 'url':
                $urlForType = str_replace("http://http://", "http://", $value);
                if (strlen($urlForType) && !preg_match('/^http\:\/\//', $urlForType)) {
                    $urlForType = 'http://'.$urlForType;
                }
                break;
        
            case 'phone':
                $urlForType = PhoneFormatter::getPhoneURL($value);
                break;
        
            case 'email':
                $urlForType = "mailto:$value";
                break;
        
            case 'category':
                $urlForType = $this->categoryURL($value, false);
                break;
        }
    
        return $urlForType;
    }
    
    protected function formatScheduleDetail(AthleticEvent $event) {
        $allFieldsValue  = $this->getFieldsForSchedule($event);
        $showFields = $this->getModuleSections('schedule-detail');
        $fields = array();

        foreach ($showFields as $key => $info) {
            $field = array();
            if (!isset($allFieldsValue[$key]) || !$allFieldsValue[$key]) {
                continue;
            }
            $value = $allFieldsValue[$key];
            
            if (isset($info['label'])) {
                $field['label'] = $info['label'];
            }
            if (isset($info['class'])) {
                $field['class'] = $info['class'];
            }
            if (isset($info['type'])) {
                $field['title'] = $this->valueForType($info['type'], $value, $event);
                $field['url']   = $this->urlForType($info['type'], $value);
            } elseif (isset($info['module'])) {
                $field = array_merge($field, Kurogo::moduleLinkForValue($info['module'], $value, $this, $event));
            } else {
                $field['title'] = nl2br($value);
            }
            $fields[] = $field;
        }
        return $fields;
    }

    protected function getFieldsForSchedule(AthleticEvent $event) {
        return array(
            'title'         => $event->getTitle(),
            'id'            => $event->getID(),
            'sport'         => $event->getSport(),
            'sportName'     => $event->getSportName(),
            'gender'        => $event->getGender(),
            'start'         => $this->timeText($event),
            'pastStatus'    => $event->getStartTime() > time() ? false : true,
            'location'      => $event->getLocation(),
            'link'          => $event->getLink(),
            'description'   => $event->getDescription(),
        );
    }
    
    protected function getSportsForGender($gender) {
        $feeds = array();
        foreach ($this->feeds as $key=>$feed) {
            if (isset($feed['GENDER']) && $feed['GENDER'] == $gender) {
                $feeds[$key] = $feed;
            }
        }
        return $feeds;
        
    }
    
    //get sport detail 
    protected function getSportData($sport) {
        if (isset($this->feeds[$sport])) {
            return $this->feeds[$sport];
        } else {  
            throw new KurogoDataException($this->getLocalizedString('ERROR_INVALID_SPORT', $sport));
        }
    }
    
    protected function getNavData($tab) {
    
    	//use the data in page-index first
        $data = isset($this->navFeeds[$tab]) ? $this->navFeeds[$tab] : '';
        if (!$data) {
        	//use the data in pages tab_ 
            $vars = $this->getOptionalModuleSection("index", "pages");
            $key = "tab_" . $tab;
            if (isset($vars[$key])) {
                $data = array('TITLE' => $vars[$key]);
            } else {
            	//no data for this type
                $data = null;
            }
        }
        return $data;
    }

    protected function getScheduleFeed($sport) {
        
        if ($sport=='allschedule'){ 
            $scheduleData = $this->getNavData('allschedule');
            $dataModel = Kurogo::arrayVal($scheduleData, 'MODEL_CLASS', self::$defaultEventModel);
            $scheduleFeed = AthleticEventsDataModel::factory($dataModel, $scheduleData);
            return $scheduleFeed;

        } else {
            $scheduleData = $this->loadScheduleData();
        }
        
        if ($feedData = Kurogo::arrayVal($scheduleData, $sport)) {
            $dataModel = Kurogo::arrayVal($feedData, 'MODEL_CLASS', self::$defaultEventModel);
            $this->scheduleFeed = AthleticEventsDataModel::factory($dataModel, $feedData);
            return $this->scheduleFeed;
        }
        
        return null;
    }
    
    protected function getNewsFeed($sport, $gender=null) {
        if ($sport=='topnews') {
            $feedData = $this->getNavData('topnews');
        } elseif (isset($this->feeds[$sport])) {
            $feedData = $this->feeds[$sport];
        } else {
            throw new KurogoDataException($this->getLocalizedString('ERROR_INVALID_SPORT', $sport));
        }
        
        if (isset($feedData['DATA_RETRIEVER']) || isset($feedData['BASE_URL'])) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'AthleticNewsDataModel';
            $this->newsFeed = DataModel::factory($dataModel, $feedData);
            return $this->newsFeed;
        }
        
        return null;
    }
    
    public function searchItems($searchTerms, $limit=null, $options=null) {  
        
        $start = isset($options['start']) ? $options['start'] : 0;
        if ($feed = $this->getNewsFeed('topnews')) {
            $feed->setStart($start);
            $feed->setLimit($limit);
            return $feed->search($searchTerms);
        }
    }
    
    public function linkForItem(KurogoObject $story, $data=null) {
        if (isset($data['federatedSearch']) && $data['federatedSearch']) {
            $data['section'] = 'topnews';
        }
        return $this->linkForNewsItem($story, $data);
    }
    
    protected function loadFeedData() {
        $feeds = parent::loadFeedData();
        foreach ($feeds as $sport=>&$sportData) {
            $localizedKey = sprintf("%s_BOOKMARK", strtoupper($sportData['GENDER']));
            $sportData['GENDER_TITLE'] = $this->getLocalizedString($localizedKey, $sportData['TITLE']);
        }
        
        return $feeds;
    }
    
    protected function initialize() {

        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        $this->showBodyThumbnail = $this->getOptionalModuleVar('SHOW_BODY_THUMBNAIL', 1);
        
    }    
        
    protected function initializeForPage() {
        
        switch($this->page) {
            case 'news':
                $start = $this->getArg('start', 0);
                $section = $this->getArg('section');

                $newsFeed = $this->getNewsFeed($section);
                $newsFeed->setStart($start);
                $newsFeed->setLimit($this->maxPerPage);
                
                $items = $newsFeed->items();
                $totalItems = $newsFeed->getTotalItems();
                $this->setLogData($section, $newsFeed->getTitle());
                
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
                    'section' => $section
                );
                
                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->linkForNewsItem($story, $options);
                }
                
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupNewsListing();');

                $this->assign('maxPerPage',     $this->maxPerPage);
                $this->assign('stories',        $stories);
                $this->assign('previousURL',    $previousURL);
                $this->assign('nextURL',        $nextURL);
                $this->assign('showImages',     $this->showImages);
                $this->assign('showPubDate',    $this->showPubDate);
                $this->assign('showAuthor',     $this->showAuthor);
                break;
                
            case 'news_detail':
                $section = $this->getArg('section');
                $gender = $this->getArg('gender');
                $storyID = $this->getArg('storyID', false);
                $storyPage = $this->getArg('storyPage', '0');
                
                $feed = $this->getNewsFeed($section, $gender);
                $showBodyThumbnail = $this->getOptionalModuleVar('SHOW_BODY_THUMBNAIL', $this->showBodyThumbnail, $section, 'feeds');          
                
                if (!$story = $feed->getItem($storyID)) {
                    throw new KurogoUserException($this->getLocalizedString('ERROR_STORY_NOT_FOUND', $storyID));
                }
                $this->setLogData($storyID, $story->getTitle());
        
                if (!$content = $this->cleanContent($story->getContent())) {
                  if ($url = $story->getLink()) {
                      Kurogo::redirectToURL($url);
                  } else {
                      throw new KurogoDataException($this->getLocalizedString('ERROR_CONTENT_NOT_FOUND', $storyID));
                  }
                }

                if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
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
                    $date = DateFormatter::formatDate($pubDate, DateFormatter::MEDIUM_STYLE, DateFormatter::NO_STYLE);
                } else {
                    $date = "";
                }

                $this->enablePager($content, $this->newsFeed->getEncoding(), $storyPage);
                $this->assign('date',   $date);
                $this->assign('title',  $this->htmlEncodeFeedString($story->getTitle()));
                $this->assign('author', $this->htmlEncodeFeedString($story->getAuthor()));
                $this->assign('image',  $this->getImageForStory($story));
                $this->assign('thumbnail', $this->getThumbnailForStory($story));
                $this->assign('showBodyThumbnail', $showBodyThumbnail);
                $this->assign('link',   $story->getLink());
                $this->assign('showLink', $this->showLink);                
                break;
            
            case 'search':
                $searchTerms = $this->getArg('filter');
                $start       = $this->getArg('start', 0);
                $section = $this->getArg('section', 'topnews');
                
                if ($searchTerms) {
                    $newsFeed = $this->getNewsFeed($section);
                    
                    $newsFeed->setStart($start);
                    $newsFeed->setLimit($this->maxPerPage);
                    
                    $items = $newsFeed->search($searchTerms);
                    $this->setLogData($searchTerms);
                    $totalItems = $newsFeed->getTotalItems();
                    
                    $stories = array();

                    $options = array(
                        'start'  => $start,
                        'filter' => $searchTerms,
                        'section' => $section
                    );

                    foreach ($items as $story) {
                        $stories[] = $this->linkForNewsItem($story, $options);
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
                        'section' => $section
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
            case 'schedule':
                $sport = $this->getArg('sport', '');
                $sportData = $this->getSportData($sport);
                
                if ($scheduleFeed = $this->getScheduleFeed($sport)) {
                    $scheduleItems = array();
                    $options = array(
                        'sport' => $sport
                    );

                    if ($events = $scheduleFeed->items()) {
                        foreach ($events as $event) {
                            $scheduleItems[] = $this->linkForScheduleItem($event, $options);
                        }
                    }

                    $this->assign('scheduleItems', $scheduleItems);
                }
                break;
             
            case 'schedule_detail':
                $sport = $this->getArg('sport', '');
                if ($sport == '') {
                    $sport = 'allschedule';
                }

                $id = $this->getArg('id', '');
                // $sportData = $this->getSportData($sport);
                
                $scheduleFeed = $this->getScheduleFeed($sport);
                if ($schedule = $scheduleFeed->getItem($id)) {
                    $this->assign('schedule', $schedule);
                } else {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_EVENT_NOT_FOUND'));
                }
                $this->setLogData($sport . ':' . $schedule->getID(), $schedule->getSport());
                
                $fields = $this->formatScheduleDetail($schedule);
                $schedule = $this->getFieldsForSchedule($schedule);
                $this->assign('schedule', $schedule);
                $this->assign('fields', $fields);
                break;
                
            case 'sport':
                $sport = $this->getArg('sport', '');
                
                $previous = array();
                $next = array();
                $sportData = $this->getSportData($sport);
                $this->assign('sportTitle', $sportData['GENDER_TITLE']);
                $this->setPageTitles($sportData['GENDER_TITLE']);

                if ($scheduleFeed = $this->getScheduleFeed($sport)) {
                    $scheduleItems = array();
                    if ($previousEvent = $scheduleFeed->getPreviousEvent()) {
                        $previous = $this->linkForScheduleItem($previousEvent, array('sport' => $sport));
                        $scheduleItems[] = $previous;
                    }

                    if ($nextEvent = $scheduleFeed->getNextEvent()) {
                        $next = $this->linkForScheduleItem($nextEvent, array('sport' => $sport));
                        $this->assign('next', $next);
                        $scheduleItems[] = $next;
                    }

                    $scheduleItems[] = array(
                        'title' => $this->getLocalizedString('FULL_SCHEDULE_TEXT'),
                        'url'   => $this->buildBreadcrumbURL('schedule', array('sport' => $sport), true)
                    );

                    $this->assign('scheduleItems', $scheduleItems);
                }
                
                if ($newsFeed = $this->getNewsFeed($sport)) {
                
                    $start = $this->getArg('start', 0);
                    $newsFeed->setStart($start);
                    $newsFeed->setLimit($this->maxPerPage);
                
                    $items = $newsFeed->items();
                    $totalItems = $newsFeed->getTotalItems();
                    $this->setLogData($sport, $newsFeed->getTitle());
                
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
                        'section'=>$sport
                    );
                    $newsItems = array();
                    foreach ($items as $story) {
                        $newsItems[] = $this->linkForNewsItem($story, $options);
                    }
                    
                    $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                    $this->addOnLoad('setupNewsListing();');
                    
                    
                    $this->assign('newsItems', $newsItems);
                    $this->assign('maxPerPage',     $this->maxPerPage);
                    $this->assign('previousURL',    $previousURL);
                    $this->assign('nextURL',        $nextURL);
                    $this->assign('showImages',     $this->showImages);
                    $this->assign('showPubDate',    $this->showPubDate);
                    $this->assign('showAuthor',     $this->showAuthor);
                }
                
                // Bookmark
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $cookieParams = array(
                        'sport'  => $sport
                    );
                  
                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);
                }
                break;

            case "index":
                $tabs = array();
                
                $latestSubTab = $this->getArg('newsTab', 'topnews');        // used to distinguish between top news and schedule
                $latestSubTabLinks = array();                               // will add 'Latest' subTab items to this array

                //get top news
                if ($newsFeedData = $this->getNavData('topnews')) {
                    $start = $this->getArg('start', 0);
                    $newsFeed = $this->getNewsFeed('topnews');
                    $newsFeed->setStart($start);
                    $newsFeed->setLimit($this->maxPerPage);

                    $newsTab = array(   'id' => 'topnews',
                                        'title' => $this->getLocalizedString('TOP_NEWS'),
                                        'url' => $this->buildBreadcrumbURL('index', array('newsTab' => 'topnews'), false),
                                        'ajaxUrl' => $this->buildAjaxBreadcrumbURL('index', array('newsTab' => 'topnews'), false));
                    $latestSubTabLinks[] = $newsTab;
                
                    $newsItems = $newsFeed->items();
                    $totalItems = $newsFeed->getTotalItems();
                    $this->setLogData('topnews', $newsFeed->getTitle());
                
                    $previousURL = null;
                    $nextURL = null;
                    
                    if ($totalItems > $this->maxPerPage) {
                        //$args = $this->args;
                        $args = array();
                        if ($start > 0) {
                            $args['start'] = $start - $this->maxPerPage;
                            $previousURL = $this->buildURL('index', $args);
                        }
                    
                        if (($totalItems - $start) > $this->maxPerPage) {
                            $args['start'] = $start + $this->maxPerPage;
                            $nextURL = $this->buildURL('index', $args);
                        }
                    }
                
                    $topNews = array();
                    
                    $options = array(
                        'section'=>'topnews'
                    );
                    foreach ($newsItems as $story) {
                        $topNews[] = $this->linkForNewsItem($story, $options);
                    }
                    
                    $extraArgs = array(
                        'section' => 'topnews'
                    );
                    
                    $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                    $this->addOnLoad('setupNewsListing();');
                    // KurogoDebug::debug($newsFeedData['TITLE'], true);
                    $this->assign('topNewsTitle', $newsFeedData['TITLE']);
                    $this->assign('topNews', $topNews);
                    $this->assign('extraArgs', $extraArgs);
                    $this->assign('maxPerPage',     $this->maxPerPage);
                    $this->assign('previousURL',    $previousURL);
                    $this->assign('nextURL',        $nextURL);
                    $this->assign('showImages',     $this->showImages);
                    $this->assign('showPubDate',    $this->showPubDate);
                    $this->assign('showAuthor',     $this->showAuthor);
                }

                // get all sports schedule
                if ($scheduleFeedData = $this->getNavData('allschedule')) {
                    // KurogoDebug::debug($scheduleFeed, true);
                    $scheduleFeed = $this->getScheduleFeed('allschedule');
                    $athleticEvents = $scheduleFeed->items();

                    $scheduleItems = array();
                    foreach ($athleticEvents as $event ) {
                        $scheduleItems[] = $this->linkForScheduleItem($event);
                    }
                    if ($limit = Kurogo::arrayVal($scheduleFeedData,'LIMIT')) {
                        $scheduleItems = array_slice($scheduleItems, 0, $limit);
                    }
                    
                    $scheduleTab = array(   'id' => 'allschedule',
                                            'title' => $this->getLocalizedString('ALL_SCHEDULE'),
                                            'url' => $this->buildBreadcrumbURL('index', array('newsTab' => 'allschedule'), false),
                                            'ajaxUrl' => $this->buildAjaxBreadcrumbURL('index', array('newsTab' => 'allschedule'), false));
                    $latestSubTabLinks[] = $scheduleTab;

                    $this->assign('scheduleItems', $scheduleItems);
                }

                // make sure we are displaying tabs correctly
                if (count($latestSubTabLinks) > 0) {
                    // if we have topnews to show
                    $tabs[] = 'topnews';
                    if (count($latestSubTabLinks) == 1) {
                        $latestSubTab = $latestSubTabLinks[0]['id'];
                    }
                }

                $this->assign('latestSubTabLinks', $latestSubTabLinks);
                $this->assign('latestSubTab', $latestSubTab);
                
                //get sports for each gender
                foreach (array('men','women','coed') as $gender) {
                    $sportsData = $this->getNavData($gender);
                    if($sportsData) {
                        if ($sportsConfig = $this->getSportsForGender($gender)) {
                            $sports = array();
                            foreach ($sportsConfig as $key => $sportData) {
                                $image = "modules/{$this->id}/images/".
                                    (isset($sportData['ICON']) ? $sportData['ICON'] : strtolower($sportData['TITLE'])).
                                    $this->imageExt;
                                $sport = array(
                                    'title' =>$sportData['TITLE'],
                                    'img'   =>$image,
                                    'url'   =>$this->buildURL('sport', array('sport' => $key))
                                );
                                $sports[] = $sport;
                            }

                            $tabs[] = $gender;
                            $this->assign($gender. 'SportsTitle', $sportsData['TITLE']);
                            $this->assign($gender.'Sports', $sports);
                        }
                    }
                }
                
                $bookmarkData = $this->getNavData('bookmarks');
                
                //get bookmarks
                $bookmarks = array();
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $bookmarksData = $this->getBookmarks();
                    foreach ($bookmarksData as $bookmark) {
                        parse_str(stripslashes($bookmark), $params);
                        if (isset($params['sport']) && ($sportData = $this->getSportData($params['sport']))) {
                            $image = "modules/{$this->id}/images/".
                                (isset($sportData['ICON']) ? $sportData['ICON'] : strtolower($sportData['TITLE'])).
                                $this->imageExt;
                            $bookmarks[] = array(
                                'title' => $sportData['GENDER_TITLE'],
                                'img'   => $image,
                                'url'   => $this->buildURL('sport', array('sport' => $params['sport']))
                            );
                        }
                    }

                    $tabs[] = 'bookmarks';
                }
                
                $this->assign('placeholder', $this->getLocalizedString('SEARCH_TEXT'));
                $this->assign('bookmarksTitle', $bookmarkData['TITLE']);
                $this->assign('bookmarks', $bookmarks);
                $this->assign('tabs', $tabs);
                $this->enableTabs($tabs);
                break;
                
            case 'pane':
                if ($this->ajaxContentLoad) {
                    $section = 'topnews';
                    
                    $newsFeed = $this->getNewsFeed($section);
                    $newsFeed->setStart(0);
                    $newsFeed->setLimit($this->maxPerPane);
                    
                    $items = $newsFeed->items();
                    $this->setLogData($section, $newsFeed->getTitle());
                    
                    $stories = array();
                    $options = array('section' => $section, 'addBreadcrumb'=>false);

                    foreach ($items as $story) {
                        $stories[] = $this->linkForItem($story, $options);
                    }
                    $this->assign('showImages', $this->showImages);
                    $this->assign('stories', $stories);
                }
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addInternalJavascript('/common/javascript/lib/paneStories.js');
                break;
        }
    }
}
