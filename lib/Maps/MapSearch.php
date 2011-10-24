<?php

class MapSearch {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    protected $feedGroup;
    
    public function __construct($feeds) {
        $this->setFeedData($feeds);
    }
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
    }
    
    public function setFeedGroup($feedGroup) {
        $this->feedGroup = $feedGroup;
    }

    public function getSearchResults() {
        return $this->searchResults;
    }
    
    public function getResultCount() {
        return $this->resultCount;
    }

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataController=null) {
        $this->searchResults = array();

        $resultsByDistance = array();
        $controllers = array();
        if ($dataController !== null) {
            $controllers[] = $dataController;
        } else {
            foreach ($this->feeds as $categoryID => $feedData) {
                $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                if ($controller->canSearch()) { // respect config settings
                    $controllers[] = $controller;
                }
            }
        }

        foreach ($controllers as $controller) {
            try {
                $results = $controller->searchByProximity($center, $tolerance, $maxItems);
                // merge arrays manually since keys are numeric
                foreach($results as $distance => $mapFeature) {
                    // avoid distance collisions
                    while(isset($resultsByDistance[$distance])) {
                        $distance++;
                    }
                    $resultsByDistance[$distance] = $mapFeature;
                }

            } catch (KurogoDataServerException $e) {
                Kurogo::log(LOG_WARNING, 'encountered KurogoDataServerException for feed config: ' . print_r($feedData, true) . $e->getMessage(), 'maps');
            }
        }

        ksort($resultsByDistance);

        if ($maxItems && count($resultsByDistance) > $maxItems) {
            array_splice($resultsByDistance, $maxItems);
        }

        $this->searchResults = array_values($resultsByDistance);
        return $this->searchResults;
    }

    public function searchCampusMap($query) {
        $this->searchResults = array();
    
    	foreach ($this->feeds as $id => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            
            if ($controller->canSearch()) {
                try {
                    $results = $controller->search($query);
                    $this->resultCount += count($results);
                    $this->searchResults = array_merge($this->searchResults, $results);
                } catch (KurogoDataServerException $e) {
                    Kurogo::log(LOG_WARNING,'encountered KurogoDataServerException for feed config: ' . print_r($feedData, true) .  $e->getMessage(), 'maps');
                }
            }
    	}
    	
    	return $this->searchResults;
    }
}



