<?php

class SocialShellModule extends ShellModule {

    protected $id = 'social';
    protected $feeds = array();
    
    public function getAllControllers() {
        $controllers = array();
        
        $feeds = $this->loadFeedData();
        
        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $controllers[$feed] = SocialDataModel::factory($modelClass, $feedData);
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
}