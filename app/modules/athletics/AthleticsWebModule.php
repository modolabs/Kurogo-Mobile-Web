<?php

class AthleticsWebModule extends WebModule {

    protected $id = 'athletics';
    protected static $defaultModel = 'AthleticsDataModel';
    protected $feeds = array();
    protected $feedIndex = 0;
    protected $maxPerPage = 10;
    protected $showImages = true;
    protected $showPubDate = false;
    protected $showAuthor = false;
    protected $showLink = false;
    
    public function linkForItem($section, $sport, $options = array()) {
        $title = isset($sport['GENDERS']) && $sport['GENDERS'] ? $sport['SPORT_NAME'] . '|' . $sport['GENDERS'] : $sport['SPORT_NAME'];
        $options['section'] = $section;
        return array(
        'url'       => $this->buildBreadcrumbURL('sport', $options, true),
        'title'     => $title
        );
    }
    
    protected function htmlEncodeFeedString($string) {
        return mb_convert_encoding($string, 'HTML-ENTITIES', $this->feed->getEncoding());
    }
    
    public function linkForNewsItem($story) {
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
        $link['url'] = $story->getProperty('link');
        return $link;
    }
    
    protected function urlForFeatures($sport, $type, $options, $data = array()) {
        $url = '';
        $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
        $page = strtolower($type);
        $url = isset($sport[$type]) && $sport[$type] ? $this->buildBreadcrumbURL($page, $options, $addBreadcrumb) : '';
        return $url;
    }
    
    protected function linkForSport($sport, $options) {
        $result = array();
        $result[] = array(
            'title' => 'Sport Name:' . $sport['SPORT_NAME']
        );
        $result[] = array(
            'title' => 'Genders:' . $sport['GENDERS']
        );
        $result[] = array(
            'title' => 'News',
            'url'   => $this->urlForFeatures($sport, 'NEWS', $options)
        );
        $result[] = array(
            'title' => 'Schedule',
            'url'   => $this->urlForFeatures($sport, 'SCHEDULE', $options)
        );
        $result[] = array(
            'title' => 'Scores',
            'url'   => $this->urlForFeatures($sport, 'SCORES', $options)
        );
        return $result;
    }
    
    protected function getFeed($section, $type = '') {
        $feeds = $this->loadFeedData();
        if ($type && isset($feeds[$section]) && $feeds[$section] && isset($feeds[$section][$type]) && $feeds[$section][$type]) {
            $feed = $feeds[$section];
            $feedData = array();
            
            $feedData['TITLE'] = $feeds[$section]['SPORT_NAME'];
            switch ($type) {
                case 'NEWS':
                    $feedData['BASE_URL'] = $feeds[$section][$type];
                    $feedData['RETRIEVER_CLASS'] = 'URLDataRetriever';
                    $feedData['PARSER_CLASS'] = 'RSSDataParser';
                    break;
                case 'SCHEDULE':
                    break;
                case 'SCORES':
                    break;
            }
            
            $controller = AthleticsDataModel::factory('AthleticsDataModel', $feedData);
            return $controller;
        }
        return null;
    }
    
    protected function initialize() {

        $this->feeds      = $this->loadFeedData();
        $this->maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
        
        if (count($this->feeds)==0) {
            return;
        }
        
        if (in_array($this->page, array('news', 'scores', 'schedule'))) {
            $this->feedIndex = $this->getArg('section', 0);
            $feedData = $this->feeds[$this->feedIndex];
            $this->showImages = isset($feedData['SHOW_IMAGES']) ? $feedData['SHOW_IMAGES'] : true;
            $this->showPubDate = isset($feedData['SHOW_PUBDATE']) ? $feedData['SHOW_PUBDATE'] : false;
            $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : false;
            $this->showLink = isset($feedData['SHOW_LINK']) ? $feedData['SHOW_LINK'] : false;
            switch ($this->page) {
                case 'news':
                    $this->feed = $this->getFeed($this->feedIndex, 'NEWS');
                    break;
                case 'scores':
                    $this->feed = $this->getFeed($this->feedIndex, 'SCORES');
                    break;
                case 'schedule':
                    $this->feed = $this->getFeed($this->feedIndex, 'SCHEDULE');
                    break;
                default:
                    break;
            }
        }
    }    

    protected function initializeForPage() {
        
        switch($this->page) {
            case 'news':
                $start = $this->getArg('start', 0);
                if (!$this->feed) {
                    throw new KurogoDataException('sport:'. $section .' not found');
                }
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
                
                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->linkForNewsItem($story);
                }
                $this->setPageTitle('News');
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
            case 'scores':
                break;
            case 'schedule':
                break;
            case 'sport':
                $section = $this->getArg('section', 0);
                $feed = isset($this->feeds[$section]) ? $this->feeds[$section] : '';
                if (!$feed) {
                    throw new KurogoDataException('sport:'. $section .' not found');
                }
                $options['section'] = $section;
                $sport = $this->linkForSport($feed, $options);
                
                $this->setPageTitle($feed['SPORT_NAME']);
                $this->assign('sport', $sport);
                break;
            case "index":
                $sports = array();
                foreach ($this->feeds as $section => $sport) {
                    $sports[] = $this->linkForItem($section, $sport);
                }
                $this->assign('sports', $sports);
                break;
        }
    }
}
