<?php

includePackage('Video');

class VideoAPIModule extends APIModule {    
    protected $id='video';  // this affects which .ini is loaded
    protected $feeds = array();
    
    public static function cleanVideoArray($videoArray) {
        $cleanArray = array();
        foreach ($videoArray as $key => $value)
        {
            $cleanKey = ltrim($key, "\0*");
            $cleanArray[$cleanKey] = $value;
            if($cleanKey == 'published') {
                $cleanArray['publishedTimestamp'] = $value->getTimestamp(); 
            } 
        }
        //error_log(print_r($cleanArray, true));
        return $cleanArray;
    }
            
    public function initializeForCommand() {
        $this->feeds = $this->loadFeedData();
        
        switch ($this->command) {
            case 'sections':
                error_log(print_r(VideoModuleUtils::getSectionsFromFeeds($this->feeds), true));
                $this->setResponse(VideoModuleUtils::getSectionsFromFeeds($this->feeds));
                $this->setResponseVersion(1);                
                break;
            case 'videos':
            case 'search':            
                // videos commands requires one argument: section.
                // search requires two arguments: section and q (query).
                $section = $this->getArg('section');
                $query = $this->getArg('q');                
                
                $feedData = $this->feeds[$section];
                $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                $totalItems = $controller->getTotalItems();
                $videos = array();
                
                if ($this->command == 'search') {
                    $items = $controller->search($query, 0, 20);
                }
                else {
                    $items = $controller->items(0, 50);
                }
                
                foreach ($items as $video) {
                    $videos[] = VideoAPIModule::cleanVideoArray((array)$video);
                }
                $this->setResponse($videos);
                $this->setResponseVersion(1);                
                break;            
            default:
                $this->invalidCommand();
                break;
        }
    }
}
