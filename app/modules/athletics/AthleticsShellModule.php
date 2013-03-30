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

class AthleticsShellModule extends ShellModule {

    protected $id = 'athletics';
    protected static $defaultEventModel = 'AthleticEventsDataModel';
    protected $feeds = array();
    protected $navFeeds = array();
    
    public function loadScheduleData() {
        $scheduleFeeds = $this->getModuleSections('schedule');
        $default = $this->getOptionalModuleSection('schedule','module');
        foreach ($scheduleFeeds as $index=>&$feedData) {
            $feedData = array_merge($default, $feedData);
        }
        return $scheduleFeeds;
    }
    
    protected function getScheduleFeed($sport) {
        
        $scheduleData = $this->loadScheduleData();
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
            $newsFeed = DataModel::factory($dataModel, $feedData);
            return $newsFeed;
        }
        
        return null;
    }
    
    protected function getNavData($tab) {
        $data = isset($this->navFeeds[$tab]) ? $this->navFeeds[$tab] : '';
        if (!$data) {
            throw new KurogoDataException($this->getLocalizedString('ERROR_NAV', $tab));
        }
        
        return $data;
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
    
    public function getAllControllers() {
        $controllers = array();
        
        $controllers[] = $this->getNewsFeed('topnews');
        
        foreach (array('men', 'women', 'coed') as $gender) {
            if ($sportsConfig = $this->getSportsForGender($gender)) {
                foreach ($sportsConfig as $key => $sportData) {
                    if ($newsFeed = $this->getNewsFeed($key)) {
                        $controllers[] = $newsFeed;
                    }
                    if ($scheduleFeed = $this->getScheduleFeed($key)) {
                        $controllers[] = $scheduleFeed;
                    }
                }
            }
        }
        
        return $controllers;
    }
    
    protected function initializeForCommand() {
        $this->feeds = $this->loadFeedData();
        $this->navFeeds = $this->getModuleSections('page-index');
        
        switch($this->command) {
            case 'fetchAllData':
                $this->preFetchAllData();
                
                return 0;
                
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}
