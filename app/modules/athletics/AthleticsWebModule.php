<?php

includePackage('Athletics');
class AthleticsWebModule extends WebModule {

    protected $id = 'athletics';
    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected $feeds = array();
    protected $navFeeds = array();
    protected $maxPerPage = 10;
    protected $showImages = true;
    protected $showPubDate = false;
    protected $showAuthor = false;
    protected $showLink = false;
    protected $newsFeed;
    protected $scheduleFeed;
    
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
            if ($image = $story->getImage()) {
                return array(
                    'src'    => $image->getURL(),
                    'width'  => $image->getProperty('width'),
                    'height' => $image->getProperty('height'),
                );
            } elseif ($image = $story->getChildElement('MEDIA:CONTENT')) {
                return array(
                    'src'    => $image->getAttrib('URL'),
                    'width'  => $image->getAttrib('WIDTH'),
                    'height' => $image->getAttrib('HEIGHT'),
                );
            }
        }
        return null;
    }
    
    protected function linkForNewsItem($story, $data = array()) {
        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        $image = $this->getImageForStory($story);

        $link = array(
            'title'   => $this->htmlEncodeFeedString($story->getTitle()),
            'pubDate' => $date,
            'author'  => $this->htmlEncodeFeedString($story->getAuthor()),
            'subtitle'=> $this->htmlEncodeFeedString($story->getDescription()),
            'img'     => $image && isset($image['src']) && $image['src'] ? $image['src'] : '',
        );
        
        if ($storyID = $story->getGUID()) {
            $options = array(
                'storyID'=>$storyID
            );    
            if (isset($data['section'])) {
                $options['section'] = $data['section'];
            }
    
            $link['url'] = $this->buildBreadcrumbURL('news_detail', $options, true);
        } elseif ($url = $story->getProperty('link')) {
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
        
        return array(
            'title'=>$event->getTitle(),
            'subtitle'=>sprintf("%s - %s", $this->timeText($event), $event->getLocation()),
            'url'=> $this->buildBreadcrumbURL('schedule_detail', $options, true)
        );
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
        $allFieldsValue  = $this->getFieldsForSechedule($event);
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

    protected function getFieldsForSechedule(AthleticEvent $event) {
        return array(
            'title'         => $event->getTitle(),
            'id'            => $event->getID(),
            'sport'         => $event->getSport(),
            'sportName'     => $event->getSportName(),
            'gender'        => $event->getGender(),
            'start'         => $this->timeText($event),
            'pastStatus'    => $event->getStartTime() > time() ? false : true,
            'location'      => $event->getLocation(),
            'link'          => $event->getLink()
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
            throw new KurogoDataException('Unable to load data for sport '. $sport);
        }
    }
    
    protected function getNavData($tab) {
    
        $data = isset($this->navFeeds[$tab]) ? $this->navFeeds[$tab] : '';
        if (!$data) {
            throw new KurogoDataException('Unable to load data for nav '. $tab);
        }
        
        return $data;
        
    }

    protected function getScheduleFeed($sport) {
    
        if ($feedData = $this->getOptionalModuleSection($sport, 'schedule')) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'AthleticEventsDataModel';
            $this->scheduleFeed = AthleticEventsDataModel::factory($dataModel, $feedData);
            return $this->scheduleFeed;
        }
        
        return null;
    }
    
    protected function getNewsFeed($sport, $gender=null) {
        if ($sport=='topnews') {
            $feedData = $this->getNavData('topnews');
        } else {
            $feedData = $this->getOptionalModuleSection($sport, 'feeds');
        }
        
        if (isset($feedData['DATA_RETRIEVER']) || isset($feedData['BASE_URL'])) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'NewsDataModel';
            $this->newsFeed = DataModel::factory($dataModel, $feedData);
            return $this->newsFeed;
        }
        
        return null;
    }
    
    protected function initialize() {

        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        
    }    

    protected function initializeForPage() {
        
        switch($this->page) {
            case 'news':
                $this->maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
                $section = $this->getArg('section');
                $start = $this->getArg('start', 0);
                
                $feed = $this->getFeed();
                $feed->setStart($start);
                $feed->setLimit($this->maxPerPage);

                $items = $feed->items();
                $totalItems = $feed->getTotalItems();
                $this->setLogData($section, $feed->getTitle());
                
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
                /*
                foreach ($items as $story) {
                    $stories[] = $this->linkForNewsItem($story, $options);
                }
                */

                KurogoDebug::debug($items, true);
                
                foreach ($items as $event) {
                    $subtitle = '';
                    $eventTime = $event->getDateTime();
                    $subtitle .= $eventTime ? $eventTime->format('Y-n-j H:i:s') : '';
                    $subtitle .= $event->getAllDay() ? ' All Day' : '';
                    $subtitle .= $event->getTBA() ? ' TBA' : '';
                    $subtitle .= $event->getLocation() ? ' | Location:' . $event->getLocation() : '';
                    $stories[] = array(
                        'title' => $event->getSport(),
                        'subtitle' => $subtitle,
                    );
                }
                
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupNewsListing();');
        
                $this->assign('maxPerPage',     $this->maxPerPage);
                $this->assign('stories',        $stories);
                $this->assign('isHome',         true);
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
                
                if (!$story = $feed->getItem($storyID)) {
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
                
                $this->enablePager($content, $this->newsFeed->getEncoding(), $storyPage);
                $this->assign('date',   $date);
                $this->assign('title',  $this->htmlEncodeFeedString($story->getTitle()));
                $this->assign('author', $this->htmlEncodeFeedString($story->getAuthor()));
                $this->assign('image',  $this->getImageForStory($story));
                $this->assign('link',   $story->getLink());
                $this->assign('showLink', $this->showLink);
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
                $id = $this->getArg('id', '');
                $sportData = $this->getSportData($sport);
                
                $scheduleFeed = $this->getScheduleFeed($sport);
                if ($schedule = $scheduleFeed->getItem($id)) {
                    $this->assign('schedule', $schedule);
                } else {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_EVENT_NOT_FOUND'));
                }
                $this->setLogData($sport . ':' . $schedule->getID(), $schedule->getSport());
                
                $fields = $this->formatScheduleDetail($schedule);
                $schedule = $this->getFieldsForSechedule($schedule);
                $this->assign('schedule', $schedule);
                $this->assign('fields', $fields);
                break;
                
            case 'sport':
                $sport = $this->getArg('sport', '');
                
                $previous = array();
                $next = array();
                $sportData = $this->getSportData($sport);
                $this->assign('sportTitle', $sportData['TITLE']);
                $this->setPageTitles($sportData['TITLE']);

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
                    $newsFeed->setLimit($this->maxPerPage);

                    $options = array(
                        'section'=>$sport
                    );
                    $newsItems = array();
                    $items = $newsFeed->items();
                    foreach ($items as $story) {
                        $newsItems[] = $this->linkForNewsItem($story, $options);
                    }
                    
                    $this->assign('newsItems', $newsItems);
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
                
                //get top news
                if ($newsFeedData = $this->getNavData('topnews')) {
                    $topNews = array();
                    $limit = isset($newsFeedData['LIMIT']) ? $newsFeedData['LIMIT'] : $this->maxPerPage;
                    
                    $newsFeed = $this->getNewsFeed('topnews');
                    $newsFeed->setLimit($limit);

                    $options = array(
                        'section'=>'topnews'
                    );
                    $items = $newsFeed->items();
                    foreach ($items as $story) {
                        $topNews[] = $this->linkForNewsItem($story, $options);
                    }
                    $tabs[] = $newsFeedData['TITLE'];
                    $this->assign('topNewsTitle', $newsFeedData['TITLE']);
                    $this->assign('topNews', $topNews);
                }
                
                //get sports for each gender
                foreach (array('men','women') as $gender) {
                    $sportsData = $this->getNavData($gender);
                    if ($sportsConfig = $this->getSportsForGender($gender)) {
                        $sports = array();
                        foreach ($sportsConfig as $key => $sportData) {
                            $sport = array(
                                'title' =>$sportData['TITLE'],
                                'url'   =>$this->buildURL('sport', array('sport' => $key))
                            );
                            $sports[] = $sport;
                        }
                    
                        $tabs[] = $sportsData['TITLE'];
                        $this->assign($gender. 'SportsTitle', $sportsData['TITLE']);
                        $this->assign($gender.'Sports', $sports);
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
                            $bookmarks[] = array(
                                'title' => $sportData['TITLE'],
                                'url'   => $this->buildURL('sport', array('sport' => $params['sport']))
                            );
                        }
                    }
                }
                
                $tabs[] = $bookmarkData['TITLE'];
                
                $this->assign('bookmarksTitle', $bookmarkData['TITLE']);
                $this->assign('bookmarks', $bookmarks);
                $this->assign('tabs', $tabs);
                $this->enableTabs($tabs);
                
                break;
        }
    }
}
