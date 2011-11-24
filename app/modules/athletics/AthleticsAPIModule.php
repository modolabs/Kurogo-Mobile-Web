<?php

Kurogo::includePackage('Athletics');

class AthleticsAPIModule extends APIModule
{
    protected $id = 'athletics';
    protected $vmin = 1;
    protected $vmax = 1;

    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected static $defaultNewsModel = 'NewsDataModel';
    
    protected $maxPerPage = 10;
    protected $feeds;
    protected $navFeeds;
    
    public function  initializeForCommand() {

        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        
        switch ($this->command) {
            case 'sports':
                // sports
                $gender = $this->getArg('gender');
                
                $tabData = $this->getNavData($gender);
                $sportsConfig = $this->getSportsForGender($gender);
                
                $sports = array();
                foreach ($sportsConfig as $key => $sportData) {
                    $sports[$key] = array('title' => $sportData['TITLE']);
                }
                
                $response = array(
                    'sports' => $sports,
                    'sporttitle'    => $tabData['TITLE'],
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;

            case 'news':
                // news 
                $sport = $this->getArg('sport', 'topnews'); // COULD BE TOPNEWS
                $start = $this->getArg('start', 0);
                $limit = $this->getArg('limit', 0);
                $mode = $this->getArg('mode');
                
                if ($sport == 'topnews') {
                    $sportData = $this->getNavData($sport);
                } else {
                    $sportData = $this->getSportData($sport);
                }
                
                $newsFeed = $this->getNewsFeed($sport);
                $limit = $limit ? $limit : $this->maxPerPage;
                
                $newsFeed->setStart($start);
                $newsFeed->setLimit($limit);
                $items = $newsFeed->items($start, $limit);
                
                $totalItems = $newsFeed->getTotalItems();

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, $mode);
                }
                
                $response = array(
                    'stories' => $stories,
                    'moreStories' => ($totalItems - $start - $limit)
                );

                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;

            case 'schedule':
                // schedule
                $sport = $this->getArg('sport');
                $sportData = $this->getSportData($sport);
                
                $count  = 0;
                if ($scheduleFeed = $this->getScheduleFeed($sport)) {
                    $scheduleItems = array();
                    
                    if ($events = $scheduleFeed->items()) {
                        foreach ($events as $event) {
                            $count++;
                            $scheduleItems[] = $this->formatSchedule($event);
                        }
                    }
                }
                
                $response = array(
                    'schedules' => $scheduleItems,
                    'sport'   => $sport,
                    'sporttitle' => $sportData['TITLE'],
                    'total' => $count,
                    'return' => $count
                );
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }
    
    protected function getImageForStory($story) {
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
        
        return null;
    }
    
    protected function formatStory($story, $mode) {
        $item = array(
            'GUID'        => $story->getGUID(),
            'link'        => $story->getLink(),
            'title'       => strip_tags($story->getTitle()),
            'description' => $story->getDescription(),
            'pubDate'     => strtotime($story->getPubDate())
        );

        // like in the web module we
        // use the existance of GUID
        // to determine if we have content
        if($story->getGUID()) {
            $item['GUID'] = $story->getGUID();
            if($mode == 'full') {
                $item['body'] = $story->getContent();
            }
            $item['hasBody'] = TRUE;
        } else {
            $item['GUID'] = $story->getLink();
            $item['hasBody'] = FALSE;
        }

        $image = $this->getImageForStory($story);
        if ($image && $image['src']) {
            $item['image'] = $image;
        }
        $item['author'] = $story->getAuthor() ? $story->getAuthor() : '';
        
        return $item;
    }
    
    protected function timeText(AthleticEvent $event, $timeOnly=false) {
        return DateFormatter::formatDateRange($event->getRange(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
    }
    
    protected function formatSchedule(KurogoObject $event) {
        $item = array(
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
        
        return $item;
    }
    
    protected function getNavData($tab) {
    
        $data = isset($this->navFeeds[$tab]) ? $this->navFeeds[$tab] : '';
        
        if (!$data) {
            throw new KurogoConfigurationException('Unable to load data for nav '. $tab);
        }
        
        return $data;
        
    }
    
    //copy from athleticsWebModule
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
    
    protected function getNewsFeed($sport, $gender=null) {
        if ($sport=='topnews') {
            $feedData = $this->getNavData('topnews');
        } else {
            $feedData = $this->getOptionalModuleSection($sport, 'feeds');
        }
        
        if (isset($feedData['DATA_RETRIEVER']) || isset($feedData['BASE_URL'])) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultNewsModel;
            $newsFeed = DataModel::factory($dataModel, $feedData);
            return $newsFeed;
        }
        
        return null;
    }
    
    //copy from athleticsWebModule
    protected function getScheduleFeed($sport) {
    
        if ($feedData = $this->getOptionalModuleSection($sport, 'schedule')) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'AthleticEventsDataModel';
            $this->scheduleFeed = AthleticEventsDataModel::factory($dataModel, $feedData);
            return $this->scheduleFeed;
        }
        
        return null;
    }
}
