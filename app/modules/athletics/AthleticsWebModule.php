<?php

class AthleticsWebModule extends WebModule {

    protected $id = 'athletics';
    protected static $defaultModel = 'AthleticsDataModel';
    protected $feeds = array();
    protected $maxPerPage = 10;
    protected $showImages = true;
    protected $showPubDate = false;
    protected $showAuthor = false;
    protected $showLink = false;
    
    
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
            
            switch ($type) {
                case 'NEWS':
                    if (!isset($feedData['PARSER_CLASS'])) {
                        $feedData['PARSER_CLASS'] = 'RSSDataParser';
                    }
                    break;
                case 'SCHEDULE':
                    includePackage('Calendar');
                    if (!isset($feedData['PARSER_CLASS'])) {
                        $feedData['PARSER_CLASS'] = 'ICSDataParser';
                    }
                    break;
                case 'SCORES':
                    break;
            }
            
            $this->feed = AthleticsDataModel::factory('AthleticsDataModel', $feedData);
            return $this->feed;
        }
        
        return null;
    }
    
    protected function initialize() {

        $this->feeds      = $this->loadFeedData();
        
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
                
                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->linkForNewsItem($story);
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
                
            case 'scores':
                break;
                
            case 'schedule':
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
