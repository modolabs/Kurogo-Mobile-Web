<?php

class AthleticsWebModule extends WebModule {

    protected $id = 'athletics';
    protected static $defaultModel = 'AthleticsDataModel';
    protected $feeds = array();
    protected $timezone;
    protected $maxPerPage = 10;
    protected $showImages = true;
    protected $showPubDate = false;
    protected $showAuthor = false;
    protected $showLink = false;
    
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
        return mb_convert_encoding($string, 'HTML-ENTITIES', $this->feed->getEncoding());
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
    
    protected function timeText($event, $timeOnly=false) {
        if ($timeOnly) {
            if ($event->get_end() - $event->get_start() == -1) {
                return DateFormatter::formatDate($event->get_start(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            } else {
                return DateFormatter::formatDateRange($event->get_range(), DateFormatter::NO_STYLE, DateFormatter::SHORT_STYLE);
            }
        } else {
            return DateFormatter::formatDateRange($event->get_range(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
        }
    }
    
    protected function linkForScheduleItem(KurogoObject $event, $data=null) {
        $subtitle = $this->timeText($event);
        if ($briefLocation = $event->get_location()) {
            $subtitle .= " | $briefLocation";
        }
      
        $options = array(
            'id'   => $event->get_uid(),
            'time' => $event->get_start()
        );
        if (isset($data['section'])) {
            $options['section'] = $data['section'];
        }
        $url = $this->buildBreadcrumbURL('schedule_detail', $options, true);

        return array(
            'url'       => $url,
            'title'     => $event->get_summary(),
            'subtitle'  => $subtitle
        );
    }
    
    protected function valueForType($type, $value) {
        $valueForType = $value;
  
        switch ($type) {
            case 'datetime':
                $valueForType = DateFormatter::formatDateRange($value, DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE);
                if ($value instanceOf TimeRange) {
                    $timeString = DateFormatter::formatDateRange($value, DateFormatter::NO_STYLE, DateFormatter::MEDIUM_STYLE);
                    $valueForType .= "<br />\n" . $timeString;
                }
            break;

        case 'url':
            $valueForType = str_replace("http://http://", "http://", $value);
            if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
                $valueForType = 'http://'.$valueForType;
            }
            break;
        
        case 'phone':
            $valueForType = PhoneFormatter::formatPhone($value);
            break;
      
        case 'email':
            $valueForType = str_replace('@', '@&shy;', $value);
            break;
        
        case 'category':
            $valueForType = $this->formatTitle($value);
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
    
    protected function formatEventDetail(ICalEvent $event) {
        $calendarFields = $this->getModuleSections('schedule-detail');
        $fields = array();

        foreach ($calendarFields as $key => $info) {
            $field = array();
            $value = $event->get_attribute($key);
            if (!$value) {
                continue;
            }
            if (isset($info['label'])) {
                $field['label'] = $info['label'];
            }
            
            if (isset($info['class'])) {
                $field['class'] = $info['class'];
            }
            if (isset($info['type'])) {
                $field['title'] = $this->valueForType($info['type'], $value);
                $field['url']   = $this->urlForType($info['type'], $value);
            } else {
                $field['title'] = nl2br($value);
            }
            $fields[] = $field;
        }
        return $fields;
    }
    
    protected function getSportData($section) {
    
        $data = isset($this->feeds[$section]) ? $this->feeds[$section] : '';
        if (!$data) {
            throw new KurogoDataException('Unable to load data for sport '. $section);
        }
        
        return $data;
        
    }
    
    protected function getSportLinks($section) {

        $links = array();
        
        //each section in the sport config is used. This permits easy extension
        $feedData = $this->getModuleSections($section);
        foreach ($feedData as $type=>$data) {
            $links[$type] = array(
                'title' => $this->getLocalizedString(strtoupper($type) . '_TITLE'),
                'url'   => $this->buildBreadcrumbURL($type, array('section'=>$section))
            );
        }

        return $links;        
    }
    
    
    protected function getFeed($section, $type = '') {
        $sportData = $this->getSportData($section);
        $feedData = $this->getModuleSections($section);

        //make sure the type (section) is there in the config
        if ($type && isset($feedData[$type])) {
            $feedData = $feedData[$type];
            if (!isset($feedData['TITLE'])) {
                $feedData['TITLE'] = $sportData['TITLE'];
            }
            
            $handelDataModel = ''; //chose the multiple types of dataModel
            
            switch (strtoupper($type)) {
                case 'NEWS':
                    if (!isset($feedData['PARSER_CLASS'])) {
                        $feedData['PARSER_CLASS'] = 'RSSDataParser';
                    }
                    $handelDataModel = 'NewsDataModel';
                    
                    break;
                    
                case 'SCHEDULE':
                    includePackage('Calendar');
                    if (!isset($feedData['PARSER_CLASS'])) {
                        $feedData['PARSER_CLASS'] = 'ICSDataParser';
                    }
                    $handelDataModel = 'CalendarDataModel';
                    
                    break;
                    
                case 'SCORES':
                    break;
            }
            if (!$handelDataModel) {
                $handelDataModel = self::$defaultModel;
            }
            $this->feed = $handelDataModel::factory($handelDataModel, $feedData);
            return $this->feed;
        }
        
        return null;
    }
    
    protected function initialize() {

        $this->feeds      = $this->loadFeedData();
        $this->timezone = Kurogo::siteTimezone();
        
    }    

    protected function initializeForPage() {
        
        switch($this->page) {
            case 'news':
                $this->maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
                $section = $this->getArg('section');
                $start = $this->getArg('start', 0);
                
                $feed = $this->getFeed($section, $this->page);
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
                $storyID = $this->getArg('storyID', false);
                $storyPage = $this->getArg('storyPage', '0');
                $feed = $this->getFeed($section, 'news');
                
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
                
                $this->enablePager($content, $this->feed->getEncoding(), $storyPage);
                $this->assign('date',   $date);
                $this->assign('title',  $this->htmlEncodeFeedString($story->getTitle()));
                $this->assign('author', $this->htmlEncodeFeedString($story->getAuthor()));
                $this->assign('image',  $this->getImageForStory($story));
                $this->assign('link',   $story->getLink());
                $this->assign('showLink', $this->showLink);
                break;
            case 'scores':
                
                break;
                
            case 'schedule':
                $current = $this->getArg('time', time(), FILTER_VALIDATE_INT);
                $limit    = $this->getArg('limit', 20);
                $section = $this->getArg('section');
                $feed = $this->getFeed($section, $this->page);
                
                $this->setLogData($section, $feed->getTitle());
                $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
                $start->setTime(0,0,0);
                
                $feed->setStartDate($start);
                $feed->setLimit($limit);
                $iCalEvents = $feed->items();
                
                $options = array(
                    'section' => $section
                );
                $events = array();
                foreach($iCalEvents as $iCalEvent) {
                    $events[] = $this->linkForScheduleItem($iCalEvent, $options);
                }
                
                $this->assign('events', $events);
                break;
             
            case 'schedule_detail':
                $id = $this->getArg('id');
                $section = $this->getArg('section');
                $time = $this->getArg('time', time(), FILTER_VALIDATE_INT);
                
                $feed = $this->getFeed($section, 'schedule');
                if ($event = $feed->getItem($id, $time)) {
                    $this->assign('event', $event);
                } else {
                    throw new KurogoDataException($this->getLocalizedString('ERROR_EVENT_NOT_FOUND'));
                }
                
                $this->setLogData($section . ':' . $event->get_uid(), $event->get_summary());

                $fields = $this->formatEventDetail($event);
                $this->assign('fields', $fields);
                break;
                
            case 'sport':
                $section = $this->getArg('section');
                $sportData = $this->getSportData($section);
                
                $this->setPageTitles($sportData['TITLE']);
                $this->assign('sportLinks', $this->getSportLinks($section));
                break;

            case "index":
                $sports = array();

                foreach ($this->feeds as $section => $sportData) {
                    $sport = array(
                        'title' =>$sportData['TITLE'],
                        'url'   =>$this->buildBreadcrumbURL('sport', array('section'=>$section))
                    );
                    $sports[] = $sport;
                }
                $this->assign('sports', $sports);
                break;
        }
    }
}
