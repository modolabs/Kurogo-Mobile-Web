<?php

includePackage('News');
class NewsShellModule extends ShellModule {

    protected $id = 'news';
    protected $legacyController = false;
    protected static $defaultModel = 'NewsDataModel';
    protected static $defaultController = 'RSSDataController';
    
    public function getFeed($index) {
        $feeds = $this->loadFeedData();
        if (isset($feeds[$index])) {
            
            $feedData = $feeds[$index];
            try {
                if (isset($feedData['CONTROLLER_CLASS'])) {
                    $modelClass = $feedData['CONTROLLER_CLASS'];
                } else {
                    $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
                }
                $controller = NewsDataModel::factory($modelClass, $feedData);
            } catch (KurogoException $e) { 
                $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                $this->legacyController = true;
            }
            
            return $controller;
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
        }
    }
    
    protected function initializeForCommand() {

        switch($this->command) {
            case 'stories':
                $categoryID = $this->getArg('id', 0);
                $start = $this->getArg('start');
                $limit = $this->getArg('limit');
                $mode = $this->getArg('mode');

                $feed = $this->getFeed($categoryID);
                
                $maxResults = $this->getOptionalModuleVar('MAX_RESULTS', 5);
                
                return 0;
                break;
                
            case 'test':
                $this->setResponseVersion(1);
                
                break;
                
            default:
                 $this->invalidCommand();
                 break;
        }
    }
}