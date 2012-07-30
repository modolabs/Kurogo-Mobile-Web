<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
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
    protected $vmax = 1;
    protected $imageExt = ".png";

    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected static $defaultNewsModel = 'NewsDataModel';
    
    protected $maxPerPage = 10;
    protected $feeds;
    protected $navFeeds;

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
    
    public function  initializeForCommand() {

        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        $responseVersion = $this->requestedVersion < 2 ? 1 : 2;
        
        switch ($this->command) {
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
                $this->setResponseVersion(1);
                break;
            case 'sports':
                // sports
                $gender = $this->getArg('gender');
                
                if($tabData = $this->getNavData($gender)) {
                    $sportsConfig = $this->getSportsForGender($gender);

                    $sports = array();
                    foreach ($sportsConfig as $key => $sportData) {
                        $image = FULL_URL_BASE . "modules/{$this->configModule}/images/" .
                            (isset($sportData['ICON']) ? $sportData['ICON'] : strtolower($sportData['TITLE'])) .
                            $this->imageExt;
                        $sports[] = array('key'=>$key, 'title' => $sportData['TITLE'], 'icon' => $image);
                    }

                    $response = array(
                        'sports' => $sports,
                        'sporttitle'    => $tabData['TITLE'],
                    );
                }else {
                    $response = null;
                }
                
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
                $this->setResponseVersion(1);
                
                break;

            case "search":
                $searchTerms = $this->getArg('filter');
                $start = $this->getArg('start', 0);
                $section = $this->getArg('section', 'topnews');
                $mode = $this->getArg('mode');

                $newsFeed = $this->getNewsFeed($section);

                $newsFeed->setStart($start);
                $newsFeed->setLimit($this->maxPerPage);

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
                'width'  => $image->getWidth(),
                'height' => $image->getHeight()
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
        
        $fieldConfig = $this->getAPIConfigData('schedule-detail');
        foreach ($fieldConfig as $field => $fieldInfo) {
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
