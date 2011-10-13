<?php

if (!function_exists('mb_convert_encoding')) {
    die('Multibyte String Functions not available (mbstring)');
}

class SiteNewsWebModule extends NewsWebModule {

     public function getFeed($index) {
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = self::defaultController;
            }
            
            //use feed data to instantiate the dataController.
            $feedData['RETRIEVER_CLASS'] = "SOAPDataRetriever"; //SOAPDataRetriever or URLDataRetriever
            $feedData['PARSER_CLASS'] = 'PassthroughDataParser';
            
            //the way of define the SOAP methods
            
            //1. use the config data pass the soap api and api params to SOAPDataRetriever and then $controller
            //will call the getParsedData() method to retieve data
            
            $feedData['api'] = 'getWeatherbyCityName'; //soap api
            $feedData['apiParams'] = array('theCityName' => 58367);
            
            $controller = ExternalDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            
            echo "first call:";
            print_r($controller->getParsedData());
            
            //2. Direct use the datacontroller object call the soap api to retieve data
            //there utilize the __call magic methods in ExternalDataController to route the api to soap client.
            echo "<br/>-------------------<br/>";
            echo "second call:";
            $params = array('theCityName' => 58367);
            print_r($controller->getSupportProvince());
            exit;
            
            return $controller;
        } else {
            throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
        }
     }
     protected function initialize() {

        $this->feeds      = $this->loadFeedData();
        $this->maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
        
        if (count($this->feeds)==0) {
            return;
        }
        
        $this->feedIndex = $this->getArg('section', 0);
        if (!isset($this->feeds[$this->feedIndex])) {
            $this->feedIndex = key($this->feeds);
        }
        
        $feedData = $this->feeds[$this->feedIndex];
        $this->feed = $this->getFeed($this->feedIndex);

        $this->showImages = isset($feedData['SHOW_IMAGES']) ? $feedData['SHOW_IMAGES'] : true;
        $this->showPubDate = isset($feedData['SHOW_PUBDATE']) ? $feedData['SHOW_PUBDATE'] : false;
        $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : false;
        $this->showLink = isset($feedData['SHOW_LINK']) ? $feedData['SHOW_LINK'] : false;
     }
     
     protected function initializeForPage() {
        parent::initializeForPage();
     }  
}
