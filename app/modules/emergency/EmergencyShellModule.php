<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class EmergencyShellModule extends ShellModule {

    protected $id='emergency';
    
    public function getAllControllers() {
        $controllers = array();
        
        $config = $this->loadFeedData();
        if(isset($config['contacts'])) {
            $modelClass = isset($config['contacts']['MODEL_CLASS']) ? $config['contacts']['MODEL_CLASS'] : 'EmergencyContactsDataModel';
            $controllers[] = EmergencyContactsDataModel::factory($modelClass, $config['contacts']);
        }
        
        if(isset($config['notice'])) {
            $modelClass = isset($config['notice']['MODEL_CLASS']) ? $config['notice']['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
            $controllers[] = EmergencyNoticeDataModel::factory($modelClass, $config['notice']);
        }
        
        return $controllers;
    }
    
    protected function initializeForCommand() {
    
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

    public function getStaticNotificationContexts() {
        return array('notice');
    }

    public function getUpdatesForStaticContext($context, $platform, $lastCheckTime) {
        $results = array();

        // don't send platform specific messages
        if ($platform === null) {
            $controller = $this->getNoticeController();
            if ($controller) {
                foreach ($controller->getAllEmergencyNotices() as $notice) {
                    if ($notice['unixtime'] >= $lastCheckTime) {
                        $message = new KurogoMessage($notice['title'], 'kurogo', $this->getConfigModule());
                        $message->extendedBody = $notice['text'];
                        $results[] = $message;
                    }
                }
            }
        }
        return $results;
    }

    private function getNoticeController() {
        $config = $this->loadFeedData();
        if (isset($config['notice'])) {
            $modelClass = isset($config['notice']['MODEL_CLASS']) ? $config['notice']['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
            return EmergencyNoticeDataModel::factory($modelClass, $config['notice']);
        }
        return null;
    }
}
