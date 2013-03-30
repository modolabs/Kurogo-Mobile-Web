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
class NewsShellModule extends ShellModule {

    protected $id = 'news';
    protected static $defaultModel = 'NewsDataModel';
    
    public function getFeed($index) {
        $feeds = $this->loadFeedData();
        if (isset($feeds[$index])) {
            $feedData = $feeds[$index];
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
            $controller = NewsDataModel::factory($modelClass, $feedData);
            
            return $controller;
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
        }
    }

    protected function preFetchData(DataModel $controller, &$response) {
		$maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
		$controller->setStart(0);
		$controller->setLimit($maxPerPage);
		return parent::preFetchData($controller, $response);
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
