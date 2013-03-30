<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('Calendar');

define('DAY_SECONDS', 24*60*60);

class CalendarShellModule extends ShellModule {

    protected $id = 'calendar';
    protected $feeds = array();
    protected $timezone;
    protected static $defaultModel = 'CalendarDataModel';
  
    public function getFeed($index) {
        $feeds = $this->loadFeedData();
        if (isset($feeds[$index])) {
            $feedData = $feeds[$index];
            if (isset($feedData['CONTROLLER_CLASS'])) {
				$modelClass = $feedData['CONTROLLER_CLASS'];
			} else {
				$modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
			}
			
			$controller = CalendarDataModel::factory($modelClass, $feedData);
			
            return $controller;
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString("ERROR_NO_CALENDAR_FEED", $index));
        }
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
