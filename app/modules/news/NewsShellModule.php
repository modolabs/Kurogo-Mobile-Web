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

    protected function preFetchData(DataModel $controller) {
		$maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
		$controller->setStart(0);
		$controller->setLimit($maxPerPage);
		return parent::preFetchData($controller);
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