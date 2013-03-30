<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('News');

class AthleticsAPIModule extends APIModule
{
    protected $id = 'athletics';
    protected $vmin = 1;
    protected $vmax = 3;
    protected $imageExt = ".png";

    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected static $defaultNewsModel = 'AthleticNewsDataModel';
    
    protected $maxPerPage = 10;
    protected $feeds;
    protected $navFeeds;
    
    protected $fieldConfig;

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

    protected function loadFeedData() {
        $feeds = parent::loadFeedData();
        foreach ($feeds as $sport=>&$sportData) {
            $localizedKey = sprintf("%s_BOOKMARK", strtoupper($sportData['GENDER']));
            $sportData['GENDER_TITLE'] = $this->getLocalizedString($localizedKey, $sportData['TITLE']);
        }

        return $feeds;
    }
    
    public function  initializeForCommand() {

        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        $responseVersion = in_array($this->requestedVersion, array(1,2,3)) ? $this->requestedVersion : 1;
        
        switch ($this->command) {
            case 'frontpage':
                // for native apps, returns whether Top News tab contains News and/or All Sports Schedule

                $response = array();

                if ($newsFeedData = $this->getNavData('topnews')) {
                    $response[] = array('id' => 'topnews', 'title' => $newsFeedData['TITLE']);
                }

                if ($scheduleFeedData = $this->getNavData('allschedule')) {
                    $response[] = array('id' => 'allschedule', 'title' => $scheduleFeedData['TITLE']);
                }
                
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);

            break;
            case 'genders':
                $genders = array();
                $response = array();
                foreach($this->feeds as $feed) {
                    $gender = $feed['GENDER'];
                    if($tabData = $this->getNavData($gender)) {
                    	if (!in_array($gender, $genders)) {
                            $genders[] = $gender;
                            $response[] = array(
                                'key' => $gender,
                                'title' => $tabData['TITLE']
                            );
                        }
                    }
                }
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;
            case 'sports':
                // sports
                $gender = $this->getArg('gender');
                
                if($tabData = $this->getNavData($gender)) {
                    $sportsConfig = $this->getSportsForGender($gender);

                    $sports = array();
                    foreach ($sportsConfig as $key => $sportData) {
                        $image = FULL_URL_BASE . "modules/{$this->id}/images/" .
                            (isset($sportData['ICON']) ? $sportData['ICON'] : strtolower($sportData['TITLE'])) .
                            $this->imageExt;
                        $sports[] = array('key'=>$key, 'title' => $sportData['TITLE'], 'gendertitle'=> $sportData['GENDER_TITLE'], 'icon' => $image);
                    }

                    $response = array(
                        'sports' => $sports,
                        'sporttitle'    => $tabData['TITLE'],
                    );
                }else {
                    $response = null;
                }
                
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                
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
                $this->setResponseVersion($responseVersion);
                
                break;

            case 'schedule':
                // schedule
                $sport = $this->getArg('sport', 'allschedule'); // COULD BE ALLSCHEDULE
                
                $count  = 0;
                if ($scheduleFeed = $this->getScheduleFeed($sport)) {
                    $scheduleItems = array();
                    
                    if ($events = $scheduleFeed->items()) {
                        foreach ($events as $event) {
                            $count++;
                            $scheduleItems[] = $this->formatSchedule($event, $responseVersion);
                        }
                    }
                }
                
                $response = array(
                    'total'        => $count,
                    'returned'     => $count,
                    'displayField' => 'title',
                    'results' => $scheduleItems,
                );
                    
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                
                break;

            case "search":
                $searchTerms = $this->getArg('filter');
                $start = $this->getArg('start', 0);
                $section = $this->getArg('section', 'topnews');
                $mode = $this->getArg('mode');

                $newsFeed = $this->getNewsFeed($section);

                $newsFeed->setStart($start);
                $newsFeed->setLimit($this->maxPerPage);

				$this->setLogData($searchTerms);
                $items = $newsFeed->search($searchTerms);
                $totalItems = $newsFeed->getTotalItems();

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, $mode);
                }

                $response = array(
                    'stories' => $stories,
                    'moreStories' => ($totalItems - $start - $this->maxPerPage)
                );

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }
    
    protected function formatStory($story, $mode) {
        
        $item = array(
            'GUID'        => $story->getGUID(),
            'link'        => $story->getLink(),
            'title'       => Sanitizer::htmlStripTags2UTF8($story->getTitle()),
            'description' => Sanitizer::htmlStripTags2UTF8($story->getDescription()),
            'pubDate'     => $story->getPubTimestamp()
        );

        if($story->getContent()) {
            if($mode == 'full') {
                $item['body'] = $this->cleanContent($story->getContent());
            }
            $item['hasBody'] = TRUE;
        } else {
            $item['hasBody'] = FALSE;
        }

       $thumbnail = $story->getThumbnail();
       if($thumbnail && $thumbnail->getURL()) {
         $key = $this->requestedVersion < 3 ? 'image' : 'thumbnail';
         $item[$key] = array(
           'src'    => $thumbnail->getURL(),
           'width'  => $thumbnail->getWidth(),
           'height' => $thumbnail->getHeight()
         );
       }
       
       if ($this->requestedVersion >= 3) {
           $image = $story->getImage();
           if($image && $image->getURL()) {
               $item['image'] = array(
                    'src'    => $image->getURL(),
                    'width'  => $image->getWidth(),
                    'height' => $image->getHeight()
               );
           }
        }
        $item['author'] = $story->getAuthor() ? $story->getAuthor() : '';
        
        return $item;
    }
    
    protected function timeText(AthleticEvent $event, $timeOnly=false) {
        return DateFormatter::formatDateRange($event->getRange(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
    }
    
    protected function getFiledDataForSchedule(KurogoObject $event) {
        return array(
            'title'         => $event->getTitle(),
            'description'   => $event->getDescription() ? $event->getDescription() : '',
            'id'            => $event->getID(),
            'sport'         => $event->getSport(),
            'sportName'     => $event->getSportName(),
            'gender'        => $event->getGender(),
            'start'         => $event->getStartTime(),
            'pastStatus'    => $event->getStartTime() > time() ? false : true,
            'location'      => $event->getLocation(),
            'link'          => $event->getLink(),
            'allday'        => $event->isAllDay()
        );
    }
    
    protected function formatSchedule(KurogoObject $event, $version) {
    
        $allFieldValue = $this->getFiledDataForSchedule($event);
        $standardAttributes = array('id', 'title', 'description', 'start', 'allday', 'location', 'pastStatus');
        
        $result = array();
        
        foreach ($standardAttributes as $attrib) {
            if (isset($allFieldValue[$attrib])) {
                
                $result[$attrib] = $allFieldValue[$attrib];
            }
        }
        $result['locationLabel'] = '';
 
        if (!$this->fieldConfig) {
            $this->fieldConfig = $this->getModuleSections('api-schedule-detail');
        }
        foreach ($this->fieldConfig as $field => $fieldInfo) {
            if (in_array($field, $standardAttributes) || !isset($allFieldValue[$field]) || !$allFieldValue[$field]) {
                continue;
            }
            
            $id      = self::argVal($fieldInfo, 'id', $field);
            $title   = self::argVal($fieldInfo, 'label', $id);
            $section = self::argVal($fieldInfo, 'section', '');
            $type    = self::argVal($fieldInfo, 'type', '');
            $value   = $allFieldValue[$field];
            
            if ($value) {
                if ($version < 2) {
                    $result[$title] = $value;
                } else {
                    if (!isset($result['field'])) {
                        $result['field'] = array();
                    }
                    $result['field'][] = array(
                        'id'      => $id,
                        'section' => $section,
                        'type'    => $type,
                        'title'   => $title,
                        'value'   => $value
                    );
                }
            }
        }
        
        return $result;
    }
    
    protected function getNavData($tab) {
    
        $data = isset($this->navFeeds[$tab]) ? $this->navFeeds[$tab] : '';
        
        if (!$data) {
            $vars = $this->getOptionalModuleSection("index", "pages");
            $key = "tab_" . $tab;
            if(isset($vars[$key])) {
                $data['TITLE'] = $vars[$key];
            }else {
                $data = null;
            }
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
        } elseif (isset($this->feeds[$sport])) {
            $feedData = $this->feeds[$sport];
        } else {
            throw new KurogoDataException($this->getLocalizedString('ERROR_INVALID_SPORT', $sport));
        }
        
        if (isset($feedData['DATA_RETRIEVER']) || isset($feedData['BASE_URL'])) {
            $dataModel = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultNewsModel;
            $newsFeed = DataModel::factory($dataModel, $feedData);
            return $newsFeed;
        }
        
        return null;
    }
    
    public function loadScheduleData() {
        $scheduleFeeds = $this->getModuleSections('schedule');
        $default = $this->getOptionalModuleSection('schedule','module');
        foreach ($scheduleFeeds as $index=>&$feedData) {
            $feedData = array_merge($default, $feedData);
        }
        return $scheduleFeeds;
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
}
